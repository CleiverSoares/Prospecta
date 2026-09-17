@props([
    'poligono' => null,
])

@php
    $token = config('prospecta.mapbox.token_front') ?: config('prospecta.mapbox.token');
    $style = config('prospecta.mapbox.style_url_front') ?: config('prospecta.mapbox.style_url');
    $config = [
        'token' => $token,
        'styleUrl' => $style,
        'estimarUrl' => route('admin.unidades.estimar-ceps'),
        'csrf' => csrf_token(),
        'poligonoInicial' => $poligono,
    ];
@endphp

<div
    class="flex h-full min-h-[28rem] flex-col space-y-3"
    x-data="mapaUnidade(@js($config))"
>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Território</p>
            <p class="mt-1 text-sm font-medium text-ink">Mapa e polígono</p>
            <p class="mt-0.5 text-xs text-ink-soft">
                Não usa pins: desenhe um <strong class="font-medium text-ink">polígono</strong> na área.
                Os CEPs são estimados depois; a regra oficial continua sendo início/fim.
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

        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-white/95 to-transparent px-4 pb-4 pt-10"
            x-show="!temArea && !erro"
            x-cloak
        >
            <p class="text-sm font-medium text-ink">Como marcar</p>
            <ol class="mt-1 list-decimal space-y-0.5 pl-4 text-xs text-ink-soft">
                <li>Clique em <span class="font-medium text-ink">Desenhar área</span> (ou no ícone de polígono).</li>
                <li>Clique no mapa para os vértices da região.</li>
                <li>Dê duplo clique para fechar — a área fica sombreada e os CEPs são estimados.</li>
            </ol>
        </div>
    </div>

    <input
        type="hidden"
        name="poligono_geojson"
        x-ref="poligono"
        value="{{ old('poligono_geojson') ? (is_string(old('poligono_geojson')) ? old('poligono_geojson') : json_encode(old('poligono_geojson'))) : ($poligono ? json_encode($poligono) : '') }}"
    >
</div>
