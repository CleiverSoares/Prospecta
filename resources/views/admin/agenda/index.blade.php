<x-layouts.admin titulo="Agenda do dia">
    <x-slot:subtitulo>
        Plano publicado pelo app + check-ins de hoje
        · meta da casa: {{ $metaCasa }}
    </x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.painel')" variante="secundario">Painel</x-admin.botao>
        <x-admin.botao :href="route('admin.visitas.index')" variante="secundario">Todas as visitas</x-admin.botao>
    </x-slot:acoes>

    <div class="mb-4 rounded-2xl border border-brand/20 bg-brand-soft/40 px-4 py-3 text-sm text-ink-soft">
        <strong class="font-semibold text-ink">Plano</strong> = rota gerada no PWA (aparece aqui ao gerar).
        <strong class="font-semibold text-ink">Feitas no plano</strong> = check-ins nessas paradas.
        <strong class="font-semibold text-ink">Meta casa</strong> ({{ $metaCasa }}) = meta fixa da operação — não é o tamanho da rota.
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($cards as $card)
            @php
                $vendedor = $card['vendedor'];
            @endphp
            <x-admin.painel class="!p-4">
                <div class="flex items-start gap-3">
                    @if ($vendedor->foto_url)
                        <img src="{{ $vendedor->foto_url }}" alt="" class="size-12 rounded-full object-cover">
                    @else
                        <span class="grid size-12 place-items-center rounded-full bg-brand text-sm font-bold text-ink">
                            {{ mb_strtoupper(mb_substr($vendedor->name, 0, 1)) }}
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink">{{ $vendedor->name }}</p>
                        <p class="text-xs text-ink-soft">{{ $vendedor->unidade?->nome ?: 'Sem unidade' }}</p>
                        <p class="mt-1 text-sm tabular-nums text-ink">
                            @if ($card['tem_plano'])
                                <span class="font-semibold">{{ $card['feitas_plano'] }}/{{ $card['total_plano'] }} no plano</span>
                                <span class="text-ink-soft"> · {{ $card['checkins_hoje'] }} check-in(s)</span>
                            @else
                                <span class="font-semibold">{{ $card['checkins_hoje'] }} check-in(s)</span>
                                <span class="text-ink-faint"> · sem plano publicado</span>
                            @endif
                            <span class="ml-1 text-xs text-ink-faint">meta casa {{ $card['meta_casa'] }}</span>
                            @if ($card['atingiu_meta_casa'])
                                <span class="ml-1 text-xs text-emerald-600">meta ok</span>
                            @endif
                        </p>
                    </div>
                    <a
                        href="{{ route('admin.painel', ['vendedor_id' => $vendedor->id, 'de' => today()->toDateString(), 'ate' => today()->toDateString()]) }}"
                        class="shrink-0 text-xs font-semibold text-brand hover:underline"
                        title="Pins cinza = check-ins · linha = GPS · pin colorido = ao vivo"
                    >Ver no mapa</a>
                </div>

                @if ($card['tem_plano'])
                    <ul class="mt-3 max-h-56 space-y-2 overflow-y-auto border-t border-surface-line/70 pt-3">
                        @foreach ($card['paradas'] as $parada)
                            <li class="flex items-start justify-between gap-2 text-sm">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-ink">
                                        <span class="text-ink-faint">{{ $parada['ordem'] }}.</span>
                                        {{ $parada['nome'] }}
                                    </p>
                                    <p class="truncate text-xs text-ink-faint">{{ $parada['endereco'] }}</p>
                                </div>
                                <span @class([
                                    'shrink-0 text-xs',
                                    'text-emerald-600' => $parada['status'] === 'feita',
                                    'text-ink-soft' => $parada['status'] !== 'feita',
                                ])>{{ $parada['status_rotulo'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <ul class="mt-3 max-h-48 space-y-2 overflow-y-auto border-t border-surface-line/70 pt-3">
                        @forelse ($card['fora_do_plano'] as $item)
                            <li class="flex items-start justify-between gap-2 text-sm">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-ink">{{ $item['nome'] }}</p>
                                    <p class="truncate text-xs text-ink-faint">{{ $item['endereco'] }}</p>
                                </div>
                                <span class="shrink-0 text-xs text-ink-soft">{{ $item['status_rotulo'] }}</span>
                            </li>
                        @empty
                            <li class="text-xs text-ink-faint">Sem plano e sem check-ins hoje. A rota só aparece aqui depois que o vendedor gera no app.</li>
                        @endforelse
                    </ul>
                @endif

                @if ($card['tem_plano'] && count($card['fora_do_plano']))
                    <div class="mt-3 border-t border-dashed border-surface-line/70 pt-3">
                        <p class="mb-2 text-[0.7rem] font-semibold uppercase tracking-wide text-ink-faint">Fora do plano</p>
                        <ul class="max-h-28 space-y-2 overflow-y-auto">
                            @foreach ($card['fora_do_plano'] as $item)
                                <li class="flex items-start justify-between gap-2 text-sm">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-ink">{{ $item['nome'] }}</p>
                                        <p class="truncate text-xs text-ink-faint">{{ $item['endereco'] }}</p>
                                    </div>
                                    <span class="shrink-0 text-xs text-ink-soft">{{ $item['status_rotulo'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-admin.painel>
        @empty
            <p class="text-sm text-ink-soft">Nenhum vendedor ativo{{ ($escopoGestor ?? false) ? ' na sua equipe' : '' }}.</p>
        @endforelse
    </div>
</x-layouts.admin>
