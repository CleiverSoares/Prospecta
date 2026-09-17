<x-layouts.auth titulo="Nova senha">
    <x-slot:marca>
        <x-auth.marca tagline="Defina uma nova senha para acessar o Prospecta." />
    </x-slot:marca>

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

        <div class="pt-2">
            <x-auth.botao-primario>Salvar senha</x-auth.botao-primario>
        </div>
    </form>
</x-layouts.auth>
