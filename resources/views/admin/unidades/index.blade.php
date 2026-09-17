<x-layouts.admin titulo="Unidades">
    <x-slot:subtitulo>Território por intervalo de CEP</x-slot:subtitulo>

    <x-slot:acoes>
        @can('unidades.criar')
            <x-admin.botao :href="route('admin.unidades.create')">Nova unidade</x-admin.botao>
        @endcan
    </x-slot:acoes>

    <x-admin.painel :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-surface-line bg-surface-muted text-ink-soft">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Nome</th>
                        <th class="px-4 py-2.5 font-medium">Tipo</th>
                        <th class="px-4 py-2.5 font-medium">CEP</th>
                        <th class="px-4 py-2.5 font-medium"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-line">
                    @forelse ($unidades as $unidade)
                        <tr class="hover:bg-surface-muted/70">
                            <td class="px-4 py-2.5 font-medium text-ink">{{ $unidade->nome }}</td>
                            <td class="px-4 py-2.5 text-ink-soft">{{ $unidade->tipo->value }}</td>
                            <td class="px-4 py-2.5 tabular-nums text-ink-soft">
                                {{ $unidade->cep_inicio ?: '—' }}
                                @if ($unidade->cep_fim)
                                    → {{ $unidade->cep_fim }}
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                @can('unidades.editar')
                                    <a href="{{ route('admin.unidades.edit', $unidade) }}" class="font-medium text-brand hover:text-brand-strong">
                                        Editar
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-ink-soft">
                                Nenhuma unidade cadastrada.
                                @can('unidades.criar')
                                    <a href="{{ route('admin.unidades.create') }}" class="font-medium text-brand hover:underline">Criar a primeira</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.painel>
</x-layouts.admin>
