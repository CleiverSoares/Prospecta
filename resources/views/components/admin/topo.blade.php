@props([
    'titulo',
    'subtitulo' => null,
    'translucido' => false,
    'compacto' => false,
])

<header @class([
    'sticky top-0 z-20 shrink-0',
    'border-b border-white/60 bg-white/80 backdrop-blur-xl' => $translucido,
    'border-b border-surface-line/70 bg-white/90 backdrop-blur-md' => ! $translucido,
])>
    <div @class([
        'flex flex-wrap items-center justify-between gap-2 px-4 sm:px-6 lg:px-8',
        'py-2.5' => $compacto,
        'py-3.5' => ! $compacto,
    ])>
        <div class="min-w-0">
            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-brand lg:hidden">Prospecta</p>
            <h1 @class([
                'truncate font-semibold tracking-tight text-ink',
                'text-lg' => $compacto,
                'text-[1.35rem]' => ! $compacto,
            ])>
                {{ $titulo }}
            </h1>
            @if ($subtitulo)
                <p @class(['text-ink-soft', 'text-xs' => $compacto, 'mt-0.5 text-sm' => ! $compacto])>{{ $subtitulo }}</p>
            @endif
        </div>

        @isset($acoes)
            <div class="flex flex-wrap items-center gap-2">
                {{ $acoes }}
            </div>
        @endisset
    </div>

    @unless ($compacto)
    <nav class="flex gap-1 overflow-x-auto px-2 pb-2.5 lg:hidden" aria-label="Navegação admin">
        @can('admin.acessar')
            <a
                href="{{ route('admin.painel') }}"
                data-ativo="{{ request()->routeIs('admin.painel') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-full px-3 py-1.5 text-sm',
                    'text-ink-soft' => ! request()->routeIs('admin.painel'),
                ])
            >Painel</a>
        @endcan
        @can('unidades.ver')
            <a
                href="{{ route('admin.unidades.index') }}"
                data-ativo="{{ request()->routeIs('admin.unidades.*') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-full px-3 py-1.5 text-sm',
                    'text-ink-soft' => ! request()->routeIs('admin.unidades.*'),
                ])
            >Unidades</a>
        @endcan
        @can('visitas.ver')
            <a
                href="{{ route('admin.agenda') }}"
                data-ativo="{{ request()->routeIs('admin.agenda') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-full px-3 py-1.5 text-sm',
                    'text-ink-soft' => ! request()->routeIs('admin.agenda'),
                ])
            >Agenda</a>
        @endcan
        @can('usuarios.ver')
            <a
                href="{{ route('admin.usuarios.index') }}"
                data-ativo="{{ request()->routeIs('admin.usuarios.*') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-full px-3 py-1.5 text-sm',
                    'text-ink-soft' => ! request()->routeIs('admin.usuarios.*'),
                ])
            >Usuários</a>
            <a
                href="{{ route('admin.usuarios.index', ['papel' => 'vendedor']) }}"
                class="admin-nav-link whitespace-nowrap rounded-full px-3 py-1.5 text-sm text-ink-soft"
            >Vendedores</a>
        @endcan
        @can('papeis.gerenciar')
            <a
                href="{{ route('admin.papeis.index') }}"
                data-ativo="{{ request()->routeIs('admin.papeis.*') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-full px-3 py-1.5 text-sm',
                    'text-ink-soft' => ! request()->routeIs('admin.papeis.*'),
                ])
            >Papéis</a>
        @endcan
        @can('admin.acessar')
            <a
                href="{{ route('admin.documentacao') }}"
                data-ativo="{{ request()->routeIs('admin.documentacao') ? '1' : '0' }}"
                @class([
                    'admin-nav-link whitespace-nowrap rounded-full px-3 py-1.5 text-sm',
                    'text-ink-soft' => ! request()->routeIs('admin.documentacao'),
                ])
            >Docs</a>
            @if (config('prospecta.demo_seed_enabled'))
                <a
                    href="{{ route('admin.demo.index') }}"
                    data-ativo="{{ request()->routeIs('admin.demo.*') ? '1' : '0' }}"
                    @class([
                        'admin-nav-link whitespace-nowrap rounded-full px-3 py-1.5 text-sm',
                        'text-ink-soft' => ! request()->routeIs('admin.demo.*'),
                    ])
                >Demo</a>
            @endif
        @endcan
    </nav>
    @endunless
</header>
