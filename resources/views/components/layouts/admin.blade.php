@props([
    'titulo' => 'Admin',
    'mapaFull' => false,
])

<!DOCTYPE html>
<html lang="pt-BR" @class(['h-full overflow-hidden' => $mapaFull])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo }} — Prospecta</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body @class([
    'font-sans text-ink antialiased',
    'min-h-dvh bg-surface-muted' => ! $mapaFull,
    'h-full overflow-hidden' => $mapaFull,
])>
    <div @class([
        'admin-shell flex',
        'min-h-dvh' => ! $mapaFull,
        'admin-shell--mapa h-dvh max-h-dvh overflow-hidden' => $mapaFull,
    ])>
        <x-admin.sidebar />

        <div @class([
            'flex min-w-0 flex-1 flex-col',
            'admin-mapa-col' => $mapaFull,
        ])>
            <x-admin.topo
                :titulo="$titulo"
                :subtitulo="isset($subtitulo) ? $subtitulo : null"
                :translucido="$mapaFull"
                :compacto="$mapaFull"
            >
                @isset($acoes)
                    <x-slot:acoes>
                        {{ $acoes }}
                    </x-slot:acoes>
                @endisset
            </x-admin.topo>

            <main @class([
                'flex-1',
                'px-4 py-5 sm:px-6 lg:px-8' => ! $mapaFull,
                'admin-mapa-main' => $mapaFull,
            ])>
                @if (session('status'))
                    <p class="mb-4 rounded-2xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-2.5 text-sm text-emerald-800" role="status">
                        {{ session('status') }}
                    </p>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
    {{ $scripts ?? '' }}
</body>
</html>
