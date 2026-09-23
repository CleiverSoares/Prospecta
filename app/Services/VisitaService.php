<?php

namespace App\Services;

use App\Enums\StatusVisita;
use App\Events\VisitaConcluida;
use App\Models\Prospecto;
use App\Models\Visita;
use App\Repositories\VisitaRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class VisitaService
{
    public function __construct(
        private readonly VisitaRepository $visitaRepository,
        private readonly RotaDiaService $rotaDiaService,
    ) {}

    /**
     * @param  array{
     *     prospecto_id: int,
     *     user_id: int,
     *     status: string|StatusVisita,
     *     checkin_lat?: float|null,
     *     checkin_lng?: float|null,
     *     caminho_foto?: string|null,
     *     caminho_audio?: string|null
     * }  $dados
     */
    public function criar(array $dados): Visita
    {
        $status = $dados['status'] instanceof StatusVisita
            ? $dados['status']
            : StatusVisita::from((string) $dados['status']);

        $prospecto = Prospecto::query()->findOrFail($dados['prospecto_id']);
        $lat = isset($dados['checkin_lat']) ? (float) $dados['checkin_lat'] : null;
        $lng = isset($dados['checkin_lng']) ? (float) $dados['checkin_lng'] : null;

        if ($lat === null || $lng === null) {
            throw ValidationException::withMessages([
                'checkin_lat' => 'GPS obrigatório para check-in.',
            ]);
        }

        if ($prospecto->lat !== null && $prospecto->lng !== null) {
            $dist = $this->distanciaMetros($lat, $lng, (float) $prospecto->lat, (float) $prospecto->lng);
            if ($dist > 100) {
                throw ValidationException::withMessages([
                    'checkin_lat' => 'Você está a '.round($dist).'m do pin (máximo 100m).',
                ]);
            }
        }

        if ($status === StatusVisita::Feita) {
            if (empty($dados['caminho_foto'])) {
                throw ValidationException::withMessages([
                    'foto' => 'Foto da fachada é obrigatória em visita feita.',
                ]);
            }
            if (empty($dados['caminho_audio'])) {
                throw ValidationException::withMessages([
                    'audio' => 'Áudio de até 15s é obrigatório em visita feita.',
                ]);
            }
        }

        $visita = $this->visitaRepository->criar([
            ...$dados,
            'status' => $status,
            'checkin_lat' => $lat,
            'checkin_lng' => $lng,
        ]);

        $this->rotaDiaService->marcarCheckin($visita);

        VisitaConcluida::dispatch($visita);

        return $visita;
    }

    public function listarPorUsuario(int $userId): Collection
    {
        return $this->visitaRepository->listarPorUsuario($userId);
    }

    private function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }
}
