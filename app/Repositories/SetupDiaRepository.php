<?php

namespace App\Repositories;

use App\Models\SetupDia;
use Illuminate\Support\Carbon;

class SetupDiaRepository
{
    public function buscarDoDia(int $userId, Carbon|string|null $data = null): ?SetupDia
    {
        $dia = $data instanceof Carbon ? $data->toDateString() : ($data ?: today()->toDateString());

        return SetupDia::query()
            ->where('user_id', $userId)
            ->whereDate('data', $dia)
            ->first();
    }

    /**
     * @param  array{local: string, segmento: string, horas: string, mix_prospeccao: float}  $dados
     */
    public function salvarDoDia(int $userId, array $dados, Carbon|string|null $data = null): SetupDia
    {
        $dia = $data instanceof Carbon ? $data->toDateString() : ($data ?: today()->toDateString());

        return SetupDia::query()->updateOrCreate(
            ['user_id' => $userId, 'data' => $dia],
            [
                'local' => $dados['local'],
                'segmento' => $dados['segmento'],
                'horas' => $dados['horas'],
                'mix_prospeccao' => $dados['mix_prospeccao'],
            ],
        );
    }
}
