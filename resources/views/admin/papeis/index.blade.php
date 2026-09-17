<x-layouts.admin titulo="Papéis">
    <x-slot:subtitulo>Controle de acesso (Spatie)</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.papeis.create')">Novo papel</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel :padding="false">
        <ul class="divide-y divide-surface-line">
            @forelse ($papeis as $papel)
                <li class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-surface-muted/70">
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
                <li class="px-4 py-10 text-center text-sm text-ink-soft">Nenhum papel cadastrado.</li>
            @endforelse
        </ul>
    </x-admin.painel>
</x-layouts.admin>
