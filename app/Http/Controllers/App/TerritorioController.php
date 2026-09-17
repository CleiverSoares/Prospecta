<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\VerificarTerritorioRequest;
use App\Services\TerritorioService;
use Illuminate\Http\JsonResponse;

class TerritorioController extends Controller
{
    public function __construct(
        private readonly TerritorioService $territorioService,
    ) {}

    public function verificar(VerificarTerritorioRequest $request): JsonResponse
    {
        $resultado = $this->territorioService->verificarCep(
            $request->user(),
            $request->validated('cep'),
        );

        return response()->json($resultado);
    }
}
