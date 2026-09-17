<x-layouts.admin titulo="Unidades">
    <x-slot:subtitulo>Matriz, filial e representação</x-slot:subtitulo>

    <x-slot:acoes>
        @can('unidades.criar')
            <x-admin.botao :href="route('admin.unidades.create')">Nova unidade</x-admin.botao>
        @endcan
    </x-slot:acoes>

    <x-admin.painel :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nome</th>
                        <th class="px-4 py-3 font-medium">Tipo</th>
                        <th class="px-4 py-3 font-medium">CEP</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($unidades as $unidade)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $unidade->nome }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $unidade->tipo->value }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $unidade->cep_inicio ?: '—' }}
                                @if ($unidade->cep_fim)
                                    → {{ $unidade->cep_fim }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('unidades.editar')
                                    <a href="{{ route('admin.unidades.edit', $unidade) }}" class="font-medium text-brand-strong hover:underline">
                                        Editar
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">
                                Nenhuma unidade cadastrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.painel>
</x-layouts.admin>
