@php
    $mapaConfig = [
        'token' => $mapboxToken,
        'styleUrl' => $mapboxStyle,
        'unidades' => $unidadesMapa,
    ];
@endphp

<x-layouts.admin titulo="Painel">
    <x-slot:subtitulo>Visão geral da operação</x-slot:subtitulo>

    <div class="mb-5 rounded-lg border border-brand/20 bg-brand-soft px-4 py-3 sm:px-5">
        <p class="text-sm font-semibold text-brand-strong">Prospecta Admin</p>
        <p class="mt-0.5 text-sm text-ink-soft">Métricas do dia e território das unidades no mapa.</p>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="admin-panel rounded-lg p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Unidades</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $metricas['unidades'] }}</p>
        </div>
        <div class="admin-panel rounded-lg p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Usuários</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $metricas['usuarios'] }}</p>
        </div>
        <div class="admin-panel rounded-lg p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Prospectos</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $metricas['prospectos'] }}</p>
        </div>
        <div class="admin-panel rounded-lg p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Visitas hoje</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $metricas['visitas_hoje'] }}</p>
        </div>
    </div>

    <x-admin.painel class="mb-5">
        <div
            class="space-y-3"
            x-data="mapaPainel(@js($mapaConfig))"
        >
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Território</p>
                <p class="mt-1 text-sm font-medium text-ink">Mapa das unidades</p>
                <p class="mt-0.5 text-xs text-ink-soft">Somente leitura — polígonos salvos em cada unidade.</p>
            </div>

            <p
                class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
                x-show="erro"
                x-text="erro"
                x-cloak
            ></p>

            <div class="relative min-h-[22rem] overflow-hidden rounded-lg border border-surface-line bg-surface-muted lg:min-h-[28rem]">
                <div x-ref="mapa" class="absolute inset-0 h-full w-full"></div>
            </div>
        </div>
    </x-admin.painel>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @can('unidades.ver')
            <a href="{{ route('admin.unidades.index') }}" class="admin-panel block rounded-lg p-4 transition hover:border-brand/40 hover:shadow-panel">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Cadastros</p>
                <p class="mt-1 text-base font-semibold text-ink">Unidades</p>
                <p class="mt-1 text-sm text-ink-soft">Matriz, filial e representação por faixa de CEP.</p>
                <p class="mt-3 text-sm font-medium text-brand">Abrir →</p>
            </a>
        @endcan

        @can('usuarios.ver')
            <a href="{{ route('admin.usuarios.index') }}" class="admin-panel block rounded-lg p-4 transition hover:border-brand/40 hover:shadow-panel">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Equipe</p>
                <p class="mt-1 text-base font-semibold text-ink">Usuários</p>
                <p class="mt-1 text-sm text-ink-soft">Vincular unidade, gestor e papel Spatie.</p>
                <p class="mt-3 text-sm font-medium text-brand">Abrir →</p>
            </a>
        @endcan

        @can('papeis.gerenciar')
            <a href="{{ route('admin.papeis.index') }}" class="admin-panel block rounded-lg p-4 transition hover:border-brand/40 hover:shadow-panel">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Segurança</p>
                <p class="mt-1 text-base font-semibold text-ink">Papéis</p>
                <p class="mt-1 text-sm text-ink-soft">Roles e permissões de acesso (Spatie).</p>
                <p class="mt-3 text-sm font-medium text-brand">Abrir →</p>
            </a>
        @endcan
    </div>
</x-layouts.admin>
