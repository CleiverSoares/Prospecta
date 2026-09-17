@props([
    'tipo' => 'submit',
])

<button
    type="{{ $tipo }}"
    {{ $attributes->merge([
        'class' => 'inline-flex h-12 w-full items-center justify-center rounded-xl bg-brand text-base font-bold text-ink transition hover:bg-brand-strong focus:outline-none focus:ring-2 focus:ring-brand/40 focus:ring-offset-2 focus:ring-offset-ink',
    ]) }}
>
    {{ $slot }}
</button>
