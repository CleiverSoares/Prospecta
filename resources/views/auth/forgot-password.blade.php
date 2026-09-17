<x-layouts.auth titulo="Recuperar senha">
    <x-slot:marca>
        <x-auth.marca tagline="Recupere o acesso com o e-mail cadastrado na conta." />
    </x-slot:marca>

    <div class="mb-6">
        <h2 class="text-xl font-semibold tracking-tight text-ink">Recuperar senha</h2>
        <p class="mt-1 text-sm text-ink-soft">Enviaremos um link de redefinição.</p>
    </div>

    <x-auth-session-status class="mb-4 text-sm text-emerald-700" :status="session('status')" />

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

        <div class="pt-1">
            <x-auth.botao-primario>Enviar link</x-auth.botao-primario>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-ink-soft">
        <a href="{{ route('login') }}" class="font-medium text-brand hover:underline">Voltar ao login</a>
    </p>
</x-layouts.auth>
