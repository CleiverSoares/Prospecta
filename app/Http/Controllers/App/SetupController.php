<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\SalvarSetupRequest;
use App\Services\SetupDiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function __construct(
        private readonly SetupDiaService $setupDiaService,
    ) {}

    public function __invoke(): View
    {
        $setup = session('prospecta.setup');
        if (! is_array($setup) || $setup === []) {
            $setup = $this->setupDiaService->buscarHoje(auth()->user()) ?? [];
            if ($setup !== []) {
                session(['prospecta.setup' => $setup]);
            }
        }

        return view('app.setup', [
            'setup' => $setup,
        ]);
    }

    public function store(SalvarSetupRequest $request): JsonResponse|RedirectResponse
    {
        $dados = $this->setupDiaService->salvar($request->user(), $request->validated());
        $request->session()->put('prospecta.setup', $dados);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'setup' => $dados,
                'redirect' => route('app.area'),
            ]);
        }

        return redirect()
            ->route('app.area')
            ->with('status', 'Setup do dia salvo.');
    }
}
