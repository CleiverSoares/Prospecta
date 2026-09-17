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
            <p class="text-sm font-medium text-slate-700">Território no mapa</p>
            <p class="text-xs text-slate-500">Desenhe o polígono — os CEPs são estimados automaticamente.</p>
        </div>
        <p class="text-xs text-slate-500" x-text="status" x-show="status" x-cloak></p>
    </div>

    <p
        class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
        x-show="erro"
        x-text="erro"
        x-cloak
    ></p>

    <div
        x-ref="mapa"
        class="h-72 w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 sm:h-96"
    ></div>

    <input
        type="hidden"
        name="poligono_geojson"
        x-ref="poligono"
        value="{{ old('poligono_geojson') ? (is_string(old('poligono_geojson')) ? old('poligono_geojson') : json_encode(old('poligono_geojson'))) : ($poligono ? json_encode($poligono) : '') }}"
    >
</div>
