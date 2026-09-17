<x-layouts.admin titulo="Painel">
    <x-slot:subtitulo>
        Operação de campo — território, unidades e permissões num só lugar.
    </x-slot:subtitulo>

    <section class="mb-10 max-w-3xl">
        <p class="font-display text-4xl font-extrabold leading-tight tracking-tight text-paper sm:text-5xl">
            Controle quem prospecta
            <span class="text-brand">onde</span>.
        </p>
        <p class="mt-4 text-base leading-relaxed text-paper/55 sm:text-lg">
            Defina unidades por faixa de CEP, gerencie papéis Spatie e prepare a rota do time na rua.
        </p>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        @can('unidades.ver')
            <a
                href="{{ route('admin.unidades.index') }}"
                class="admin-panel group block rounded-2xl p-7 transition hover:border-brand/40"
            >
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-brand">Território</p>
                <p class="mt-3 font-display text-2xl font-bold text-paper group-hover:text-brand">Unidades</p>
                <p class="mt-2 text-sm leading-relaxed text-paper/50">
                    Matriz, filial e representação — CEP início/fim e mapa quando fizer sentido.
                </p>
                <span class="mt-6 inline-flex text-sm font-bold text-brand">Abrir →</span>
            </a>
        @endcan

        @can('papeis.gerenciar')
            <a
                href="{{ route('admin.papeis.index') }}"
                class="admin-panel group block rounded-2xl p-7 transition hover:border-brand/40"
            >
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-brand">Acesso</p>
                <p class="mt-3 font-display text-2xl font-bold text-paper group-hover:text-brand">Papéis</p>
                <p class="mt-2 text-sm leading-relaxed text-paper/50">
                    Roles e permissions dinâmicas — crie perfil novo sem refatorar o core.
                </p>
                <span class="mt-6 inline-flex text-sm font-bold text-brand">Abrir →</span>
            </a>
        @endcan
    </div>
</x-layouts.admin>
