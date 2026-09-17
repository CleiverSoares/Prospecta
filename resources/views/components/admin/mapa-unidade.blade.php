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
    class="space-y-3"
    x-data="mapaUnidade(@js($config))"
>
    <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
            <p class="text-sm font-semibold text-paper/70">Território no mapa</p>
            <p class="text-xs text-paper/40">Opcional — desenhe para estimar CEPs. A regra de negócio usa o intervalo.</p>
        </div>
        <p class="text-xs text-brand/80" x-text="status" x-show="status" x-cloak></p>
    </div>

    <p
        class="rounded-xl border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"
        x-show="erro"
        x-text="erro"
        x-cloak
    ></p>

    <div
        x-ref="mapa"
        class="h-72 w-full overflow-hidden rounded-2xl border border-brand/20 bg-ink/80 sm:h-96"
    ></div>

    <input
        type="hidden"
        name="poligono_geojson"
        x-ref="poligono"
        value="{{ old('poligono_geojson') ? (is_string(old('poligono_geojson')) ? old('poligono_geojson') : json_encode(old('poligono_geojson'))) : ($poligono ? json_encode($poligono) : '') }}"
    >
</div>
