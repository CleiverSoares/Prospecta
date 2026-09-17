<x-layouts.auth titulo="Entrar">
    <x-slot:marca>
        <x-auth.marca />
    </x-slot:marca>

    <x-auth-session-status class="mb-4 text-sm text-emerald-400" :status="session('status')" />

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

        <label class="flex items-center gap-2.5 pt-1 text-sm text-paper/75">
            <input
                id="remember_me"
                type="checkbox"
                name="remember"
                class="size-4 rounded border-white/20 bg-ink text-brand focus:ring-brand/40"
            >
            <span>Manter conectado</span>
        </label>

        <div class="pt-2">
            <x-auth.botao-primario>Entrar</x-auth.botao-primario>
        </div>
    </form>
</x-layouts.auth>
