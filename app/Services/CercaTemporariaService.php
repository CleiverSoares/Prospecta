<?php

namespace App\Services;

use App\Models\CercaTemporaria;
use App\Models\User;
use Illuminate\Support\Carbon;

class CercaTemporariaService
{
    /**
     * @param  array{rotulo?: string, cep_inicio?: string|null, cep_fim?: string|null, poligono_geojson?: array|null}  $dados
     */
    public function reservar(User $user, array $dados): CercaTemporaria
    {
        $dias = (int) config('prospecta.cerca_dias', 30);

        return CercaTemporaria::query()->create([
            'user_id' => $user->id,
            'rotulo' => $dados['rotulo'] ?? 'Cerca do dia',
            'cep_inicio' => $dados['cep_inicio'] ?? null,
            'cep_fim' => $dados['cep_fim'] ?? null,
            'poligono_geojson' => $dados['poligono_geojson'] ?? null,
            'expira_em' => Carbon::now()->addDays($dias),
        ]);
    }

    public function conflitoComOutro(User $user, ?string $cep): ?CercaTemporaria
    {
        if (! filled($cep)) {
            return null;
        }

        $cepLimpo = preg_replace('/\D+/', '', $cep) ?? '';

        return CercaTemporaria::query()
            ->where('user_id', '!=', $user->id)
            ->where('expira_em', '>', now())
            ->whereNotNull('cep_inicio')
            ->whereNotNull('cep_fim')
            ->where('cep_inicio', '<=', $cepLimpo)
            ->where('cep_fim', '>=', $cepLimpo)
            ->first();
    }
}
