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

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    <div class="flex items-baseline justify-between gap-3">
        <label for="{{ $nome }}" class="text-xs font-semibold uppercase tracking-[0.08em] text-paper/70">
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
        class="w-full rounded-xl border border-white/15 bg-ink/60 px-4 py-3.5 text-base text-paper outline-none transition placeholder:text-paper/35 focus:border-brand focus:bg-ink/80 focus:ring-2 focus:ring-brand/30"
    >

    @if ($erros)
        <ul class="space-y-1 text-sm text-rose-400">
            @foreach ((array) $erros as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
    @endif
</div>
