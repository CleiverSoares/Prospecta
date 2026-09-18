@php
    $itens = [
        [
            'rotulo' => 'Painel',
            'rota' => 'admin.painel',
            'ativo' => request()->routeIs('admin.painel'),
            'permissao' => 'admin.acessar',
            'icone' => 'mapa',
        ],
        [
            'rotulo' => 'Visitas',
            'rota' => 'admin.visitas.index',
            'ativo' => request()->routeIs('admin.visitas.*'),
            'permissao' => 'visitas.ver',
            'icone' => 'visita',
        ],
        [
            'rotulo' => 'Unidades',
            'rota' => 'admin.unidades.index',
            'ativo' => request()->routeIs('admin.unidades.*'),
            'permissao' => 'unidades.ver',
            'icone' => 'territorio',
        ],
        [
            'rotulo' => 'Usuários',
            'rota' => 'admin.usuarios.index',
            'ativo' => request()->routeIs('admin.usuarios.*'),
            'permissao' => 'usuarios.ver',
            'icone' => 'equipe',
        ],
        [
            'rotulo' => 'Papéis',
            'rota' => 'admin.papeis.index',
            'ativo' => request()->routeIs('admin.papeis.*'),
            'permissao' => 'papeis.gerenciar',
            'icone' => 'chave',
        ],
        [
            'rotulo' => 'Integrações',
            'rota' => 'admin.integracoes',
            'ativo' => request()->routeIs('admin.integracoes'),
            'permissao' => 'admin.acessar',
            'icone' => 'plug',
        ],
    ];
@endphp

<aside class="admin-sidebar-glass hidden w-[15.5rem] shrink-0 flex-col border-r border-white/40 bg-white/70 backdrop-blur-xl lg:flex">
    <div class="admin-sidebar-brand px-5 pb-2">
        <a href="{{ route('admin.painel') }}" class="block no-underline">
            <span class="text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-brand">Prospecta</span>
            <p class="mt-1 text-lg font-semibold tracking-tight text-ink">Campo</p>
        </a>
        <p class="mt-0.5 text-xs text-ink-soft">Operação e território</p>
    </div>

    <nav class="flex flex-1 flex-col gap-1 px-2.5 py-3" aria-label="Menu lateral">
        @foreach ($itens as $item)
            @can($item['permissao'])
                <a
                    href="{{ route($item['rota']) }}"
                    data-ativo="{{ $item['ativo'] ? '1' : '0' }}"
                    @class([
                        'admin-nav-link rounded-2xl px-3.5 py-2.5 text-sm',
                        'text-ink-soft hover:bg-white/80 hover:text-ink' => ! $item['ativo'],
                    ])
                >
                    <span class="grid size-7 place-items-center rounded-xl bg-brand-soft/80 text-brand">
                        @if ($item['icone'] === 'mapa')
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6.5 9 4l6 2.5L21 4v13.5L15 20l-6-2.5L3 20Z"/><path d="M9 4v13.5M15 6.5V20"/></svg>
                        @elseif ($item['icone'] === 'visita')
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        @elseif ($item['icone'] === 'territorio')
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        @elseif ($item['icone'] === 'equipe')
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="3"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        @elseif ($item['icone'] === 'chave')
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="15" r="4"/><path d="m18 8-6.5 6.5M15 5l4 4"/></svg>
                        @else
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8"/></svg>
                        @endif
                    </span>
                    <span>{{ $item['rotulo'] }}</span>
                </a>
            @endcan
        @endforeach
    </nav>

    <div class="admin-user-card mx-3 mb-4 rounded-2xl bg-white/80 p-4 shadow-sm">
        <p class="truncate text-sm font-medium text-ink">{{ auth()->user()?->name }}</p>
        <p class="mt-0.5 truncate text-xs text-ink-faint">{{ auth()->user()?->email }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm font-medium text-brand hover:text-brand-strong">
                Sair
            </button>
        </form>
    </div>
</aside>
