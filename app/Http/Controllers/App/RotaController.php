<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\GerarRotaRequest;
use App\Repositories\ProspectoRepository;
use App\Services\RotaService;
use Illuminate\Http\JsonResponse;

class RotaController extends Controller
{
    public function __construct(
        private readonly RotaService $rotaService,
        private readonly ProspectoRepository $prospectoRepository,
    ) {}

    public function gerar(GerarRotaRequest $request): JsonResponse
    {
        $ids = $request->validated('prospecto_ids');
        $prospectos = $this->prospectoRepository->buscarPorIds($ids);

        $origem = null;
        if ($request->filled('origem_lat') && $request->filled('origem_lng')) {
            $origem = [
                'lat' => (float) $request->validated('origem_lat'),
                'lng' => (float) $request->validated('origem_lng'),
            ];
        }

        $rota = $this->rotaService->gerar(
            $prospectos,
            $origem,
            $request->validated('limite'),
            [
                'segmento' => $request->validated('segmento'),
                'horas' => $request->validated('horas'),
                'mix_prospeccao' => (float) $request->validated('mix_prospeccao'),
            ],
        );

        return response()->json($rota);
    }
}
