@php
    /** @var \App\Models\Unidade|null $unidade */
    $unidade = $unidade ?? null;
    $rotuloSubmit = $rotuloSubmit ?? 'Salvar';

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

<div class="grid gap-6 lg:grid-cols-12 lg:gap-8">
    <div class="space-y-5 lg:col-span-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Dados</p>
            <p class="mt-1 text-sm text-ink-soft">Identificação e tipo da unidade.</p>
        </div>

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

        <div class="border-t border-surface-line pt-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Faixa de CEP</p>
            <p class="mt-1 mb-4 text-sm text-ink-soft">Regra oficial de território (início → fim).</p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
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
        </div>

        <div class="flex flex-wrap gap-2 border-t border-surface-line pt-5">
            <x-admin.botao tipo="submit">{{ $rotuloSubmit }}</x-admin.botao>
            <x-admin.botao :href="route('admin.unidades.index')" variante="secundario">Cancelar</x-admin.botao>
        </div>
    </div>

    <div class="min-w-0 lg:col-span-8">
        <x-admin.mapa-unidade :poligono="old('poligono_geojson', $unidade?->poligono_geojson)" />
    </div>
</div>
