<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LocalizacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalizacaoAoVivoController extends Controller
{
    public function __construct(
        private readonly LocalizacaoService $localizacaoService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $minutos = (int) $request->integer('minutos', 15);

        return response()->json([
            'vendedores' => $this->localizacaoService->aoVivo(
                $request->filled('unidade_id') ? (int) $request->input('unidade_id') : null,
                $request->filled('gestor_id') ? (int) $request->input('gestor_id') : null,
                $request->filled('vendedor_id') ? (int) $request->input('vendedor_id') : null,
                max(1, min(120, $minutos)),
            ),
        ]);
    }
}
