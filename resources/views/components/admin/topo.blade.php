@props([
    'titulo',
    'subtitulo' => null,
])

<header class="border-b border-slate-200/80 bg-white/90 backdrop-blur-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-8">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400 lg:hidden">Prospecta</p>
            <h1 class="truncate font-display text-2xl font-bold tracking-tight text-slate-900">
                {{ $titulo }}
            </h1>
            @if ($subtitulo)
                <p class="mt-1 text-sm text-slate-500">{{ $subtitulo }}</p>
            @endif
        </div>

        @isset($acoes)
            <div class="flex flex-wrap items-center gap-2">
                {{ $acoes }}
            </div>
        @endisset
    </div>

    <nav class="flex gap-1 overflow-x-auto border-t border-slate-100 px-2 py-2 lg:hidden" aria-label="Navegação admin">
        @can('admin.acessar')
            <a
                href="{{ route('admin.painel') }}"
                @class([
                    'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium',
                    'bg-brand/15 text-slate-900' => request()->routeIs('admin.painel'),
                    'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('admin.painel'),
                ])
            >Painel</a>
        @endcan
        @can('unidades.ver')
            <a
                href="{{ route('admin.unidades.index') }}"
                @class([
                    'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium',
                    'bg-brand/15 text-slate-900' => request()->routeIs('admin.unidades.*'),
                    'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('admin.unidades.*'),
                ])
            >Unidades</a>
        @endcan
        @can('papeis.gerenciar')
            <a
                href="{{ route('admin.papeis.index') }}"
                @class([
                    'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium',
                    'bg-brand/15 text-slate-900' => request()->routeIs('admin.papeis.*'),
                    'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('admin.papeis.*'),
                ])
            >Papéis</a>
        @endcan
    </nav>
</header>
