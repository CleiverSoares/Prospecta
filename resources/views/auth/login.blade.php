<x-layouts.auth titulo="Entrar">
    <x-slot:marca>
        <x-auth.marca />
    </x-slot:marca>

    <div class="mb-6">
        <h2 class="text-xl font-semibold tracking-tight text-ink">Entrar</h2>
        <p class="mt-1 text-sm text-ink-soft">Acesse com o e-mail corporativo.</p>
    </div>

    <x-auth-session-status class="mb-4 text-sm text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
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

        <x-auth.campo
            rotulo="Senha"
            nome="password"
            tipo="password"
            placeholder="••••••••"
            obrigatorio
            autocomplete="current-password"
        >
            <x-slot:acao>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand hover:underline">
                    Esqueci a senha
                </a>
            </x-slot:acao>
        </x-auth.campo>

        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input
                id="remember_me"
                type="checkbox"
                name="remember"
                class="size-4 rounded border-surface-line text-brand focus:ring-brand/30"
            >
            <span>Manter conectado</span>
        </label>

        <div class="pt-1">
            <x-auth.botao-primario>Entrar</x-auth.botao-primario>
        </div>
    </form>
</x-layouts.auth>
