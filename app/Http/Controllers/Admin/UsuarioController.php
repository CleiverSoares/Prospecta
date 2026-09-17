<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalvarUsuarioRequest;
use App\Models\Unidade;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $usuarioService,
    ) {}

    public function index(): View
    {
        $query = User::query()
            ->with(['unidade', 'gestor', 'roles'])
            ->orderBy('name');

        if (request()->user()?->hasRole('gestor') && ! request()->user()?->hasRole('adm')) {
            $unidadeId = request()->user()->unidade_id;
            $query->where(function ($q) use ($unidadeId) {
                $q->where('unidade_id', $unidadeId)
                    ->orWhere('gestor_id', request()->user()->id)
                    ->orWhere('id', request()->user()->id);
            });
        }

        return view('admin.usuarios.index', [
            'usuarios' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.create', $this->formData());
    }

    public function store(SalvarUsuarioRequest $request): RedirectResponse
    {
        $usuario = $this->usuarioService->criar($request->validated());

        return redirect()
            ->route('admin.usuarios.edit', $usuario)
            ->with('status', 'Usuário criado.');
    }

    public function edit(User $usuario): View
    {
        $usuario->load(['unidade', 'gestor', 'roles']);

        return view('admin.usuarios.edit', array_merge($this->formData(), [
            'usuario' => $usuario,
        ]));
    }

    public function update(SalvarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->usuarioService->atualizar($usuario, $request->validated());

        return redirect()
            ->route('admin.usuarios.edit', $usuario)
            ->with('status', 'Usuário atualizado.');
    }

    /**
     * @return array{unidades: \Illuminate\Support\Collection, gestores: \Illuminate\Support\Collection, papeis: list<string>}
     */
    private function formData(): array
    {
        return [
            'unidades' => Unidade::query()->orderBy('nome')->get(['id', 'nome']),
            'gestores' => User::query()
                ->role('gestor')
                ->orderBy('name')
                ->get(['id', 'name']),
            'papeis' => ['adm', 'gestor', 'vendedor'],
        ];
    }
}
