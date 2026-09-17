<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unidades — Prospecta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-5xl px-4 py-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Unidades</h1>
                <p class="mt-1 text-slate-600">Matriz, filial e representação.</p>
            </div>
            @can('unidades.criar')
                <a href="{{ route('admin.unidades.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Nova unidade</a>
            @endcan
        </div>

        @if (session('status'))
            <p class="mt-4 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <div class="mt-8 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nome</th>
                        <th class="px-4 py-3 font-medium">Tipo</th>
                        <th class="px-4 py-3 font-medium">CEP</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($unidades as $unidade)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $unidade->nome }}</td>
                            <td class="px-4 py-3">{{ $unidade->tipo->value }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $unidade->cep_inicio ?: '—' }}
                                @if ($unidade->cep_fim)
                                    → {{ $unidade->cep_fim }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('unidades.editar')
                                    <a href="{{ route('admin.unidades.edit', $unidade) }}" class="underline">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">Nenhuma unidade cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
