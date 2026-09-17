<x-layouts.admin titulo="Painel">
    <x-slot:subtitulo>Área desktop do Prospecta</x-slot:subtitulo>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @can('unidades.ver')
            <a href="{{ route('admin.unidades.index') }}" class="group block rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40 transition hover:border-brand/40 hover:shadow-md">
                <p class="font-display text-lg font-bold text-slate-900 group-hover:text-ink">Unidades</p>
                <p class="mt-2 text-sm text-slate-500">Matriz, filial e representação.</p>
            </a>
        @endcan

        @can('papeis.gerenciar')
            <a href="{{ route('admin.papeis.index') }}" class="group block rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40 transition hover:border-brand/40 hover:shadow-md">
                <p class="font-display text-lg font-bold text-slate-900 group-hover:text-ink">Papéis</p>
                <p class="mt-2 text-sm text-slate-500">Roles Spatie e permissões.</p>
            </a>
        @endcan
    </div>
</x-layouts.admin>
