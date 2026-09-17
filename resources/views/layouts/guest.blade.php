<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Prospecta') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=syne:600,700,800|manrope:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-shell antialiased">
        <div class="auth-atmosphere" aria-hidden="true">
            <div class="auth-grid"></div>
            <div class="auth-glow auth-glow--a"></div>
            <div class="auth-glow auth-glow--b"></div>
            <div class="auth-noise"></div>
        </div>

        <div class="relative z-10 mx-auto flex min-h-dvh max-w-lg flex-col justify-center px-5 py-10">
            <a href="/" class="auth-logo mb-8 text-center text-4xl no-underline" style="font-size:2.5rem">Prospecta</a>
            <div class="auth-panel">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
