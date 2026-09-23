@php
    $config = [
        'prospectarUrl' => route('app.area.prospectar'),
        'municipiosUrl' => route('app.area.municipios'),
        'bairrosUrl' => route('app.area.bairros'),
        'detalheUrl' => route('app.places.detalhe'),
        'rotaUrl' => route('app.rota'),
        'csrf' => csrf_token(),
    ];
@endphp

<x-layouts.app-pwa titulo="Onde prospectar hoje?" passo="area">
    <x-slot:subtitulo>Escolha UF → cidade → bairro (com sugestões) ou desenhe a cerca no mapa.</x-slot:subtitulo>

    @if ($googleMapsKey)
        <x-slot:head>
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&language=pt-BR&libraries=places&callback=Function.prototype" async defer></script>
        </x-slot:head>
    @endif

    <div class="grid gap-4 lg:grid-cols-12" x-data="ondeProspectar(@js($config))">
        <div class="space-y-4 rounded-xl border border-surface-line bg-white p-4 shadow-panel sm:p-5 lg:col-span-4">
            <div class="grid grid-cols-3 gap-2">
                <label class="block space-y-1.5">
                    <span class="text-sm font-medium text-ink">1. UF</span>
                    <select
                        x-model="uf"
                        @change="aoMudarUf()"
                        class="h-11 w-full rounded-md border border-surface-line pl-2 pr-8 text-sm"
                    >
                        <option value="">UF</option>
                        <template x-for="sigla in ufs" :key="sigla">
                            <option :value="sigla" x-text="sigla"></option>
                        </template>
                    </select>
                </label>
                <label class="col-span-2 relative block space-y-1.5">
                    <span class="text-sm font-medium text-ink">2. Cidade</span>
                    <input
                        x-ref="cidadeInput"
                        type="text"
                        class="h-11 w-full rounded-md border border-surface-line px-3 text-sm disabled:bg-surface-muted disabled:text-ink-faint"
                        placeholder="Digite e escolha na lista"
                        autocomplete="off"
                        :disabled="!uf"
                        @input="aoDigitarCidade($event.target.value)"
                        @keydown.escape="sugestoesCidade = []"
                        @keydown.arrow-down.prevent="focarSugestao('cidade', 1)"
                        @keydown.arrow-up.prevent="focarSugestao('cidade', -1)"
                        @keydown.enter.prevent="confirmarSugestaoAtiva('cidade')"
                    >
                    <ul class="pwa-ac-lista" x-show="sugestoesCidade.length" x-cloak @mousedown.prevent>
                        <template x-for="(s, i) in sugestoesCidade" :key="s.placeId">
                            <li>
                                <button
                                    type="button"
                                    class="pwa-ac-item"
                                    :class="i === idxCidade ? 'is-active' : ''"
                                    @click="escolherSugestaoCidade(s)"
                                >
                                    <span class="font-semibold text-ink" x-text="s.main"></span>
                                    <span class="block text-xs text-ink-soft" x-text="s.secondary"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <p class="text-[11px] text-amber-800" x-show="avisoCidade" x-text="avisoCidade" x-cloak></p>
                </label>
            </div>

            <label class="relative block space-y-1.5">
                <span class="text-sm font-medium text-ink">3. Bairro</span>
                <input
                    x-ref="bairroInput"
                    type="text"
                    class="h-11 w-full rounded-md border border-surface-line px-3 text-sm disabled:bg-surface-muted disabled:text-ink-faint"
                    placeholder="Digite e escolha na lista"
                    autocomplete="off"
                    :disabled="!cidade"
                    @input="aoDigitarBairro($event.target.value)"
                    @keydown.escape="sugestoesBairro = []"
                    @keydown.arrow-down.prevent="focarSugestao('bairro', 1)"
                    @keydown.arrow-up.prevent="focarSugestao('bairro', -1)"
                    @keydown.enter.prevent="confirmarSugestaoAtiva('bairro')"
                >
                <ul class="pwa-ac-lista" x-show="sugestoesBairro.length" x-cloak @mousedown.prevent>
                    <template x-for="(s, i) in sugestoesBairro" :key="s.placeId">
                        <li>
                            <button
                                type="button"
                                class="pwa-ac-item"
                                :class="i === idxBairro ? 'is-active' : ''"
                                @click="escolherSugestaoBairro(s)"
                            >
                                <span class="font-semibold text-ink" x-text="s.main"></span>
                                <span class="block text-xs text-ink-soft" x-text="s.secondary"></span>
                            </button>
                        </li>
                    </template>
                </ul>
                <p class="text-[11px] text-amber-800" x-show="avisoBairro" x-text="avisoBairro" x-cloak></p>
            </label>

            <label class="block space-y-1.5">
                <span class="text-sm font-medium text-ink">CEP <span class="font-normal text-ink-faint">(opcional)</span></span>
                <input x-model="cep" type="text" inputmode="numeric" class="h-11 w-full rounded-md border border-surface-line px-3 text-sm" placeholder="22041-080">
            </label>

            <p class="text-xs leading-relaxed text-ink-soft">
                Ordem: <strong class="font-medium text-ink">UF → cidade → bairro</strong>.
                Cidades vêm do <strong class="font-medium text-ink">IBGE</strong> (só do estado).
                Bairros: ViaCEP na cidade + filtros — sem rua “Teresópolis” em outro município.
            </p>

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

            <p
                class="flex items-center gap-2 rounded-md border border-brand/20 bg-brand-soft px-3 py-2 text-sm text-brand-strong"
                x-show="status || buscando"
                x-cloak
            >
                <span
                    x-show="buscando"
                    class="inline-block size-4 shrink-0 animate-spin rounded-full border-2 border-brand/30 border-t-brand"
                    aria-hidden="true"
                ></span>
                <span x-text="buscando ? (status || 'Buscando leads…') : status"></span>
            </p>
            <p
                class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-900"
                x-show="localResolvido && !buscando"
                x-cloak
            >
                Centro da busca: <strong x-text="localResolvido"></strong>
            </p>
            <template x-for="(aviso, ai) in avisos" :key="ai">
                <p class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950" x-text="aviso" x-cloak></p>
            </template>
            <p class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" x-show="erro && !buscando" x-text="erro" x-cloak></p>

            <button
                type="button"
                class="pwa-btn pwa-btn-primary"
                :disabled="buscando || (!uf && !poligono && !cep)"
                @click="buscarLeads()"
            >
                <span x-show="!buscando">Buscar leads na área</span>
                <span x-show="buscando" x-cloak class="inline-flex items-center gap-2">
                    <span class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    Buscando…
                </span>
            </button>

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

        <div class="relative overflow-hidden rounded-xl border border-surface-line bg-white shadow-panel lg:col-span-8">
            <div x-ref="mapa" class="pwa-map w-full bg-surface-muted"></div>
            <div
                x-show="buscando"
                x-cloak
                class="absolute inset-0 z-10 grid place-items-center bg-white/70 backdrop-blur-[1px]"
            >
                <div class="flex flex-col items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-lg">
                    <span class="inline-block size-8 animate-spin rounded-full border-2 border-brand/25 border-t-brand"></span>
                    <p class="text-sm font-semibold text-ink">Buscando leads…</p>
                    <p class="text-xs text-ink-soft">Google Places + território</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app-pwa>
