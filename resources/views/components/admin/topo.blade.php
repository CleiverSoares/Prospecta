@props([
    'titulo',
    'subtitulo' => null,
])

<header class="border-b border-surface-line bg-white/95 backdrop-blur-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-6 lg:px-8">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-brand lg:hidden">Prospecta</p>
            <h1 class="truncate text-[1.35rem] font-semibold tracking-tight text-ink">
                {{ $titulo }}
            </h1>
            @if ($subtitulo)
                <p class="mt-0.5 text-sm text-ink-soft">{{ $subtitulo }}</p>
            @endif
        </div>

        @isset($acoes)
            <div class="flex flex-wrap items-center gap-2">
                {{ $acoes }}
            </div>
        @endisset
    </div>

    <nav class="flex gap-1 overflow-x-auto border-t border-surface-line px-2 py-1.5 lg:hidden" aria-label="Navegação admin">
        @can('admin.acessar')
            <a
                href="{{ route('admin.painel') }}"
                data-ativo="{{ request()->routeIs('admin.painel') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-md px-3 py-1.5 text-sm',
                    'text-ink-soft' => ! request()->routeIs('admin.painel'),
                ])
            >Painel</a>
        @endcan
        @can('unidades.ver')
            <a
                href="{{ route('admin.unidades.index') }}"
                data-ativo="{{ request()->routeIs('admin.unidades.*') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-md px-3 py-1.5 text-sm',
                    'text-ink-soft' => ! request()->routeIs('admin.unidades.*'),
                ])
            >Unidades</a>
        @endcan
        @can('papeis.gerenciar')
            <a
                href="{{ route('admin.papeis.index') }}"
                data-ativo="{{ request()->routeIs('admin.papeis.*') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-md px-3 py-1.5 text-sm',
                    'text-ink-soft' => ! request()->routeIs('admin.papeis.*'),
                ])
            >Papéis</a>
        @endcan
    </nav>
</header>
