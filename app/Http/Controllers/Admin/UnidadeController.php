<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoUnidade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalvarUnidadeRequest;
use App\Models\Unidade;
use App\Services\UnidadeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UnidadeController extends Controller
{
    public function __construct(
        private readonly UnidadeService $unidadeService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Unidade::class);

        return view('admin.unidades.index', [
            'unidades' => $this->unidadeService->listar(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Unidade::class);

        return view('admin.unidades.create', [
            'tipos' => TipoUnidade::cases(),
        ]);
    }

    public function store(SalvarUnidadeRequest $request): RedirectResponse
    {
        $this->authorize('create', Unidade::class);
        $unidade = $this->unidadeService->criar($request->validated());

        return redirect()
            ->route('admin.unidades.edit', $unidade)
            ->with('status', 'Unidade criada.');
    }

    public function edit(Unidade $unidade): View
    {
        $this->authorize('update', $unidade);

        return view('admin.unidades.edit', [
            'unidade' => $unidade,
            'tipos' => TipoUnidade::cases(),
        ]);
    }

    public function update(SalvarUnidadeRequest $request, Unidade $unidade): RedirectResponse
    {
        $this->authorize('update', $unidade);
        $this->unidadeService->atualizar($unidade, $request->validated());

        return redirect()
            ->route('admin.unidades.edit', $unidade)
            ->with('status', 'Unidade atualizada.');
    }
}
