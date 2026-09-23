<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DemoMassaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

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

        try {
            $resumo = $this->demoMassaService->regenerarCampo();
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.demo.index')
                ->with('erro', 'Falha ao regenerar o mapa ao vivo. Tente de novo em instantes.');
        }

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

    public function clientesMock(): RedirectResponse
    {
        $this->garantirHabilitado();

        $resumo = $this->demoMassaService->gerarClientesMock();

        return redirect()
            ->route('admin.demo.index')
            ->with(
                'sucesso',
                sprintf(
                    'Clientes mock no mapa: %d pins upsertados · %d clientes com coordenadas no Painel.',
                    $resumo['criados'],
                    $resumo['clientes_no_mapa'],
                ),
            );
    }

    private function garantirHabilitado(): void
    {
        abort_unless($this->demoMassaService->habilitado(), 404);
    }
}
