@props([
    'titulo',
    'subtitulo' => null,
])

<header class="relative z-10 border-b border-brand/10 bg-ink/40 backdrop-blur-md">
    <div class="flex flex-wrap items-end justify-between gap-4 px-4 py-5 sm:px-8 lg:px-10">
        <div class="min-w-0">
            <p class="font-display text-xl font-extrabold tracking-tight text-paper lg:hidden">Prospecta</p>
            <h1 class="mt-1 truncate font-display text-3xl font-extrabold tracking-tight text-paper sm:text-4xl">
                {{ $titulo }}
            </h1>
            @if ($subtitulo)
                <p class="mt-2 max-w-xl text-sm text-paper/50">{{ $subtitulo }}</p>
            @endif
        </div>

        @isset($acoes)
            <div class="flex flex-wrap items-center gap-2 pb-1">
                {{ $acoes }}
            </div>
        @endisset
    </div>

    <nav class="flex gap-1 overflow-x-auto border-t border-brand/10 px-2 py-2 lg:hidden" aria-label="Navegação admin">
        @can('admin.acessar')
            <a
                href="{{ route('admin.painel') }}"
                data-ativo="{{ request()->routeIs('admin.painel') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold',
                    'text-paper/60' => ! request()->routeIs('admin.painel'),
                ])
            >Painel</a>
        @endcan
        @can('unidades.ver')
            <a
                href="{{ route('admin.unidades.index') }}"
                data-ativo="{{ request()->routeIs('admin.unidades.*') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold',
                    'text-paper/60' => ! request()->routeIs('admin.unidades.*'),
                ])
            >Unidades</a>
        @endcan
        @can('papeis.gerenciar')
            <a
                href="{{ route('admin.papeis.index') }}"
                data-ativo="{{ request()->routeIs('admin.papeis.*') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold',
                    'text-paper/60' => ! request()->routeIs('admin.papeis.*'),
                ])
            >Papéis</a>
        @endcan
    </nav>
</header>
