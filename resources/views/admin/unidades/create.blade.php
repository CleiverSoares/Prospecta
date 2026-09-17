<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova unidade — Prospecta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-xl px-4 py-10">
        <a href="{{ route('admin.unidades.index') }}" class="text-sm text-slate-600 underline">Voltar</a>
        <h1 class="mt-4 text-2xl font-semibold tracking-tight">Nova unidade</h1>

        <form method="POST" action="{{ route('admin.unidades.store') }}" class="mt-8 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            @include('admin.unidades._form')
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Criar</button>
        </form>
    </main>
</body>
</html>
