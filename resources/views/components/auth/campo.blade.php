@props([
    'rotulo',
    'nome',
    'tipo' => 'text',
    'valor' => '',
    'placeholder' => '',
    'obrigatorio' => false,
    'autocomplete' => null,
    'autofocus' => false,
])

@php
    $erros = $errors->get($nome);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    <div class="flex items-baseline justify-between gap-3">
        <label for="{{ $nome }}" class="text-sm font-medium text-ink">
            {{ $rotulo }}
        </label>
        {{ $acao ?? '' }}
    </div>

    <input
        id="{{ $nome }}"
        name="{{ $nome }}"
        type="{{ $tipo }}"
        value="{{ $valor }}"
        placeholder="{{ $placeholder }}"
        @if ($obrigatorio) required @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($autofocus) autofocus @endif
        class="h-10 w-full rounded-md border border-surface-line bg-white px-3 text-sm text-ink outline-none transition placeholder:text-ink-faint focus:border-brand focus:ring-2 focus:ring-brand/20"
    >

    @if ($erros)
        <ul class="space-y-1 text-sm text-rose-600">
            @foreach ((array) $erros as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
    @endif
</div>
