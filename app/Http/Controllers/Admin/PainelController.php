<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prospecto;
use App\Models\Unidade;
use App\Models\User;
use App\Models\Visita;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function __invoke(): View
    {
        $unidadesMapa = Unidade::query()
            ->whereNotNull('poligono_geojson')
            ->orderBy('nome')
            ->get(['id', 'nome', 'poligono_geojson'])
            ->map(fn (Unidade $u) => [
                'id' => $u->id,
                'nome' => $u->nome,
                'poligono' => $u->poligono_geojson,
            ])
            ->values();

        return view('admin.painel', [
            'metricas' => [
                'unidades' => Unidade::query()->count(),
                'usuarios' => User::query()->count(),
                'prospectos' => Prospecto::query()->count(),
                'visitas_hoje' => Visita::query()->whereDate('created_at', today())->count(),
            ],
            'unidadesMapa' => $unidadesMapa,
            'mapboxToken' => config('prospecta.mapbox.token_front') ?: config('prospecta.mapbox.token'),
            'mapboxStyle' => config('prospecta.mapbox.style_url_front')
                ?: config('prospecta.mapbox.style_url'),
        ]);
    }
}
