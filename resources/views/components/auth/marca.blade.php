@props([
    'tagline' => 'Prospecção de campo com território sob controle.',
])

<div {{ $attributes->merge(['class' => 'max-w-xl']) }}>
    <a href="{{ url('/') }}" class="font-display text-5xl font-extrabold tracking-tight text-paper no-underline sm:text-6xl lg:text-7xl">
        Prospecta
    </a>
    <p class="mt-4 max-w-[18ch] text-lg leading-snug text-paper/70 sm:text-xl">
        {{ $tagline }}
    </p>
</div>
