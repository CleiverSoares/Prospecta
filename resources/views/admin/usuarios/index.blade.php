<x-layouts.admin titulo="Usuários">
    <x-slot:subtitulo>Vendedores, gestores e admins — criar, editar e vincular à unidade</x-slot:subtitulo>

    <x-slot:acoes>
        @can('usuarios.criar')
            <x-admin.botao :href="route('admin.usuarios.create')">Novo usuário</x-admin.botao>
        @endcan
    </x-slot:acoes>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Buscar nome ou e-mail"
            class="h-10 min-w-[14rem] flex-1 rounded-2xl border-0 bg-white/90 px-4 text-sm shadow-sm focus:ring-2 focus:ring-brand/25"
        >
        <select
            name="papel"
            class="h-10 rounded-2xl border-0 bg-white/90 px-3 text-sm shadow-sm focus:ring-2 focus:ring-brand/25"
        >
            <option value="">Todos os papéis</option>
            <option value="vendedor" @selected(($papelFiltro ?? '') === 'vendedor')>Só vendedores</option>
            <option value="gestor" @selected(($papelFiltro ?? '') === 'gestor')>Só gestores</option>
            <option value="adm" @selected(($papelFiltro ?? '') === 'adm')>Só admins</option>
        </select>
        <label class="inline-flex h-10 items-center gap-2 rounded-2xl bg-white/90 px-3 text-sm text-ink-soft shadow-sm">
            <input type="checkbox" name="incluir_inativos" value="1" @checked(request()->boolean('incluir_inativos')) class="rounded border-slate-300 text-brand focus:ring-brand/40">
            Incluir inativos
        </label>
        <button type="submit" class="h-10 rounded-2xl bg-brand px-4 text-sm font-semibold text-white">Filtrar</button>
        @if (request()->filled('q') || request()->filled('papel'))
            <a href="{{ route('admin.usuarios.index') }}" class="h-10 inline-flex items-center px-3 text-sm font-medium text-ink-soft hover:text-ink">Limpar</a>
        @endif
    </form>

    <div class="admin-table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-surface-line/80">
                    <tr>
                        <th class="px-5 py-3.5">Pessoa</th>
                        <th class="px-5 py-3.5">E-mail</th>
                        <th class="px-5 py-3.5">Papel</th>
                        <th class="px-5 py-3.5">Unidade</th>
                        <th class="px-5 py-3.5">Gestor</th>
                        <th class="px-5 py-3.5"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-line/70">
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($usuario->foto_url)
                                        <img
                                            src="{{ $usuario->foto_url }}"
                                            alt=""
                                            class="size-10 rounded-full object-cover ring-2 ring-white"
                                        >
                                    @else
                                        <span class="grid size-10 place-items-center rounded-full bg-brand text-xs font-bold text-ink">
                                            {{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}
                                        </span>
                                    @endif
                                    <span class="font-medium text-ink">
                                        {{ $usuario->name }}
                                        @unless ($usuario->ativo)
                                            <span class="ml-1 text-xs font-normal text-rose-600">(inativo)</span>
                                        @endunless
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $usuario->email }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">
                                {{ $usuario->roles->pluck('name')->join(', ') ?: '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $usuario->unidade?->nome ?: '—' }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $usuario->gestor?->name ?: '—' }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @can('usuarios.editar')
                                    <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="font-medium text-brand hover:text-brand-strong">
                                        Editar
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-ink-soft">
                                Nenhum usuário cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usuarios->hasPages())
            <div class="border-t border-surface-line/70 px-5 py-3">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
