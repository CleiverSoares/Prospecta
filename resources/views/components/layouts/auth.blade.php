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
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh overflow-x-hidden font-sans text-ink antialiased">
    <div class="auth-shell relative min-h-dvh">
        <x-auth.atmosfera />

        <div class="relative z-10 mx-auto grid min-h-dvh w-full max-w-6xl lg:grid-cols-2">
            <section class="auth-rise flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-14">
                {{ $marca ?? '' }}
            </section>

            <section class="auth-rise-delay flex flex-col justify-center px-6 py-10 sm:px-10 lg:px-12">
                <div class="auth-panel mx-auto w-full max-w-md rounded-xl p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </section>
        </div>
    </div>
</body>
</html>
