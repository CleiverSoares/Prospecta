<x-layouts.auth titulo="Recuperar senha">
    <x-slot:marca>
        <x-auth.marca tagline="Informe seu e-mail para receber o link de redefinição." />
    </x-slot:marca>

    <x-auth-session-status class="mb-4 text-sm text-emerald-400" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-auth.campo
            rotulo="E-mail"
            nome="email"
            tipo="email"
            :valor="old('email')"
            placeholder="voce@empresa.com"
            obrigatorio
            autocomplete="username"
            autofocus
        />

        <div class="pt-2">
            <x-auth.botao-primario>Enviar link</x-auth.botao-primario>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-paper/55">
        <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">Voltar ao login</a>
    </p>
</x-layouts.auth>
