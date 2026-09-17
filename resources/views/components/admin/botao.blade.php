@props([
    'href' => null,
    'tipo' => 'button',
    'variante' => 'primario',
])

@php
    $classes = match ($variante) {
        'secundario' => 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50',
        'perigo' => 'bg-rose-600 text-white hover:bg-rose-500',
        default => 'bg-brand text-ink hover:bg-brand-strong',
    };
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => "inline-flex h-11 items-center justify-center rounded-xl px-4 text-sm font-semibold transition {$classes}"]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $tipo }}"
        {{ $attributes->merge(['class' => "inline-flex h-11 items-center justify-center rounded-xl px-4 text-sm font-semibold transition {$classes}"]) }}
    >
        {{ $slot }}
    </button>
@endif
