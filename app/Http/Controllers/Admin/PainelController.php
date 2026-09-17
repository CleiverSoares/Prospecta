<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PainelService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function __construct(
        private readonly PainelService $painelService,
    ) {}

    public function __invoke(Request $request): View
    {
        $dados = $this->painelService->montar($request->only([
            'unidade_id', 'gestor_id', 'vendedor_id', 'de', 'ate', 'segmento',
        ]));

        return view('admin.painel', [
            ...$dados,
            'mapboxToken' => config('prospecta.mapbox.token_front') ?: config('prospecta.mapbox.token'),
            'mapboxStyle' => config('prospecta.mapbox.style_url_front')
                ?: config('prospecta.mapbox.style_url'),
        ]);
    }
}
