@props([
    'rotulo',
    'nome',
    'tipo' => 'text',
    'valor' => '',
    'obrigatorio' => false,
    'placeholder' => null,
])

@php
    $classeCampo = 'h-9 w-full rounded-md border border-surface-line bg-white px-3 text-sm text-ink outline-none transition placeholder:text-ink-faint focus:border-brand focus:ring-2 focus:ring-brand/20';
@endphp

<div class="space-y-1.5">
    <label for="{{ $nome }}" class="block text-sm font-medium text-ink">
        {{ $rotulo }}
        @if ($obrigatorio)<span class="text-brand">*</span>@endif
    </label>

    @if ($tipo === 'select')
        <select
            id="{{ $nome }}"
            name="{{ $nome }}"
            @if ($obrigatorio) required @endif
            {{ $attributes->merge(['class' => $classeCampo]) }}
        >
            {{ $slot }}
        </select>
    @else
        <input
            id="{{ $nome }}"
            name="{{ $nome }}"
            type="{{ $tipo }}"
            value="{{ $valor }}"
            @if ($obrigatorio) required @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            {{ $attributes->merge(['class' => $classeCampo]) }}
        >
    @endif

    @error($nome)
        <p class="text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
