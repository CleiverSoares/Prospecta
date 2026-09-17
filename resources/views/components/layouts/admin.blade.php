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
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-surface-muted font-sans text-ink antialiased">
    <div class="admin-shell flex min-h-dvh">
        <x-admin.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
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

            <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8">
                @if (session('status'))
                    <p class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800" role="status">
                        {{ session('status') }}
                    </p>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
