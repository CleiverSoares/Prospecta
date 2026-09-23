<x-layouts.admin titulo="Demo — mapa ao vivo">
    <x-slot:subtitulo>Regenera GPS e clientes mock sem artisan no Render</x-slot:subtitulo>

    <div class="mx-auto max-w-xl space-y-5">
        @if (session('sucesso'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                {{ session('sucesso') }}
            </div>
        @endif
        @if (session('erro'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900" role="alert">
                {{ session('erro') }}
            </div>
        @endif

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Regenerar campo</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                Roda o seed de massa de campo: visitas DEMO do dia + trajetos GPS com último ping recente.
                Use quando os pins <strong class="font-medium text-ink">ao vivo</strong> sumirem do Painel
                (janela de ~{{ config('prospecta.tracking.janela_minutos', 15) }}&nbsp;min).
            </p>

            <form method="POST" action="{{ route('admin.demo.regenerar') }}" class="mt-5 flex flex-wrap items-center gap-3">
                @csrf
                <x-admin.botao tipo="submit">
                    Regenerar mapa ao vivo
                </x-admin.botao>
                <x-admin.botao href="{{ route('admin.painel') }}" variante="secundario">
                    Abrir Painel
                </x-admin.botao>
            </form>
        </section>

        <section class="rounded-2xl border border-white/55 bg-white/90 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-ink">Clientes mock</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                Gera pins <strong class="font-medium text-ink">azuis</strong> de cliente espalhados pelo Brasil (SP, RJ, BH, Sul, Nordeste, Centro-Oeste, Norte)
                para simular carteira no Painel. Não apaga leads vermelhos.
            </p>

            <form method="POST" action="{{ route('admin.demo.clientes-mock') }}" class="mt-5 flex flex-wrap items-center gap-3">
                @csrf
                <x-admin.botao tipo="submit">
                    Gerar clientes mock
                </x-admin.botao>
                <x-admin.botao href="{{ route('admin.painel') }}" variante="secundario">
                    Ver no mapa
                </x-admin.botao>
            </form>
        </section>

        <p class="text-xs text-ink-faint">
            Disponível só com <code class="rounded bg-surface-muted px-1">DEMO_SEED_ENABLED=true</code>.
            Logins demo: adm / gestor / vendedor @prospecta.test — senha <code class="rounded bg-surface-muted px-1">password</code>.
        </p>
    </div>
</x-layouts.admin>
