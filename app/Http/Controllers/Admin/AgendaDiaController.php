<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\StatusVisita;
use App\Models\User;
use App\Models\Visita;
use Illuminate\View\View;

class AgendaDiaController extends Controller
{
    public function __invoke(): View
    {
        $visitas = Visita::query()
            ->with(['usuario:id,name,unidade_id,foto_path', 'usuario.unidade:id,nome', 'prospecto:id,razao_social,endereco,lat,lng'])
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->groupBy('user_id');

        $vendedores = User::query()
            ->role('vendedor')
            ->where('ativo', true)
            ->with('unidade:id,nome')
            ->orderBy('name')
            ->get(['id', 'name', 'unidade_id', 'foto_path']);

        return view('admin.agenda.index', [
            'visitasPorVendedor' => $visitas,
            'vendedores' => $vendedores,
            'meta' => (int) config('prospecta.meta_visitas_dia', 8),
            'statusFeita' => StatusVisita::Feita,
        ]);
    }
}
