@props([
    'href' => null,
    'tipo' => 'button',
    'variante' => 'primario',
])

@php
    $classes = match ($variante) {
        'secundario' => 'border border-brand/25 bg-transparent text-paper hover:border-brand/50 hover:bg-white/5',
        'perigo' => 'bg-rose-500/90 text-white hover:bg-rose-400',
        default => 'bg-brand text-ink shadow-lg shadow-brand/25 hover:bg-brand-strong',
    };
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => "inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-bold tracking-tight transition {$classes}"]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $tipo }}"
        {{ $attributes->merge(['class' => "inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-bold tracking-tight transition {$classes}"]) }}
    >
        {{ $slot }}
    </button>
@endif
