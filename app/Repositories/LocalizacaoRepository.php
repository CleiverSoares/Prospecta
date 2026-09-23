<?php

namespace App\Repositories;

use App\Models\Localizacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LocalizacaoRepository
{
    public function registrar(array $dados): Localizacao
    {
        return Localizacao::query()->create($dados);
    }

    /**
     * Última posição por vendedor (últimos N minutos).
     *
     * @return Collection<int, Localizacao>
     */
    public function ultimasPorUsuario(int $minutos = 15, ?int $unidadeId = null, ?int $gestorId = null, ?int $vendedorId = null): Collection
    {
        $desde = now()->subMinutes($minutos);

        $sub = Localizacao::query()
            ->select('user_id', DB::raw('MAX(id) as max_id'))
            ->where('capturado_em', '>=', $desde)
            ->groupBy('user_id');

        $query = Localizacao::query()
            ->select('localizacoes.*')
            ->with([
                'usuario:id,name,unidade_id,gestor_id,foto_path',
                'usuario.unidade:id,nome,tipo,poligono_geojson',
            ])
            ->joinSub($sub, 'ult', fn ($join) => $join->on('localizacoes.id', '=', 'ult.max_id'));

        if ($vendedorId) {
            $query->where('localizacoes.user_id', $vendedorId);
        } elseif ($gestorId) {
            $query->whereHas('usuario', fn ($q) => $q->where('gestor_id', $gestorId)->orWhere('id', $gestorId));
        } elseif ($unidadeId) {
            $query->whereHas('usuario', fn ($q) => $q->where('unidade_id', $unidadeId));
        }

        return $query->get();
    }

    /**
     * @return Collection<int, Localizacao>
     */
    public function historicoDoUsuario(int $userId, int $minutos = 120): Collection
    {
        return Localizacao::query()
            ->where('user_id', $userId)
            ->where('capturado_em', '>=', now()->subMinutes($minutos))
            ->orderBy('capturado_em')
            ->get(['id', 'user_id', 'lat', 'lng', 'velocidade', 'capturado_em']);
    }

    public function contarRecentes(int $minutos = 15): int
    {
        return Localizacao::query()
            ->where('capturado_em', '>=', now()->subMinutes($minutos))
            ->count();
    }

    public function contarVendedoresComSinal(int $minutos = 15): int
    {
        return Localizacao::query()
            ->where('capturado_em', '>=', now()->subMinutes($minutos))
            ->pluck('user_id')
            ->unique()
            ->count();
    }

    /**
     * @param  list<int>  $userIds
     */
    public function apagarPorUsuarios(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        Localizacao::query()->whereIn('user_id', $userIds)->delete();
    }

    /**
     * Interpola pontos entre waypoints e termina com ping “agora” (ao vivo).
     *
     * @param  list<array{lat: float, lng: float}>  $waypoints
     */
    public function semearTrajeto(int $userId, array $waypoints): void
    {
        if (count($waypoints) < 2) {
            return;
        }

        $inicio = now()->subMinutes(40);
        $intervalo = (int) max(60, (40 * 60) / max(1, (count($waypoints) - 1) * 6));
        $pontos = [];
        $tick = 0;

        for ($i = 0; $i < count($waypoints) - 1; $i++) {
            $a = $waypoints[$i];
            $b = $waypoints[$i + 1];
            $passos = 6;
            for ($s = 0; $s < $passos; $s++) {
                $t = $s / $passos;
                $jitterLat = sin(($i + $s) * 1.7) * 0.00035;
                $jitterLng = cos(($i + $s) * 1.3) * 0.00035;
                $pontos[] = [
                    'lat' => $a['lat'] + (($b['lat'] - $a['lat']) * $t) + $jitterLat,
                    'lng' => $a['lng'] + (($b['lng'] - $a['lng']) * $t) + $jitterLng,
                    'em' => $inicio->copy()->addSeconds($tick * $intervalo),
                ];
                $tick++;
            }
        }

        $ultimo = $waypoints[array_key_last($waypoints)];
        $pontos[] = [
            'lat' => $ultimo['lat'] + 0.0002,
            'lng' => $ultimo['lng'] + 0.00015,
            'em' => now()->subMinutes(1),
        ];

        foreach ($pontos as $idx => $p) {
            Localizacao::query()->create([
                'user_id' => $userId,
                'lat' => $p['lat'],
                'lng' => $p['lng'],
                'precisao' => 8 + ($idx % 5),
                'velocidade' => $idx === array_key_last($pontos) ? 0 : 3.8 + ($idx % 3),
                'direcao' => 40 + (($idx * 17) % 280),
                'capturado_em' => $p['em'],
            ]);
        }
    }
}
