<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Prospecta') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=syne:700,800|manrope:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh overflow-x-hidden bg-ink font-sans text-paper">
    <x-auth.atmosfera />

    <main class="relative z-10 flex min-h-dvh items-center justify-center px-5 py-10">
        <div class="w-full max-w-[420px]">
            <a href="{{ url('/') }}" class="mb-8 block font-display text-[2.5rem] font-extrabold leading-none tracking-tight text-paper no-underline">
                Prospecta
            </a>
            {{ $slot }}
        </div>
    </main>
</body>
</html>
