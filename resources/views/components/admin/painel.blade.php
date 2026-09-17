{{-- Container de conteúdo admin --}}
@props([
    'padding' => true,
])

<div {{ $attributes->merge([
    'class' => 'admin-panel overflow-hidden rounded-2xl'.($padding ? ' p-6 sm:p-8' : ''),
]) }}>
    {{ $slot }}
</div>
