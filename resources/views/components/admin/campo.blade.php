@props([
    'rotulo',
    'nome',
    'tipo' => 'text',
    'valor' => '',
    'obrigatorio' => false,
    'placeholder' => null,
])

@php
    $classeCampo = 'h-11 w-full rounded-xl border border-brand/20 bg-ink/60 px-3 text-sm text-paper outline-none transition placeholder:text-paper/30 focus:border-brand focus:ring-2 focus:ring-brand/20';
@endphp

<div class="space-y-2">
    <label for="{{ $nome }}" class="block text-sm font-semibold text-paper/70">
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
        <p class="text-sm text-rose-400">{{ $message }}</p>
    @enderror
</div>
