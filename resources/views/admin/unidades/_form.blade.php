@php
    /** @var \App\Models\Unidade|null $unidade */
    $unidade = $unidade ?? null;

    $formatarCep = static function (?string $cep): string {
        if ($cep === null || $cep === '') {
            return '';
        }

        $digitos = preg_replace('/\D+/', '', $cep) ?? '';

        if (strlen($digitos) < 8) {
            return $cep;
        }

        return substr($digitos, 0, 5).'-'.substr($digitos, 5, 3);
    };
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

<x-admin.mapa-unidade :poligono="old('poligono_geojson', $unidade?->poligono_geojson)" />

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-admin.campo
        rotulo="CEP início"
        nome="cep_inicio"
        :valor="old('cep_inicio', $formatarCep($unidade?->cep_inicio))"
        placeholder="00000-000"
        inputmode="numeric"
        autocomplete="postal-code"
    />

    <x-admin.campo
        rotulo="CEP fim"
        nome="cep_fim"
        :valor="old('cep_fim', $formatarCep($unidade?->cep_fim))"
        placeholder="00000-000"
        inputmode="numeric"
        autocomplete="postal-code"
    />
</div>
