@props([
    'titulo' => null,
])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo ? $titulo.' — ' : '' }}{{ config('app.name', 'Prospecta') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=syne:700,800|manrope:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh overflow-x-hidden bg-ink font-sans text-paper">
    <x-auth.atmosfera />

    <div class="relative z-10 mx-auto grid min-h-dvh w-full max-w-6xl lg:grid-cols-2">
        <section class="auth-rise flex flex-col justify-center px-6 py-10 sm:px-10 lg:px-14">
            {{ $marca ?? '' }}
        </section>

        <section class="auth-rise-delay flex flex-col justify-center border-t border-white/10 bg-black/25 px-6 py-10 backdrop-blur-sm sm:px-10 lg:border-l lg:border-t-0 lg:px-14 lg:bg-black/20">
            <div class="mx-auto w-full max-w-md">
                {{ $slot }}
            </div>
        </section>
    </div>
</body>
</html>
