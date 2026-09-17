<?php

namespace App\Http\Controllers\App;

use App\Contracts\ConsultaReceitaInterface;
use App\Enums\StatusReceita;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\ConsultarReceitaRequest;
use App\Repositories\ProspectoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReceitaController extends Controller
{
    public function __construct(
        private readonly ConsultaReceitaInterface $consultaReceita,
        private readonly ProspectoRepository $prospectoRepository,
    ) {}

    public function consultar(ConsultarReceitaRequest $request): JsonResponse
    {
        $cnpj = $request->validated('cnpj');

        try {
            $dados = $this->consultaReceita->consultar($cnpj);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'cnpj' => 'Falha ao consultar Receita: '.$e->getMessage(),
            ]);
        }

        $status = StatusReceita::tryFrom((string) ($dados['status_receita'] ?? ''))
            ?? StatusReceita::Desconhecido;

        if (in_array($status, [StatusReceita::Baixada, StatusReceita::Inapta, StatusReceita::Nulo], true)) {
            return response()->json([
                'ok' => false,
                'bloqueado' => true,
                'motivo' => 'CNPJ '.$status->value.' — fora do ICP / não prospectar.',
                'dados' => $dados,
            ], 422);
        }

        $prospecto = null;
        if ($request->filled('prospecto_id')) {
            $existente = $this->prospectoRepository->buscarPorId((int) $request->validated('prospecto_id'));
            if ($existente) {
                $prospecto = $this->prospectoRepository->atualizar($existente, [
                    'cnpj' => $dados['cnpj'],
                    'razao_social' => $dados['razao_social'] ?: $existente->razao_social,
                    'cep' => $dados['cep'] ?: $existente->cep,
                    'status_receita' => $status->value,
                    'lat' => $dados['lat'] ?? $existente->lat,
                    'lng' => $dados['lng'] ?? $existente->lng,
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'bloqueado' => false,
            'dados' => $dados,
            'prospecto' => $prospecto ? [
                'id' => $prospecto->id,
                'cnpj' => $prospecto->cnpj,
                'razao_social' => $prospecto->razao_social,
                'status_receita' => $prospecto->status_receita?->value ?? $prospecto->status_receita,
                'cep' => $prospecto->cep,
            ] : null,
            'driver' => config('prospecta.receita_ws.driver'),
        ]);
    }
}
