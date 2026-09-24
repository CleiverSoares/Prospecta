<?php

namespace App\Http\Middleware;

use App\Services\SetupDiaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupDiario
{
    public function __construct(
        private readonly SetupDiaService $setupDiaService,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $setup = $request->session()->get('prospecta.setup');

        if (! $this->completo($setup) && $request->user()) {
            $doBanco = $this->setupDiaService->buscarHoje($request->user());
            if ($doBanco) {
                $request->session()->put('prospecta.setup', $doBanco);
                $setup = $doBanco;
            }
        }

        if ($this->completo($setup)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Complete o Setup do dia antes de continuar.',
                'redirect' => route('app.setup'),
            ], 422);
        }

        return redirect()
            ->route('app.setup')
            ->with('aviso', 'Preencha o Setup do dia (4 campos) para liberar a rota.');
    }

    /**
     * @param  mixed  $setup
     */
    private function completo(mixed $setup): bool
    {
        if (! is_array($setup)) {
            return false;
        }

        return filled($setup['local'] ?? null)
            && filled($setup['segmento'] ?? null)
            && filled($setup['horas'] ?? null)
            && array_key_exists('mix_prospeccao', $setup)
            && $setup['mix_prospeccao'] !== null
            && $setup['mix_prospeccao'] !== '';
    }
}
