<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\SalvarSetupRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function __invoke(): View
    {
        return view('app.setup', [
            'setup' => session('prospecta.setup', []),
        ]);
    }

    public function store(SalvarSetupRequest $request): JsonResponse|RedirectResponse
    {
        $dados = [
            'local' => $request->validated('local'),
            'segmento' => $request->validated('segmento'),
            'horas' => $request->validated('horas'),
            'mix_prospeccao' => (float) $request->validated('mix_prospeccao'),
            'salvo_em' => now()->toIso8601String(),
        ];

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
