<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar — Prospecta</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=syne:600,700,800|manrope:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-shell antialiased">
    <div class="auth-atmosphere" aria-hidden="true">
        <div class="auth-grid"></div>
        <div class="auth-glow auth-glow--a"></div>
        <div class="auth-glow auth-glow--b"></div>
        <div class="auth-route"></div>
        <div class="auth-noise"></div>
    </div>

    <main class="auth-stage">
        <section class="auth-brand">
            <p class="auth-kicker">Field sales · território</p>
            <h1 class="auth-logo">Prospecta</h1>
            <p class="auth-tagline">Prospecção de campo com território sob controle.</p>
        </section>

        <section class="auth-panel" aria-label="Acesso">
            <x-auth-session-status class="auth-status" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="auth-form">
                @csrf

                <div class="auth-field">
                    <label for="email">E-mail</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="voce@empresa.com"
                    >
                    <x-input-error :messages="$errors->get('email')" class="auth-error" />
                </div>

                <div class="auth-field">
                    <div class="auth-field-head">
                        <label for="password">Senha</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}">Esqueci a senha</a>
                        @endif
                    </div>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    >
                    <x-input-error :messages="$errors->get('password')" class="auth-error" />
                </div>

                <label class="auth-remember">
                    <input id="remember_me" type="checkbox" name="remember">
                    <span>Manter conectado</span>
                </label>

                <button type="submit" class="auth-submit">Entrar</button>
            </form>

            @if (Route::has('register'))
                <p class="auth-foot">
                    Ainda sem acesso?
                    <a href="{{ route('register') }}">Criar conta</a>
                </p>
            @endif
        </section>
    </main>
</body>
</html>
