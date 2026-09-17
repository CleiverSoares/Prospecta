<x-layouts.admin titulo="Usuários">
    <x-slot:subtitulo>Equipe, unidade, gestor e papéis</x-slot:subtitulo>

    <x-slot:acoes>
        @can('usuarios.criar')
            <x-admin.botao :href="route('admin.usuarios.create')">Novo usuário</x-admin.botao>
        @endcan
    </x-slot:acoes>

    <x-admin.painel :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-surface-line bg-surface-muted text-ink-soft">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Nome</th>
                        <th class="px-4 py-2.5 font-medium">E-mail</th>
                        <th class="px-4 py-2.5 font-medium">Papel</th>
                        <th class="px-4 py-2.5 font-medium">Unidade</th>
                        <th class="px-4 py-2.5 font-medium">Gestor</th>
                        <th class="px-4 py-2.5 font-medium"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-line">
                    @forelse ($usuarios as $usuario)
                        <tr class="hover:bg-surface-muted/70">
                            <td class="px-4 py-2.5 font-medium text-ink">{{ $usuario->name }}</td>
                            <td class="px-4 py-2.5 text-ink-soft">{{ $usuario->email }}</td>
                            <td class="px-4 py-2.5 text-ink-soft">
                                {{ $usuario->roles->pluck('name')->join(', ') ?: '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-ink-soft">{{ $usuario->unidade?->nome ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-ink-soft">{{ $usuario->gestor?->name ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-right">
                                @can('usuarios.editar')
                                    <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="font-medium text-brand hover:text-brand-strong">
                                        Editar
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-ink-soft">
                                Nenhum usuário cadastrado.
                                @can('usuarios.criar')
                                    <a href="{{ route('admin.usuarios.create') }}" class="font-medium text-brand hover:underline">Criar o primeiro</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usuarios->hasPages())
            <div class="border-t border-surface-line px-4 py-3">
                {{ $usuarios->links() }}
            </div>
        @endif
    </x-admin.painel>
</x-layouts.admin>
