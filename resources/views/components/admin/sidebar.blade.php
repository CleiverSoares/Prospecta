@php
    $itens = [
        [
            'rotulo' => 'Painel',
            'rota' => 'admin.painel',
            'ativo' => request()->routeIs('admin.painel'),
            'permissao' => 'admin.acessar',
        ],
        [
            'rotulo' => 'Unidades',
            'rota' => 'admin.unidades.index',
            'ativo' => request()->routeIs('admin.unidades.*'),
            'permissao' => 'unidades.ver',
        ],
        [
            'rotulo' => 'Usuários',
            'rota' => 'admin.usuarios.index',
            'ativo' => request()->routeIs('admin.usuarios.*'),
            'permissao' => 'usuarios.ver',
        ],
        [
            'rotulo' => 'Papéis',
            'rota' => 'admin.papeis.index',
            'ativo' => request()->routeIs('admin.papeis.*'),
            'permissao' => 'papeis.gerenciar',
        ],
        [
            'rotulo' => 'Integrações',
            'rota' => 'admin.integracoes',
            'ativo' => request()->routeIs('admin.integracoes'),
            'permissao' => 'admin.acessar',
        ],
    ];
@endphp

<aside class="hidden w-64 shrink-0 flex-col border-r border-surface-line bg-white lg:flex">
    <div class="admin-sidebar-brand border-b border-brand-strong/30 px-5 py-4">
        <a href="{{ route('admin.painel') }}" class="text-lg font-semibold tracking-tight text-white no-underline">
            Prospecta
        </a>
        <p class="mt-0.5 text-xs text-white/80">Administração</p>
    </div>

    <nav class="flex flex-1 flex-col gap-0.5 p-2" aria-label="Menu lateral">
        @foreach ($itens as $item)
            @can($item['permissao'])
                <a
                    href="{{ route($item['rota']) }}"
                    data-ativo="{{ $item['ativo'] ? '1' : '0' }}"
                    @class([
                        'admin-nav-link rounded-md px-3 py-2 text-sm',
                        'text-ink-soft hover:bg-surface-muted hover:text-ink' => ! $item['ativo'],
                    ])
                >
                    {{ $item['rotulo'] }}
                </a>
            @endcan
        @endforeach
    </nav>

    <div class="border-t border-surface-line p-4">
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
