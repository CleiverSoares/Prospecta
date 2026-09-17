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

<aside class="hidden w-64 shrink-0 flex-col bg-ink text-paper lg:flex">
    <div class="border-b border-white/10 px-5 py-6">
        <a href="{{ route('admin.painel') }}" class="font-display text-2xl font-extrabold tracking-tight text-paper no-underline">
            Prospecta
        </a>
        <p class="mt-1 text-xs uppercase tracking-[0.14em] text-paper/45">Admin</p>
    </div>

    <nav class="flex flex-1 flex-col gap-1 p-3" aria-label="Menu lateral">
        @foreach ($itens as $item)
            @can($item['permissao'])
                <a
                    href="{{ route($item['rota']) }}"
                    @class([
                        'rounded-xl px-3 py-2.5 text-sm font-medium transition',
                        'bg-brand text-ink shadow-sm shadow-brand/20' => $item['ativo'],
                        'text-paper/70 hover:bg-white/5 hover:text-paper' => ! $item['ativo'],
                    ])
                >
                    {{ $item['rotulo'] }}
                </a>
            @endcan
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-4">
        <p class="truncate text-sm text-paper/70">{{ auth()->user()?->name }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm font-medium text-brand hover:underline">
                Sair
            </button>
        </form>
    </div>
</aside>
