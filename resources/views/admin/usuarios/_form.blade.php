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

    <div class="space-y-2">
        <label for="foto" class="block text-sm font-medium text-ink">Foto (mapa ao vivo)</label>
        <div class="flex flex-wrap items-center gap-4">
            @if ($usuario?->foto_url)
                <img src="{{ $usuario->foto_url }}" alt="" class="size-16 rounded-full object-cover ring-2 ring-surface-line">
            @endif
            <input
                id="foto"
                type="file"
                name="foto"
                accept="image/*"
                class="block w-full max-w-sm text-sm text-ink-soft file:mr-3 file:rounded-xl file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-semibold file:text-ink"
            >
        </div>
        @error('foto')
            <p class="text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

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

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <x-admin.campo
                rotulo="Origem preferencial (rótulo)"
                nome="origem_rotulo"
                :valor="old('origem_rotulo', $usuario?->origem_rotulo)"
                placeholder="Hotel / Escritório"
            />
            <x-admin.campo
                rotulo="Origem lat"
                nome="origem_lat"
                :valor="old('origem_lat', $usuario?->origem_lat)"
                placeholder="-22.9"
            />
            <x-admin.campo
                rotulo="Origem lng"
                nome="origem_lng"
                :valor="old('origem_lng', $usuario?->origem_lng)"
                placeholder="-43.2"
            />
        </div>
    </div>

    <div class="flex flex-wrap gap-2 border-t border-surface-line pt-5">
        <x-admin.botao tipo="submit">{{ $rotuloSubmit }}</x-admin.botao>
        <x-admin.botao :href="route('admin.usuarios.index')" variante="secundario">Cancelar</x-admin.botao>

        @if ($usuario && auth()->user()?->can('usuarios.editar') && $usuario->id !== auth()->id())
            @if ($usuario->ativo)
                <form method="POST" action="{{ route('admin.usuarios.desativar', $usuario) }}" onsubmit="return confirm('Desativar este usuário?')">
                    @csrf
                    <x-admin.botao tipo="submit" variante="perigo">Desativar</x-admin.botao>
                </form>
            @else
                <form method="POST" action="{{ route('admin.usuarios.reativar', $usuario) }}">
                    @csrf
                    <x-admin.botao tipo="submit" variante="secundario">Reativar</x-admin.botao>
                </form>
            @endif
        @endif
    </div>
</div>
