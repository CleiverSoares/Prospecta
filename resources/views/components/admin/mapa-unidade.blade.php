@props([
    'poligono' => null,
    'prospectos' => [],
])

@php
    $token = config('prospecta.mapbox.token_front') ?: config('prospecta.mapbox.token');
    $style = config('prospecta.mapbox.style_url_front') ?: config('prospecta.mapbox.style_url');

    $poligonoNormalizado = $poligono;
    if (is_string($poligonoNormalizado)) {
        $poligonoNormalizado = json_decode($poligonoNormalizado, true);
    }

    $config = [
        'token' => $token,
        'styleUrl' => $style,
        'estimarUrl' => route('admin.unidades.estimar-ceps'),
        'csrf' => csrf_token(),
        'poligonoInicial' => $poligonoNormalizado,
        'prospectos' => $prospectos,
    ];
@endphp

<div
    class="mapa-unidade-canvas flex h-full min-h-[28rem] flex-col space-y-3"
    x-data="mapaUnidade(@js($config))"
>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Território</p>
            <p class="mt-1 text-sm font-medium text-ink">Pins + polígono livre</p>
            <p class="mt-0.5 text-xs text-ink-soft">
                Desenhe qualquer formato (não precisa ser círculo ou quadrado) em volta dos pins.
                O sistema conta quantos leads ficam dentro e estima a faixa de CEP.
            </p>
        </div>

        <button
            type="button"
            class="inline-flex h-9 shrink-0 items-center justify-center rounded-md bg-brand px-3.5 text-sm font-semibold text-white transition hover:bg-brand-strong"
            @click="iniciarDesenho()"
        >
            Desenhar área
        </button>
    </div>

    <p
        class="rounded-md border border-brand/20 bg-brand-soft px-3 py-2 text-sm text-brand-strong"
        x-text="status"
        x-show="status"
        x-cloak
    ></p>

    <p
        class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
        x-show="erro"
        x-text="erro"
        x-cloak
    ></p>

    <div class="relative min-h-[22rem] flex-1 overflow-hidden rounded-lg border border-surface-line bg-surface-muted lg:min-h-[32rem]">
        <div x-ref="mapa" class="absolute inset-0 h-full w-full"></div>

        <aside
            class="pointer-events-none absolute bottom-3 left-3 z-10 w-[min(100%,16rem)] rounded-xl border border-white/60 bg-white/95 p-3 shadow-lg backdrop-blur"
            x-show="temArea"
            x-cloak
        >
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.1em] text-ink-faint">Na área</p>
            <p class="mt-1 text-lg font-semibold text-ink">
                <span x-text="resumo.total"></span> lead(s)
            </p>
            <p class="mt-1 text-xs text-ink-soft">
                <span x-text="resumo.leads"></span> novos ·
                <span x-text="resumo.clientes"></span> clientes
            </p>
            <p class="mt-2 text-[0.7rem] text-ink-faint">Tipo: polígono livre</p>
        </aside>

        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-white/95 to-transparent px-4 pb-4 pt-10"
            x-show="!temArea && !erro"
            x-cloak
        >
            <p class="text-sm font-medium text-ink">Como marcar (estilo território)</p>
            <ol class="mt-1 list-decimal space-y-0.5 pl-4 text-xs text-ink-soft">
                <li>Os pins verdes/azuis são leads já no Prospecta.</li>
                <li>Clique em <span class="font-medium text-ink">Desenhar área</span> e marque vértices livres.</li>
                <li>Feche o polígono — aparece quantos leads ficaram dentro + CEP estimado.</li>
            </ol>
        </div>
    </div>

    <input
        type="hidden"
        name="poligono_geojson"
        x-ref="poligono"
        value="{{ old('poligono_geojson') ? (is_string(old('poligono_geojson')) ? old('poligono_geojson') : json_encode(old('poligono_geojson'))) : ($poligonoNormalizado ? json_encode($poligonoNormalizado) : '') }}"
    >
</div>
