<?php

namespace App\Repositories;

use App\Enums\StatusParadaPlanejada;
use App\Models\ParadaPlanejada;
use App\Models\RotaDia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RotaDiaRepository
{
    public function buscarDoDia(int $userId, Carbon|string|null $data = null): ?RotaDia
    {
        $dia = $data instanceof Carbon ? $data->toDateString() : ($data ?: today()->toDateString());

        return RotaDia::query()
            ->where('user_id', $userId)
            ->whereDate('data', $dia)
            ->with(['paradas.prospecto:id,razao_social,endereco,lat,lng', 'paradas.visita:id,status'])
            ->first();
    }

    /**
     * @param  list<array{prospecto_id: int, ordem: int, lat?: float|null, lng?: float|null}>  $paradas
     */
    public function publicar(int $userId, Carbon|string $data, array $paradas): RotaDia
    {
        $dia = $data instanceof Carbon ? $data->toDateString() : $data;

        return DB::transaction(function () use ($userId, $dia, $paradas) {
            $rota = RotaDia::query()->updateOrCreate(
                ['user_id' => $userId, 'data' => $dia],
                ['total_paradas' => count($paradas)],
            );

            ParadaPlanejada::query()->where('rota_dia_id', $rota->id)->delete();

            foreach ($paradas as $parada) {
                ParadaPlanejada::query()->create([
                    'rota_dia_id' => $rota->id,
                    'prospecto_id' => $parada['prospecto_id'],
                    'ordem' => $parada['ordem'],
                    'lat' => $parada['lat'] ?? null,
                    'lng' => $parada['lng'] ?? null,
                    'status' => StatusParadaPlanejada::Pendente,
                    'visita_id' => null,
                ]);
            }

            return $rota->fresh(['paradas.prospecto']);
        });
    }

    /**
     * Remove o plano do dia (paradas + rota) — some da Agenda admin.
     */
    public function cancelarDoDia(int $userId, Carbon|string|null $data = null): bool
    {
        $rota = $this->buscarDoDia($userId, $data);
        if (! $rota) {
            return false;
        }

        return DB::transaction(function () use ($rota) {
            ParadaPlanejada::query()->where('rota_dia_id', $rota->id)->delete();
            $rota->delete();

            return true;
        });
    }

    public function marcarParadaFeita(int $userId, int $prospectoId, int $visitaId, Carbon|string|null $data = null): ?ParadaPlanejada
    {
        $rota = $this->buscarDoDia($userId, $data);
        if (! $rota) {
            return null;
        }

        $parada = ParadaPlanejada::query()
            ->where('rota_dia_id', $rota->id)
            ->where('prospecto_id', $prospectoId)
            ->where('status', StatusParadaPlanejada::Pendente)
            ->orderBy('ordem')
            ->first();

        if (! $parada) {
            return null;
        }

        $parada->update([
            'status' => StatusParadaPlanejada::Feita,
            'visita_id' => $visitaId,
        ]);

        return $parada->fresh(['prospecto', 'visita']);
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, RotaDia>
     */
    public function listarDoDiaPorUsuarios(array $userIds, Carbon|string|null $data = null): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        $dia = $data instanceof Carbon ? $data->toDateString() : ($data ?: today()->toDateString());

        return RotaDia::query()
            ->whereIn('user_id', $userIds)
            ->whereDate('data', $dia)
            ->with([
                'paradas' => fn ($q) => $q->orderBy('ordem'),
                'paradas.prospecto:id,razao_social,endereco,lat,lng',
                'paradas.visita:id,status',
            ])
            ->get()
            ->keyBy('user_id');
    }
}
