<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\RotaDiaRepository;
use App\Repositories\VisitaRepository;

class VendedorApiService
{
    public function __construct(
        private readonly VisitaRepository $visitaRepository,
        private readonly RotaDiaRepository $rotaDiaRepository,
    ) {}

    /**
     * @return array{visitas_hoje: int, visitas_semana: int, proxima_rota: string|null}
     */
    public function resumo(User $usuario): array
    {
        $visitasHoje = $this->visitaRepository->contarPorUsuarioDesde($usuario->id, today()->startOfDay());
        $visitasSemana = $this->visitaRepository->contarPorUsuarioDesde($usuario->id, now()->startOfWeek());

        $rota = $this->rotaDiaRepository->buscarDoDia($usuario->id);
        $proxima = null;

        if ($rota && $rota->paradas->isNotEmpty()) {
            $pendente = $rota->paradas->first(
                fn ($parada) => $parada->visita_id === null,
            ) ?? $rota->paradas->first();

            $proxima = $pendente?->prospecto?->razao_social;
        }

        return [
            'visitas_hoje' => $visitasHoje,
            'visitas_semana' => $visitasSemana,
            'proxima_rota' => $proxima,
        ];
    }
}
