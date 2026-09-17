<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar {{ $papel->name }} — Prospecta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-3xl px-4 py-10">
        <a href="{{ route('admin.papeis.index') }}" class="text-sm text-slate-600 underline">Voltar</a>
        <h1 class="mt-4 text-2xl font-semibold tracking-tight">Papel: {{ $papel->name }}</h1>

        @if (session('status'))
            <p class="mt-4 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.papeis.update', $papel) }}" class="mt-8 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            @method('PUT')
            <fieldset class="space-y-2">
                <legend class="text-sm font-medium">Permissões</legend>
                @foreach ($permissoes as $permissao)
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="permissoes[]"
                            value="{{ $permissao->name }}"
                            @checked($papel->permissions->contains('name', $permissao->name))
                        >
                        <span>{{ $permissao->name }}</span>
                    </label>
                @endforeach
            </fieldset>
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Salvar</button>
        </form>
    </main>
</body>
</html>
