<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DemoMassaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DemoMassaController extends Controller
{
    public function __construct(
        private readonly DemoMassaService $demoMassaService,
    ) {}

    public function index(): View
    {
        $this->garantirHabilitado();

        return view('admin.demo.index', [
            'habilitado' => true,
        ]);
    }

    public function regenerar(): RedirectResponse
    {
        $this->garantirHabilitado();

        $resumo = $this->demoMassaService->regenerarCampo();

        return redirect()
            ->route('admin.demo.index')
            ->with(
                'sucesso',
                sprintf(
                    'Mapa ao vivo regenerado: %d usuários demo, %d vendedor(es) com GPS na janela, %d pontos recentes.',
                    $resumo['usuarios_demo'],
                    $resumo['vendedores_ao_vivo'],
                    $resumo['localizacoes_recentes'],
                ),
            );
    }

    private function garantirHabilitado(): void
    {
        abort_unless($this->demoMassaService->habilitado(), 404);
    }
}
