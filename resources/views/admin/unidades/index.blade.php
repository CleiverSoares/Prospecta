<x-layouts.admin titulo="Unidades">
    <x-slot:subtitulo>Faixas de CEP e território visual por unidade</x-slot:subtitulo>

    <x-slot:acoes>
        @can('unidades.criar')
            <x-admin.botao :href="route('admin.unidades.create')">Nova unidade</x-admin.botao>
        @endcan
    </x-slot:acoes>

    @if ($unidades->isEmpty())
        <x-admin.painel class="text-center">
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-brand">Comece por aqui</p>
            <p class="mt-4 font-display text-3xl font-extrabold text-paper">Nenhuma unidade ainda</p>
            <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-paper/50">
                Cadastre a primeira filial ou representação com intervalo de CEP.
                O mapa é opcional — a regra de território usa a faixa.
            </p>
            @can('unidades.criar')
                <div class="mt-8 flex justify-center">
                    <x-admin.botao :href="route('admin.unidades.create')">Criar primeira unidade</x-admin.botao>
                </div>
            @endcan
        </x-admin.painel>
    @else
        <x-admin.painel :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-brand/10 text-paper/45">
                        <tr>
                            <th class="px-5 py-4 font-semibold">Nome</th>
                            <th class="px-5 py-4 font-semibold">Tipo</th>
                            <th class="px-5 py-4 font-semibold">CEP</th>
                            <th class="px-5 py-4 font-semibold"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand/10">
                        @foreach ($unidades as $unidade)
                            <tr class="transition hover:bg-white/[0.03]">
                                <td class="px-5 py-4 font-semibold text-paper">{{ $unidade->nome }}</td>
                                <td class="px-5 py-4 text-paper/60">{{ $unidade->tipo->value }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-paper/60 sm:text-sm">
                                    {{ $unidade->cep_inicio ?: '—' }}
                                    @if ($unidade->cep_fim)
                                        <span class="text-brand/70">→</span> {{ $unidade->cep_fim }}
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @can('unidades.editar')
                                        <a href="{{ route('admin.unidades.edit', $unidade) }}" class="font-bold text-brand hover:text-brand-strong">
                                            Editar
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.painel>
    @endif
</x-layouts.admin>
