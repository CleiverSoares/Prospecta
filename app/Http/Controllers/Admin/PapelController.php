<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalvarPapelRequest;
use App\Http\Requests\Admin\SincronizarPermissoesPapelRequest;
use App\Services\PapelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class PapelController extends Controller
{
    public function __construct(
        private readonly PapelService $papelService,
    ) {}

    public function index(): View
    {
        return view('admin.papeis.index', [
            'papeis' => $this->papelService->listarPapeis(),
        ]);
    }

    public function create(): View
    {
        return view('admin.papeis.create');
    }

    public function store(SalvarPapelRequest $request): RedirectResponse
    {
        $papel = $this->papelService->criar($request->validated('nome'));

        return redirect()
            ->route('admin.papeis.edit', $papel)
            ->with('status', 'Papel criado.');
    }

    public function edit(Role $papel): View
    {
        $papel = $this->papelService->buscar($papel->id) ?? $papel;

        return view('admin.papeis.edit', [
            'papel' => $papel,
            'permissoes' => $this->papelService->listarPermissoes(),
        ]);
    }

    public function update(SincronizarPermissoesPapelRequest $request, Role $papel): RedirectResponse
    {
        $this->papelService->sincronizarPermissoes(
            $papel,
            $request->validated('permissoes', []),
        );

        return redirect()
            ->route('admin.papeis.edit', $papel)
            ->with('status', 'Permissões atualizadas.');
    }
}
