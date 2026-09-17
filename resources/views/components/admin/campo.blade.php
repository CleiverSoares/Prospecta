@props([
    'rotulo',
    'nome',
    'tipo' => 'text',
    'valor' => '',
    'obrigatorio' => false,
    'placeholder' => null,
])

@php
    $classeCampo = 'h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand focus:ring-2 focus:ring-brand/25';
@endphp

<div class="space-y-2">
    <label for="{{ $nome }}" class="block text-sm font-medium text-slate-700">
        {{ $rotulo }}
        @if ($obrigatorio)<span class="text-rose-500">*</span>@endif
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
