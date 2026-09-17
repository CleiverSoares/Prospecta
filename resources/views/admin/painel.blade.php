@php
    $mapaConfig = [
        'token' => $mapboxToken,
        'styleUrl' => $mapboxStyle,
        'unidades' => $unidadesMapa,
        'prospectos' => $prospectosMapa,
        'visitas' => $visitasMapa,
    ];
@endphp

<x-layouts.admin titulo="Painel" :mapa-full="true">
    <x-slot:subtitulo>Território ao vivo — camadas e operação do dia</x-slot:subtitulo>

    <div
        class="admin-mapa-stage"
        x-data="mapaPainel(@js($mapaConfig))"
    >
        <div x-ref="mapa" class="admin-mapa-stage__map"></div>

        <div class="pointer-events-none absolute inset-x-0 top-0 z-10 flex flex-wrap items-start justify-between gap-3 p-3 sm:p-4">
            <div class="pointer-events-auto flex flex-wrap gap-2">
                <div class="admin-chip">
                    <span class="admin-chip__label">Unidades</span>
                    <span class="admin-chip__value">{{ $metricas['unidades'] }}</span>
                </div>
                <div class="admin-chip">
                    <span class="admin-chip__label">Visitas hoje</span>
                    <span class="admin-chip__value">{{ $metricas['visitas_hoje'] }}</span>
                </div>
                <div class="admin-chip">
                    <span class="admin-chip__label">Conversão</span>
                    <span class="admin-chip__value">{{ $metricas['conversao'] }}%</span>
                </div>
                <div class="admin-chip">
                    <span class="admin-chip__label">Km est.</span>
                    <span class="admin-chip__value">{{ $metricas['km_estimado'] }}</span>
                </div>
                <div class="admin-chip">
                    <span class="admin-chip__label">Upsells</span>
                    <span class="admin-chip__value">{{ $metricas['upsells'] }}</span>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.painel') }}" class="pointer-events-auto flex max-w-full flex-wrap gap-2 rounded-2xl border border-white/50 bg-white/85 p-2 shadow-lg backdrop-blur-md">
                <select name="unidade_id" class="h-9 rounded-xl border-0 bg-transparent text-xs text-ink focus:ring-0">
                    <option value="">Unidade</option>
                    @foreach ($opcoes['unidades'] as $u)
                        <option value="{{ $u->id }}" @selected(($filtros['unidade_id'] ?? null) == $u->id)>{{ $u->nome }}</option>
                    @endforeach
                </select>
                <select name="gestor_id" class="h-9 rounded-xl border-0 bg-transparent text-xs text-ink focus:ring-0">
                    <option value="">Gestor</option>
                    @foreach ($opcoes['gestores'] as $g)
                        <option value="{{ $g->id }}" @selected(($filtros['gestor_id'] ?? null) == $g->id)>{{ $g->name }}</option>
                    @endforeach
                </select>
                <select name="vendedor_id" class="h-9 rounded-xl border-0 bg-transparent text-xs text-ink focus:ring-0">
                    <option value="">Vendedor</option>
                    @foreach ($opcoes['vendedores'] as $v)
                        <option value="{{ $v->id }}" @selected(($filtros['vendedor_id'] ?? null) == $v->id)>{{ $v->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="de" value="{{ $filtros['de'] }}" class="h-9 rounded-xl border-0 bg-transparent text-xs text-ink focus:ring-0">
                <input type="date" name="ate" value="{{ $filtros['ate'] }}" class="h-9 rounded-xl border-0 bg-transparent text-xs text-ink focus:ring-0">
                <button type="submit" class="h-9 rounded-xl bg-brand px-3 text-xs font-semibold text-white">Filtrar</button>
            </form>
        </div>

        <div class="pointer-events-none absolute bottom-4 left-4 z-10 flex flex-wrap gap-2">
            <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.unidades ? '1' : '0'" @click="toggleCamada('unidades')">Unidades</button>
            <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.prospectos ? '1' : '0'" @click="toggleCamada('prospectos')">Prospectos</button>
            <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.visitas ? '1' : '0'" @click="toggleCamada('visitas')">Visitas</button>
            <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.calor ? '1' : '0'" @click="toggleCamada('calor')">Calor</button>
        </div>

        <aside class="pointer-events-auto absolute bottom-4 right-4 z-10 w-[min(100%,16rem)] rounded-2xl border border-white/50 bg-white/90 p-3 shadow-lg backdrop-blur-md">
            <p class="text-xs font-semibold text-ink">Placar do dia</p>
            <ul class="mt-2 space-y-1.5">
                @forelse ($placar as $i => $linha)
                    <li class="flex items-center justify-between text-sm">
                        <span class="truncate text-ink-soft"><span class="mr-1 font-semibold text-brand">{{ $i + 1 }}.</span>{{ $linha['nome'] }}</span>
                        <span class="tabular-nums font-semibold text-ink">{{ $linha['checkins'] }}</span>
                    </li>
                @empty
                    <li class="text-xs text-ink-faint">Sem check-ins hoje.</li>
                @endforelse
            </ul>
            <div class="mt-3 flex flex-wrap gap-2 border-t border-surface-line/70 pt-3 text-xs">
                @can('unidades.ver')
                    <a href="{{ route('admin.unidades.index') }}" class="font-medium text-brand">Unidades</a>
                @endcan
                @can('usuarios.ver')
                    <a href="{{ route('admin.usuarios.index') }}" class="font-medium text-brand">Equipe</a>
                @endcan
            </div>
        </aside>

        <p
            class="absolute left-1/2 top-1/2 z-10 max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm text-amber-900 shadow"
            x-show="erro"
            x-text="erro"
            x-cloak
        ></p>
    </div>
</x-layouts.admin>
