@php
    /** @var \App\Models\Unidade|null $unidade */
    $unidade = $unidade ?? null;
@endphp

<x-admin.campo
    rotulo="Nome"
    nome="nome"
    :valor="old('nome', $unidade?->nome)"
    obrigatorio
/>

<x-admin.campo
    rotulo="Tipo"
    nome="tipo"
    tipo="select"
    obrigatorio
>
    @foreach ($tipos as $tipo)
        <option value="{{ $tipo->value }}" @selected(old('tipo', $unidade?->tipo?->value) === $tipo->value)>
            {{ $tipo->value }}
        </option>
    @endforeach
</x-admin.campo>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-admin.campo
        rotulo="CEP início"
        nome="cep_inicio"
        :valor="old('cep_inicio', $unidade?->cep_inicio)"
        placeholder="00000-000"
        inputmode="numeric"
        autocomplete="postal-code"
    />

    <x-admin.campo
        rotulo="CEP fim"
        nome="cep_fim"
        :valor="old('cep_fim', $unidade?->cep_fim)"
        placeholder="00000-000"
        inputmode="numeric"
        autocomplete="postal-code"
    />
</div>
