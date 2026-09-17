@props([
    'titulo' => 'Admin',
])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo }} — Prospecta</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=syne:700,800|manrope:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh font-sans text-paper antialiased">
    <div class="admin-shell relative flex min-h-dvh">
        <div class="admin-grid-soft absolute inset-0" aria-hidden="true"></div>

        <x-admin.sidebar />

        <div class="relative z-10 flex min-w-0 flex-1 flex-col">
            <x-admin.topo
                :titulo="$titulo"
                :subtitulo="isset($subtitulo) ? $subtitulo : null"
            >
                @isset($acoes)
                    <x-slot:acoes>
                        {{ $acoes }}
                    </x-slot:acoes>
                @endisset
            </x-admin.topo>

            <main class="admin-rise flex-1 px-4 py-6 sm:px-8 lg:px-10">
                @if (session('status'))
                    <p class="mb-5 rounded-xl border border-brand/30 bg-brand/10 px-4 py-3 text-sm text-paper" role="status">
                        {{ session('status') }}
                    </p>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
