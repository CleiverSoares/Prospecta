@php
    /** @var \App\Models\User|null $usuario */
    $usuario = $usuario ?? null;
    $rotuloSubmit = $rotuloSubmit ?? 'Salvar';
    $papelAtual = old('role', $usuario?->roles->first()?->name);

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

    $rotulosPapel = [
        'adm' => 'Administrador',
        'gestor' => 'Gestor',
        'vendedor' => 'Vendedor',
    ];
@endphp

<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Dados</p>
        <p class="mt-1 text-sm text-ink-soft">Identificação e acesso do usuário.</p>
    </div>

    <x-admin.campo
        rotulo="Nome"
        nome="name"
        :valor="old('name', $usuario?->name)"
        obrigatorio
    />

    <x-admin.campo
        rotulo="E-mail"
        nome="email"
        tipo="email"
        :valor="old('email', $usuario?->email)"
        obrigatorio
        autocomplete="email"
    />

    <x-admin.campo
        rotulo="Senha"
        nome="password"
        tipo="password"
        :valor="''"
        :obrigatorio="! $usuario"
        autocomplete="new-password"
        :placeholder="$usuario ? 'Deixe em branco para manter' : null"
    />

    <x-admin.campo
        rotulo="Papel"
        nome="role"
        tipo="select"
        obrigatorio
    >
        @foreach ($papeis as $papel)
            <option value="{{ $papel }}" @selected($papelAtual === $papel)>
                {{ $rotulosPapel[$papel] ?? $papel }}
            </option>
        @endforeach
    </x-admin.campo>

    <div class="border-t border-surface-line pt-5">
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Território</p>
        <p class="mt-1 mb-4 text-sm text-ink-soft">Unidade, gestor e base CEP do vendedor.</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.campo
                rotulo="Unidade"
                nome="unidade_id"
                tipo="select"
            >
                <option value="">— Sem unidade —</option>
                @foreach ($unidades as $unidade)
                    <option value="{{ $unidade->id }}" @selected((string) old('unidade_id', $usuario?->unidade_id) === (string) $unidade->id)>
                        {{ $unidade->nome }}
                    </option>
                @endforeach
            </x-admin.campo>

            <x-admin.campo
                rotulo="Gestor"
                nome="gestor_id"
                tipo="select"
            >
                <option value="">— Sem gestor —</option>
                @foreach ($gestores as $gestor)
                    <option value="{{ $gestor->id }}" @selected((string) old('gestor_id', $usuario?->gestor_id) === (string) $gestor->id)>
                        {{ $gestor->name }}
                    </option>
                @endforeach
            </x-admin.campo>

            <x-admin.campo
                rotulo="CEP base início"
                nome="cep_base_inicio"
                :valor="old('cep_base_inicio', $formatarCep($usuario?->cep_base_inicio))"
                placeholder="00000-000"
                inputmode="numeric"
                autocomplete="postal-code"
            />

            <x-admin.campo
                rotulo="CEP base fim"
                nome="cep_base_fim"
                :valor="old('cep_base_fim', $formatarCep($usuario?->cep_base_fim))"
                placeholder="00000-000"
                inputmode="numeric"
                autocomplete="postal-code"
            />
        </div>
    </div>

    <div class="flex flex-wrap gap-2 border-t border-surface-line pt-5">
        <x-admin.botao tipo="submit">{{ $rotuloSubmit }}</x-admin.botao>
        <x-admin.botao :href="route('admin.usuarios.index')" variante="secundario">Cancelar</x-admin.botao>
    </div>
</div>
