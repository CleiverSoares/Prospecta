@php
    $config = [
        'prospectarUrl' => route('app.area.prospectar'),
        'detalheUrl' => route('app.places.detalhe'),
        'rotaUrl' => route('app.rota'),
        'csrf' => csrf_token(),
    ];
@endphp

<x-layouts.app-pwa titulo="Onde prospectar hoje?" passo="area">
    <x-slot:subtitulo>Bairro/CEP e/ou desenhe a cerca no mapa. O sistema libera só área sua ou livre.</x-slot:subtitulo>

    @if ($googleMapsKey)
        <x-slot:head>
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&language=pt-BR" defer></script>
        </x-slot:head>
    @endif

    <div class="grid gap-4 lg:grid-cols-12" x-data="ondeProspectar(@js($config))">
        <div class="space-y-4 rounded-xl border border-surface-line bg-white p-4 shadow-panel sm:p-5 lg:col-span-4">
            <label class="block space-y-1.5">
                <span class="text-sm font-medium text-ink">Bairro</span>
                <input x-model="bairro" type="text" class="h-11 w-full rounded-md border border-surface-line px-3 text-sm" placeholder="Ex.: Copacabana">
            </label>
            <div class="grid grid-cols-3 gap-2">
                <label class="col-span-2 block space-y-1.5">
                    <span class="text-sm font-medium text-ink">Cidade</span>
                    <input x-model="cidade" type="text" class="h-11 w-full rounded-md border border-surface-line px-3 text-sm" placeholder="Rio de Janeiro">
                </label>
                <label class="block space-y-1.5">
                    <span class="text-sm font-medium text-ink">UF</span>
                    <input x-model="uf" type="text" maxlength="2" class="h-11 w-full rounded-md border border-surface-line px-3 text-sm uppercase" placeholder="RJ">
                </label>
            </div>
            <label class="block space-y-1.5">
                <span class="text-sm font-medium text-ink">CEP (opcional)</span>
                <input x-model="cep" type="text" class="h-11 w-full rounded-md border border-surface-line px-3 text-sm" placeholder="22041-080">
            </label>

            <p class="text-xs text-ink-soft">Desenhar: clique em “Desenhar cerca”, marque ≥3 pontos no mapa e “Fechar cerca”. Depois busque leads no Google Places.</p>

            <div class="pwa-btn-row">
                <button
                    type="button"
                    class="pwa-btn pwa-btn-ghost"
                    @click="toggleDesenho()"
                    x-text="desenhando ? 'Cancelar' : 'Desenhar cerca'"
                ></button>
                <button
                    type="button"
                    class="pwa-btn pwa-btn-secondary"
                    :disabled="!desenhando || vertices.length < 3"
                    @click="fecharCerca()"
                >Fechar cerca</button>
            </div>
            <button
                type="button"
                class="pwa-btn pwa-btn-ghost !min-h-10 text-ink-soft"
                x-show="poligono"
                x-cloak
                @click="limparCerca()"
            >Limpar cerca</button>

            <p class="rounded-md border border-brand/20 bg-brand-soft px-3 py-2 text-sm text-brand-strong" x-show="status" x-text="status" x-cloak></p>
            <p class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" x-show="erro" x-text="erro" x-cloak></p>

            <button type="button" class="pwa-btn pwa-btn-primary" @click="buscarLeads()">Buscar leads na área</button>

            <div class="space-y-2" x-show="prospectos.length" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-medium text-ink">
                        <span x-text="selecionadosIds.length"></span> de <span x-text="prospectos.length"></span> na rota
                    </p>
                    <div class="flex gap-2">
                        <button type="button" class="text-xs font-semibold text-brand" @click="marcarTodos()">Todos</button>
                        <button type="button" class="text-xs font-semibold text-ink-soft" @click="limparSelecao()">Nenhum</button>
                    </div>
                </div>

                <ul class="max-h-72 space-y-2 overflow-y-auto">
                    <template x-for="(p, idx) in prospectos" :key="p.id">
                        <li>
                            <label
                                class="flex cursor-pointer gap-3 rounded-xl border px-3 py-2.5 text-sm transition active:scale-[0.99]"
                                :class="estaSelecionado(p.id) ? 'border-brand/40 bg-brand-soft/40' : 'border-surface-line bg-white opacity-70'"
                            >
                                <input
                                    type="checkbox"
                                    class="mt-1 size-4 accent-[#0083C1]"
                                    :checked="estaSelecionado(p.id)"
                                    @change="toggleLead(p.id)"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="font-semibold text-brand" x-text="(idx+1) + '.'"></span>
                                    <span class="font-medium text-ink" x-text="p.razao_social"></span>
                                    <p class="text-xs text-ink-soft" x-text="p.endereco"></p>
                                    <p class="text-xs text-ink-faint" x-show="p.telefone" x-text="p.telefone"></p>
                                </span>
                            </label>
                        </li>
                    </template>
                </ul>

                <button
                    type="button"
                    class="pwa-btn pwa-btn-primary"
                    :disabled="!selecionadosIds.length"
                    @click="irParaRota()"
                    x-text="'Gerar rota · ' + selecionadosIds.length + ' lead(s)'"
                ></button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-surface-line bg-white shadow-panel lg:col-span-8">
            <div x-ref="mapa" class="pwa-map w-full bg-surface-muted"></div>
        </div>
    </div>
</x-layouts.app-pwa>
