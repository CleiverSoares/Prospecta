<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — Prospecta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-5xl px-4 py-10">
        <h1 class="text-2xl font-semibold tracking-tight">Painel admin</h1>
        <p class="mt-2 text-slate-600">Área desktop do Prospecta.</p>
        <nav class="mt-8 flex flex-wrap gap-4 text-sm font-medium">
            @can('unidades.ver')
                <a href="{{ route('admin.unidades.index') }}" class="underline">Unidades</a>
            @endcan
            @can('papeis.gerenciar')
                <a href="{{ route('admin.papeis.index') }}" class="underline">Papéis</a>
            @endcan
        </nav>
    </main>
</body>
</html>
