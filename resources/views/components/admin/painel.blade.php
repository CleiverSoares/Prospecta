{{-- Painel branco enterprise --}}
@props([
    'padding' => true,
])

<div {{ $attributes->merge([
    'class' => 'admin-panel overflow-hidden rounded-xl'.($padding ? ' p-4 sm:p-5' : ''),
]) }}>
    {{ $slot }}
</div>
