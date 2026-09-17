<x-layouts.auth titulo="Nova senha">
    <x-slot:marca>
        <x-auth.marca tagline="Defina uma nova senha para continuar no Prospecta." />
    </x-slot:marca>

    <div class="mb-6">
        <h2 class="text-xl font-semibold tracking-tight text-ink">Nova senha</h2>
        <p class="mt-1 text-sm text-ink-soft">Escolha uma senha forte e confirme.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-auth.campo
            rotulo="E-mail"
            nome="email"
            tipo="email"
            :valor="old('email', $request->email)"
            placeholder="voce@empresa.com"
            obrigatorio
            autocomplete="username"
            autofocus
        />

        <x-auth.campo
            rotulo="Nova senha"
            nome="password"
            tipo="password"
            placeholder="••••••••"
            obrigatorio
            autocomplete="new-password"
        />

        <x-auth.campo
            rotulo="Confirmar senha"
            nome="password_confirmation"
            tipo="password"
            placeholder="••••••••"
            obrigatorio
            autocomplete="new-password"
        />

        <div class="pt-1">
            <x-auth.botao-primario>Salvar senha</x-auth.botao-primario>
        </div>
    </form>
</x-layouts.auth>
