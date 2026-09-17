<?php

namespace App\Services;

use App\Models\Unidade;
use App\Repositories\UnidadeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class UnidadeService
{
    public function __construct(
        private readonly UnidadeRepository $unidadeRepository,
    ) {}

    public function listar(): Collection
    {
        return $this->unidadeRepository->todos();
    }

    public function buscar(int $id): ?Unidade
    {
        return $this->unidadeRepository->buscarPorId($id);
    }

    public function criar(array $dados): Unidade
    {
        return $this->unidadeRepository->criar($this->normalizar($dados));
    }

    public function atualizar(Unidade $unidade, array $dados): Unidade
    {
        return $this->unidadeRepository->atualizar($unidade, $this->normalizar($dados));
    }

    public function excluir(Unidade $unidade): void
    {
        $this->unidadeRepository->excluir($unidade);
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function normalizar(array $dados): array
    {
        if (isset($dados['cep_inicio'])) {
            $dados['cep_inicio'] = preg_replace('/\D+/', '', (string) $dados['cep_inicio']);
        }

        if (isset($dados['cep_fim'])) {
            $dados['cep_fim'] = preg_replace('/\D+/', '', (string) $dados['cep_fim']);
        }

        if (
            filled($dados['cep_inicio'] ?? null)
            && filled($dados['cep_fim'] ?? null)
            && $dados['cep_inicio'] > $dados['cep_fim']
        ) {
            throw ValidationException::withMessages([
                'cep_fim' => 'O CEP final deve ser maior ou igual ao CEP inicial.',
            ]);
        }

        return $dados;
    }
}
