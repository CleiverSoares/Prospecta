<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LocalizacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalizacaoTrajetoController extends Controller
{
    public function __construct(
        private readonly LocalizacaoService $localizacaoService,
    ) {}

    public function __invoke(Request $request, User $usuario): JsonResponse
    {
        $minutos = max(15, min(720, (int) $request->integer('minutos', 120)));

        return response()->json([
            'user_id' => $usuario->id,
            'nome' => $usuario->name,
            'pontos' => $this->localizacaoService->trajeto($usuario->id, $minutos),
        ]);
    }
}
