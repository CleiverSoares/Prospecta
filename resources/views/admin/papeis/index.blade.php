<x-layouts.admin titulo="Papéis">
    <x-slot:subtitulo>Roles Spatie e permissões dinâmicas</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.papeis.create')">Novo papel</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel :padding="false">
        <ul class="divide-y divide-slate-100">
            @forelse ($papeis as $papel)
                <li class="flex items-center justify-between gap-4 px-4 py-4 hover:bg-slate-50/80">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900">{{ $papel->name }}</p>
                        <p class="mt-0.5 truncate text-sm text-slate-500">
                            {{ $papel->permissions->pluck('name')->join(', ') ?: 'Sem permissões' }}
                        </p>
                    </div>
                    <a href="{{ route('admin.papeis.edit', $papel) }}" class="shrink-0 font-medium text-brand-strong hover:underline">
                        Editar
                    </a>
                </li>
            @empty
                <li class="px-4 py-10 text-center text-sm text-slate-500">Nenhum papel cadastrado.</li>
            @endforelse
        </ul>
    </x-admin.painel>
</x-layouts.admin>
