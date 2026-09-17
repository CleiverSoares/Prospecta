@props([
    'tipo' => 'submit',
])

<button
    type="{{ $tipo }}"
    {{ $attributes->merge([
        'class' => 'inline-flex h-10 w-full items-center justify-center rounded-md bg-brand text-sm font-semibold text-white transition hover:bg-brand-strong focus:outline-none focus:ring-2 focus:ring-brand/30 focus:ring-offset-2',
    ]) }}
>
    {{ $slot }}
</button>
