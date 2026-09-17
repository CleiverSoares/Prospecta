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
            'rotulo' => 'Papéis',
            'rota' => 'admin.papeis.index',
            'ativo' => request()->routeIs('admin.papeis.*'),
            'permissao' => 'papeis.gerenciar',
        ],
    ];
@endphp

<aside class="relative z-20 hidden w-[17.5rem] shrink-0 flex-col border-r border-brand/10 bg-ink/80 backdrop-blur-md lg:flex">
    <div class="px-6 py-7">
        <a href="{{ route('admin.painel') }}" class="font-display text-3xl font-extrabold tracking-tight text-paper no-underline">
            Prospecta
        </a>
        <p class="mt-2 text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-brand">
            Campo · território · rota
        </p>
    </div>

    <nav class="flex flex-1 flex-col gap-1 px-3" aria-label="Menu lateral">
        @foreach ($itens as $item)
            @can($item['permissao'])
                <a
                    href="{{ route($item['rota']) }}"
                    data-ativo="{{ $item['ativo'] ? '1' : '0' }}"
                    @class([
                        'admin-nav-link rounded-xl px-3 py-2.5 text-sm font-semibold',
                        'text-paper/55 hover:bg-white/5 hover:text-paper' => ! $item['ativo'],
                    ])
                >
                    {{ $item['rotulo'] }}
                </a>
            @endcan
        @endforeach
    </nav>

    <div class="border-t border-brand/10 p-5">
        <p class="truncate text-sm font-medium text-paper/80">{{ auth()->user()?->name }}</p>
        <p class="mt-0.5 truncate text-xs text-paper/40">{{ auth()->user()?->email }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm font-semibold text-brand transition hover:text-brand-strong">
                Sair
            </button>
        </form>
    </div>
</aside>
