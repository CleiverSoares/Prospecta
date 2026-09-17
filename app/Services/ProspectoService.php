<?php

namespace App\Services;

use App\Contracts\ConsultaReceitaInterface;
use App\Models\Prospecto;
use App\Models\User;
use App\Repositories\ProspectoRepository;
use Illuminate\Validation\ValidationException;

class ProspectoService
{
    public function __construct(
        private readonly ProspectoRepository $prospectoRepository,
        private readonly ConsultaReceitaInterface $consultaReceita,
        private readonly TerritorioService $territorioService,
    ) {}

    public function buscarPorCnpj(string $cnpj): ?Prospecto
    {
        return $this->prospectoRepository->buscarPorCnpj($this->normalizarCnpj($cnpj));
    }

    /**
     * Consulta Receita, valida território e faz upsert por CNPJ.
     *
     * @return array{prospecto: Prospecto, territorio: array<string, mixed>}
     */
    public function buscar(User $usuario, string $cnpj): array
    {
        $cnpjLimpo = $this->normalizarCnpj($cnpj);
        $dados = $this->consultaReceita->consultar($cnpjLimpo);
        $territorio = $this->territorioService->verificarCep($usuario, $dados['cep']);

        if (! $territorio['permitido']) {
            $nome = $territorio['unidade_nome'] ?? 'outra unidade';

            throw ValidationException::withMessages([
                'cnpj' => "CEP do prospecto está em território bloqueado ({$nome}).",
            ]);
        }

        $prospecto = $this->prospectoRepository->upsertPorCnpj($cnpjLimpo, [
            'cnpj' => $cnpjLimpo,
            'razao_social' => $dados['razao_social'],
            'cep' => $dados['cep'],
            'lat' => $dados['lat'],
            'lng' => $dados['lng'],
            'status_receita' => $dados['status_receita'],
        ]);

        return [
            'prospecto' => $prospecto,
            'territorio' => $territorio,
        ];
    }

    public function upsertPorCnpj(string $cnpj, array $dados): Prospecto
    {
        return $this->prospectoRepository->upsertPorCnpj($this->normalizarCnpj($cnpj), $dados);
    }

    private function normalizarCnpj(string $cnpj): string
    {
        return preg_replace('/\D+/', '', $cnpj) ?? '';
    }
}
