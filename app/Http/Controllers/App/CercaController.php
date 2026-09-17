<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\ReservarCercaRequest;
use App\Services\CercaTemporariaService;
use Illuminate\Http\JsonResponse;

class CercaController extends Controller
{
    public function __construct(
        private readonly CercaTemporariaService $cercas,
    ) {}

    public function store(ReservarCercaRequest $request): JsonResponse
    {
        $cerca = $this->cercas->reservar($request->user(), $request->validated());

        return response()->json([
            'cerca' => [
                'id' => $cerca->id,
                'expira_em' => $cerca->expira_em->toIso8601String(),
                'rotulo' => $cerca->rotulo,
            ],
        ], 201);
    }
}
