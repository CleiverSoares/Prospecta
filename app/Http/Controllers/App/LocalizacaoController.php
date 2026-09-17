<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\SalvarLocalizacaoRequest;
use App\Services\LocalizacaoService;
use Illuminate\Http\JsonResponse;

class LocalizacaoController extends Controller
{
    public function __construct(
        private readonly LocalizacaoService $localizacaoService,
    ) {}

    public function store(SalvarLocalizacaoRequest $request): JsonResponse
    {
        $loc = $this->localizacaoService->registrar(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'ok' => true,
            'capturado_em' => $loc->capturado_em->toIso8601String(),
        ], 201);
    }
}
