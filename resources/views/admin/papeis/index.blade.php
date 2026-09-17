<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Papéis — Prospecta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-5xl px-4 py-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Papéis</h1>
                <p class="mt-1 text-slate-600">Roles Spatie e permissões dinâmicas.</p>
            </div>
            <a href="{{ route('admin.papeis.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Novo papel</a>
        </div>

        @if (session('status'))
            <p class="mt-4 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <ul class="mt-8 divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
            @forelse ($papeis as $papel)
                <li class="flex items-center justify-between gap-4 px-4 py-3">
                    <div>
                        <p class="font-medium">{{ $papel->name }}</p>
                        <p class="text-sm text-slate-500">{{ $papel->permissions->pluck('name')->join(', ') ?: 'Sem permissões' }}</p>
                    </div>
                    <a href="{{ route('admin.papeis.edit', $papel) }}" class="text-sm font-medium text-slate-900 underline">Editar</a>
                </li>
            @empty
                <li class="px-4 py-6 text-sm text-slate-500">Nenhum papel cadastrado.</li>
            @endforelse
        </ul>
    </main>
</body>
</html>
