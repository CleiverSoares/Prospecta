<?php

namespace App\Http\Middleware;

use App\Services\SetupDiaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupApi
{
    public function __construct(
        private readonly SetupDiaService $setupDiaService,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $this->setupDiaService->completoHoje($usuario)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Complete o Setup do dia antes de continuar.',
            'codigo' => 'setup_obrigatorio',
        ], 422);
    }
}
