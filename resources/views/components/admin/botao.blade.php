@props([
    'href' => null,
    'tipo' => 'button',
    'variante' => 'primario',
])

@php
    $classes = match ($variante) {
        'secundario' => 'border border-surface-line bg-white text-ink hover:bg-surface-muted',
        'perigo' => 'bg-rose-600 text-white hover:bg-rose-500',
        default => 'bg-brand text-white hover:bg-brand-strong',
    };
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => "inline-flex h-9 items-center justify-center rounded-md px-3.5 text-sm font-semibold transition {$classes}"]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $tipo }}"
        {{ $attributes->merge(['class' => "inline-flex h-9 items-center justify-center rounded-md px-3.5 text-sm font-semibold transition {$classes}"]) }}
    >
        {{ $slot }}
    </button>
@endif
