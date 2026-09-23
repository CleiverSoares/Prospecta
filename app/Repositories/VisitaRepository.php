<?php

namespace App\Repositories;

use App\Models\Visita;
use Illuminate\Database\Eloquent\Collection;

class VisitaRepository
{
    public function criar(array $dados): Visita
    {
        return Visita::query()->create($dados);
    }

    public function listarPorUsuario(int $userId): Collection
    {
        return Visita::query()
            ->where('user_id', $userId)
            ->with(['prospecto'])
            ->latest()
            ->get();
    }

    public function buscarPorId(int $id): ?Visita
    {
        return Visita::query()->with(['prospecto', 'usuario'])->find($id);
    }

    /**
     * Check-ins do dia com lat/lng (para regenerar trajeto GPS demo).
     *
     * @return Collection<int, Visita>
     */
    public function checkinsDoDiaComCoordenada(int $userId): Collection
    {
        return Visita::query()
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->whereNotNull('checkin_lat')
            ->whereNotNull('checkin_lng')
            ->orderBy('created_at')
            ->get(['id', 'user_id', 'checkin_lat', 'checkin_lng', 'created_at']);
    }
}
