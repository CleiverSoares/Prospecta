<x-layouts.admin titulo="Usuários">
    <x-slot:subtitulo>Equipe, unidade, gestor e papéis</x-slot:subtitulo>

    <x-slot:acoes>
        @can('usuarios.criar')
            <x-admin.botao :href="route('admin.usuarios.create')">Novo usuário</x-admin.botao>
        @endcan
    </x-slot:acoes>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Buscar nome ou e-mail"
            class="h-10 min-w-[14rem] flex-1 rounded-2xl border-0 bg-white/90 px-4 text-sm shadow-sm focus:ring-2 focus:ring-brand/25"
        >
        <button type="submit" class="h-10 rounded-2xl bg-brand px-4 text-sm font-semibold text-white">Filtrar</button>
    </form>

    <div class="admin-table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-surface-line/80">
                    <tr>
                        <th class="px-5 py-3.5">Nome</th>
                        <th class="px-5 py-3.5">E-mail</th>
                        <th class="px-5 py-3.5">Papel</th>
                        <th class="px-5 py-3.5">Unidade</th>
                        <th class="px-5 py-3.5">Gestor</th>
                        <th class="px-5 py-3.5"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-line/70">
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td class="px-5 py-3.5 font-medium text-ink">{{ $usuario->name }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $usuario->email }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">
                                {{ $usuario->roles->pluck('name')->join(', ') ?: '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $usuario->unidade?->nome ?: '—' }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $usuario->gestor?->name ?: '—' }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @can('usuarios.editar')
                                    <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="font-medium text-brand hover:text-brand-strong">
                                        Editar
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-ink-soft">
                                Nenhum usuário cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usuarios->hasPages())
            <div class="border-t border-surface-line/70 px-5 py-3">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
