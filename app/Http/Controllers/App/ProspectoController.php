<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\BuscarProspectoRequest;
use App\Services\ProspectoService;
use Illuminate\Http\JsonResponse;

class ProspectoController extends Controller
{
    public function __construct(
        private readonly ProspectoService $prospectoService,
    ) {}

    public function buscar(BuscarProspectoRequest $request): JsonResponse
    {
        $resultado = $this->prospectoService->buscar(
            $request->user(),
            $request->validated('cnpj'),
        );

        return response()->json([
            'prospecto' => $resultado['prospecto'],
            'territorio' => $resultado['territorio'],
        ]);
    }
}
