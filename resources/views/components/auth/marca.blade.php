@props([
    'tagline' => 'Prospecção de campo com território sob controle.',
])

<div {{ $attributes->merge(['class' => 'mb-8']) }}>
    <a href="{{ url('/') }}" class="block font-display text-[2.5rem] font-extrabold leading-none tracking-tight text-paper no-underline sm:text-5xl">
        Prospecta
    </a>
    <p class="mt-3 text-base leading-snug text-paper/65 sm:text-lg">
        {{ $tagline }}
    </p>
</div>
