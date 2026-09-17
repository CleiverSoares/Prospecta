<x-layouts.admin titulo="Papéis">
    <x-slot:subtitulo>Controle de acesso (Spatie)</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.papeis.create')">Novo papel</x-admin.botao>
    </x-slot:acoes>

    <div class="admin-table-wrap">
        <ul class="divide-y divide-surface-line/70">
            @forelse ($papeis as $papel)
                <li class="flex items-center justify-between gap-4 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ink">{{ $papel->name }}</p>
                        <p class="mt-0.5 truncate text-xs text-ink-soft sm:text-sm">
                            {{ $papel->permissions->pluck('name')->join(', ') ?: 'Sem permissões' }}
                        </p>
                    </div>
                    <a href="{{ route('admin.papeis.edit', $papel) }}" class="shrink-0 text-sm font-medium text-brand hover:text-brand-strong">
                        Editar
                    </a>
                </li>
            @empty
                <li class="px-5 py-12 text-center text-sm text-ink-soft">Nenhum papel cadastrado.</li>
            @endforelse
        </ul>
    </div>
</x-layouts.admin>
