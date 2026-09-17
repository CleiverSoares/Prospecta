{{-- Painel de formulário / lista interativa --}}
@props([
    'padding' => true,
])

<div {{ $attributes->merge([
    'class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/40'.($padding ? ' p-6' : ''),
]) }}>
    {{ $slot }}
</div>
