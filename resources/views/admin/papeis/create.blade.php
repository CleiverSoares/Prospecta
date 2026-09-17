<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novo papel — Prospecta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-lg px-4 py-10">
        <h1 class="text-2xl font-semibold tracking-tight">Novo papel</h1>
        <form method="POST" action="{{ route('admin.papeis.store') }}" class="mt-8 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            <div>
                <label for="nome" class="block text-sm font-medium">Nome</label>
                <input id="nome" name="nome" value="{{ old('nome') }}" required class="mt-1 w-full rounded-md border-slate-300 shadow-sm">
                @error('nome')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Criar</button>
        </form>
    </main>
</body>
</html>
