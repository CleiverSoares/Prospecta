@php
    $mapaConfig = [
        'token' => $mapboxToken,
        'styleUrl' => $mapboxStyle,
        'unidades' => $unidadesMapa,
        'prospectos' => $prospectosMapa,
        'visitas' => $visitasMapa,
        'aoVivo' => $aoVivo ?? [],
        'aoVivoUrl' => route('admin.localizacoes.ao-vivo'),
        'trajetoUrlBase' => url('/admin/localizacoes'),
        'rascunhoUrl' => route('admin.unidades.rascunho-poligono'),
        'csrf' => csrf_token(),
        'podeCriarUnidade' => auth()->user()?->can('unidades.criar') ?? false,
        'filtros' => $filtros,
        'janelaMinutos' => (int) config('prospecta.tracking.janela_minutos', 15),
    ];
@endphp

<x-layouts.admin titulo="Painel" :mapa-full="true">
    <x-slot:subtitulo>Território ao vivo — camadas e operação do dia</x-slot:subtitulo>

    <div
        class="admin-mapa-stage"
        x-data="mapaPainel(@js($mapaConfig))"
    >
        <div x-ref="mapa" class="admin-mapa-stage__map" aria-label="Mapa de território"></div>

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

            <form method="GET" action="{{ route('admin.painel') }}" class="pointer-events-auto flex max-w-full flex-wrap items-center gap-1.5 rounded-2xl border border-white/55 bg-white/90 p-1.5 shadow-lg backdrop-blur-md">
                @if ($escopoGestor ?? false)
                    <input type="hidden" name="gestor_id" value="{{ $filtros['gestor_id'] }}">
                    @if ($filtros['unidade_id'] ?? null)
                        <input type="hidden" name="unidade_id" value="{{ $filtros['unidade_id'] }}">
                    @endif
                    <span class="h-9 max-w-[11rem] truncate rounded-xl bg-surface-muted/80 px-2.5 py-2 text-xs font-medium text-ink" title="Seu escopo">
                        {{ auth()->user()?->name }}
                    </span>
                @else
                    <select name="unidade_id" class="h-9 max-w-[9.5rem] rounded-xl border-0 bg-transparent pl-2 pr-8 text-xs text-ink focus:ring-0">
                        <option value="">Todas unidades</option>
                        @foreach ($opcoes['unidades'] as $u)
                            <option value="{{ $u->id }}" @selected(($filtros['unidade_id'] ?? null) == $u->id)>{{ $u->nome }}</option>
                        @endforeach
                    </select>
                    <select name="gestor_id" class="h-9 max-w-[9rem] rounded-xl border-0 bg-transparent pl-2 pr-8 text-xs text-ink focus:ring-0">
                        <option value="">Todos gestores</option>
                        @foreach ($opcoes['gestores'] as $g)
                            <option value="{{ $g->id }}" @selected(($filtros['gestor_id'] ?? null) == $g->id)>{{ $g->name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="vendedor_id" class="h-9 max-w-[9.5rem] rounded-xl border-0 bg-transparent pl-2 pr-8 text-xs text-ink focus:ring-0">
                    <option value="">Todos vendedores</option>
                    @foreach ($opcoes['vendedores'] as $v)
                        <option value="{{ $v->id }}" @selected(($filtros['vendedor_id'] ?? null) == $v->id)>{{ $v->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="de" value="{{ $filtros['de'] }}" class="h-9 rounded-xl border-0 bg-transparent px-2 text-xs text-ink focus:ring-0">
                <input type="date" name="ate" value="{{ $filtros['ate'] }}" class="h-9 rounded-xl border-0 bg-transparent px-2 text-xs text-ink focus:ring-0">
                <button type="submit" class="h-9 rounded-xl bg-brand px-3.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-strong">Filtrar</button>
            </form>
        </div>

        <div class="pointer-events-none absolute bottom-4 left-4 z-10 flex max-w-[min(100%,28rem)] flex-col gap-2">
            <div class="admin-mapa-legend pointer-events-none">
                <span><i class="admin-mapa-legend__dot" style="background:#e11d48"></i>Lead</span>
                <span><i class="admin-mapa-legend__dot" style="background:#0083C1"></i>Cliente</span>
                <span><i class="admin-mapa-legend__dot" style="background:#16a34a"></i>Visita feita</span>
                <span><i class="admin-mapa-legend__dot" style="background:#f59e0b"></i>Retorno</span>
                <span><i class="admin-mapa-legend__dot" style="background:#0284c7"></i>Trajeto</span>
                <span class="w-full basis-full text-[0.65rem] text-ink-faint">Ao vivo (GPS do vendedor):</span>
                <span><i class="admin-mapa-legend__dot" style="background:#22c55e"></i>Ok</span>
                <span><i class="admin-mapa-legend__dot" style="background:#f59e0b"></i>Sem sinal</span>
                <span><i class="admin-mapa-legend__dot" style="background:#a855f7"></i>Parado</span>
                <span><i class="admin-mapa-legend__dot" style="background:#e11d48"></i>Fora do território</span>
            </div>
            @if (!empty($filtros['vendedor_id']))
                <p class="pointer-events-none rounded-xl border border-white/55 bg-white/90 px-3 py-2 text-[0.7rem] font-medium text-ink-soft shadow">
                    Mapa filtrado: pins = check-ins · linha = caminho GPS (não a meta 8). Se a linha for curta, o seed/GPS tinha poucos pontos.
                </p>
            @endif
            <div class="flex flex-wrap gap-2">
                <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.unidades ? '1' : '0'" @click="toggleCamada('unidades')">Unidades</button>
                <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.leads ? '1' : '0'" @click="toggleCamada('leads')">Leads</button>
                <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.clientes ? '1' : '0'" @click="toggleCamada('clientes')">Clientes</button>
                <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.visitas ? '1' : '0'" @click="toggleCamada('visitas')">Visitas</button>
                <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.calor ? '1' : '0'" @click="toggleCamada('calor')">Calor</button>
                <button type="button" class="admin-layer-toggle pointer-events-auto" :data-on="camadas.aoVivo ? '1' : '0'" @click="toggleCamada('aoVivo')">Ao vivo</button>
                @can('unidades.criar')
                    <button
                        type="button"
                        class="admin-layer-toggle pointer-events-auto"
                        :data-on="selecionando || temSelecao ? '1' : '0'"
                        @click="iniciarSelecaoArea()"
                    >Selecionar área</button>
                @endcan
            </div>
        </div>

        <aside
            class="pointer-events-auto absolute bottom-4 right-4 z-10 w-[min(100%,17rem)] rounded-2xl border border-white/55 bg-white/92 p-3.5 shadow-lg backdrop-blur-md"
            x-show="temSelecao"
            x-cloak
        >
            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-ink-faint">Leads na área</p>
            <p class="mt-1.5 text-2xl font-semibold tabular-nums text-ink">
                <span x-text="resumo.total"></span>
            </p>
            <p class="text-xs text-ink-soft">
                <span x-text="resumo.leads"></span> novos ·
                <span x-text="resumo.clientes"></span> clientes
            </p>
            <p class="mt-2 text-[0.7rem] text-ink-faint">Polígono livre — não precisa ser quadrado</p>
            <div class="mt-3 flex flex-col gap-2">
                <button
                    type="button"
                    class="h-9 rounded-xl bg-brand text-xs font-semibold text-white hover:bg-brand-strong disabled:opacity-60"
                    @click="criarUnidadeDaArea()"
                    :disabled="enviandoUnidade"
                    x-text="enviandoUnidade ? 'Abrindo…' : 'Criar unidade desta área'"
                ></button>
                <button type="button" class="text-xs font-medium text-ink-soft hover:text-ink" @click="limparSelecao()">Limpar seleção</button>
            </div>
        </aside>

        <aside
            class="pointer-events-auto absolute bottom-4 right-4 z-10 w-[min(100%,16.5rem)] rounded-2xl border border-white/55 bg-white/92 p-3.5 shadow-lg backdrop-blur-md"
            x-show="!temSelecao"
            x-cloak
        >
            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-ink-faint">Placar · feitas / meta {{ (int) config('prospecta.meta_visitas_dia', 8) }}</p>
            <ul class="mt-2.5 space-y-1.5">
                @forelse ($placar as $i => $linha)
                    <li class="flex items-center justify-between text-sm">
                        <span class="truncate text-ink-soft"><span class="mr-1 font-semibold text-brand">{{ $i + 1 }}.</span>{{ $linha['nome'] }}</span>
                        <span class="tabular-nums font-semibold text-ink">{{ $linha['checkins'] }} feitas<span class="font-normal text-ink-faint"> / {{ $linha['meta'] ?? config('prospecta.meta_visitas_dia', 8) }}</span></span>
                    </li>
                @empty
                    <li class="text-xs leading-relaxed text-ink-faint">Sem check-ins hoje — abra o app do vendedor e faça uma visita.</li>
                @endforelse
            </ul>
            <div class="mt-3 flex flex-wrap gap-3 border-t border-surface-line/70 pt-3 text-xs">
                @can('visitas.ver')
                    <a href="{{ route('admin.agenda') }}" class="font-semibold text-brand hover:text-brand-strong">Agenda</a>
                    <a href="{{ route('admin.visitas.index') }}" class="font-semibold text-brand hover:text-brand-strong">Visitas</a>
                @endcan
                @can('unidades.ver')
                    <a href="{{ route('admin.unidades.index') }}" class="font-semibold text-brand hover:text-brand-strong">Unidades</a>
                @endcan
                @can('usuarios.ver')
                    <a href="{{ route('admin.usuarios.index') }}" class="font-semibold text-brand hover:text-brand-strong">Equipe</a>
                @endcan
            </div>
        </aside>

        <div
            class="absolute inset-0 z-[5] grid place-items-center bg-[#d7e3ec]/70 backdrop-blur-[2px]"
            x-show="carregando"
            x-cloak
        >
            <p class="rounded-2xl bg-white/90 px-4 py-2 text-sm font-medium text-ink-soft shadow">Carregando mapa…</p>
        </div>

        <p
            class="absolute left-1/2 top-1/2 z-10 max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm text-amber-900 shadow"
            x-show="erro && !carregando"
            x-text="erro"
            x-cloak
        ></p>
    </div>
</x-layouts.admin>
