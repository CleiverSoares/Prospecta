@props([
    'tagline' => 'Prospecção de campo com território sob controle.',
])

<div {{ $attributes->merge(['class' => 'max-w-lg']) }}>
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand">Prospecta</p>
    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-ink sm:text-4xl">
        Operação de campo com clareza de território.
    </h1>
    <p class="mt-3 text-base leading-relaxed text-ink-soft">
        {{ $tagline }}
    </p>

    <ul class="mt-8 space-y-3 text-sm text-ink-soft">
        <li class="flex gap-2.5">
            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand"></span>
            <span>Unidades e faixas de CEP com leitura visual no mapa.</span>
        </li>
        <li class="flex gap-2.5">
            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand"></span>
            <span>Papéis e permissões alinhados à equipe comercial.</span>
        </li>
        <li class="flex gap-2.5">
            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand"></span>
            <span>Admin denso, claro e pronto para o dia a dia.</span>
        </li>
    </ul>
</div>
