<x-layouts.admin titulo="Agenda do dia">
    <x-slot:subtitulo>Visitas de hoje por vendedor · meta {{ $meta }}/dia</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.painel')" variante="secundario">Painel</x-admin.botao>
        <x-admin.botao :href="route('admin.visitas.index')" variante="secundario">Todas as visitas</x-admin.botao>
    </x-slot:acoes>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($vendedores as $vendedor)
            @php
                $grupo = $visitasPorVendedor->get($vendedor->id, collect());
                $total = $grupo->count();
                $atingiuMeta = $total >= $meta;
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
                            {{ $total }} / {{ $meta }} visitas
                            @if ($atingiuMeta)
                                <span class="ml-1 text-emerald-600">meta ok</span>
                            @endif
                        </p>
                    </div>
                    <a
                        href="{{ route('admin.painel', ['vendedor_id' => $vendedor->id]) }}"
                        class="text-xs font-semibold text-brand hover:underline"
                    >Mapa</a>
                </div>

                <ul class="mt-3 max-h-48 space-y-2 overflow-y-auto border-t border-surface-line/70 pt-3">
                    @forelse ($grupo as $visita)
                        <li class="flex items-start justify-between gap-2 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-ink">{{ $visita->prospecto?->razao_social ?: '—' }}</p>
                                <p class="truncate text-xs text-ink-faint">{{ $visita->prospecto?->endereco }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-ink-soft">{{ $visita->status?->value ?? $visita->status }}</span>
                        </li>
                    @empty
                        <li class="text-xs text-ink-faint">Sem visitas hoje.</li>
                    @endforelse
                </ul>
            </x-admin.painel>
        @empty
            <p class="text-sm text-ink-soft">Nenhum vendedor ativo.</p>
        @endforelse
    </div>
</x-layouts.admin>
