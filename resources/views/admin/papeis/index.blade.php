<x-layouts.admin titulo="Papéis">
    <x-slot:subtitulo>Roles Spatie — permissões dinâmicas no banco</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.papeis.create')">Novo papel</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel :padding="false">
        <ul class="divide-y divide-brand/10">
            @forelse ($papeis as $papel)
                <li class="flex items-center justify-between gap-4 px-5 py-5 transition hover:bg-white/[0.03]">
                    <div class="min-w-0">
                        <p class="font-display text-lg font-bold text-paper">{{ $papel->name }}</p>
                        <p class="mt-1 truncate text-sm text-paper/45">
                            {{ $papel->permissions->pluck('name')->join(', ') ?: 'Sem permissões' }}
                        </p>
                    </div>
                    <a href="{{ route('admin.papeis.edit', $papel) }}" class="shrink-0 font-bold text-brand hover:text-brand-strong">
                        Editar
                    </a>
                </li>
            @empty
                <li class="px-5 py-12 text-center text-sm text-paper/45">Nenhum papel cadastrado.</li>
            @endforelse
        </ul>
    </x-admin.painel>
</x-layouts.admin>
