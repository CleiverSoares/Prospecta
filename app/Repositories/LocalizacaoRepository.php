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
}
