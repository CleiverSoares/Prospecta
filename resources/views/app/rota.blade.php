@php
    $config = [
        'gerarUrl' => route('app.rota.gerar'),
        'detalheUrl' => route('app.places.detalhe'),
        'csrf' => csrf_token(),
    ];
@endphp

<x-layouts.app-pwa titulo="Rota do dia" passo="rota">
    <x-slot:subtitulo>
        <span class="hidden sm:inline">Toque no pin ou no card. Carro = você. Navegue na tela ou no Maps.</span>
        <span class="sm:hidden">Toque no pin · carro = você</span>
    </x-slot:subtitulo>

    @if ($googleMapsKey)
        <x-slot:head>
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&language=pt-BR" defer></script>
        </x-slot:head>
    @endif

    <div class="flex flex-col gap-4 lg:grid lg:grid-cols-12 lg:items-start" x-data="rotaDia(@js($config))">
        {{-- Mapa: primeiro no mobile, direita no desktop --}}
        <section class="relative order-1 overflow-hidden rounded-2xl border border-surface-line bg-white shadow-panel lg:order-2 lg:col-span-7 lg:sticky lg:top-24">
            <div x-ref="mapa" class="pwa-map pwa-rota-mapa-el w-full"></div>
            <div class="pointer-events-none absolute inset-x-0 top-0 flex justify-between p-3 lg:hidden" x-show="gps || itens.length" x-cloak>
                <span class="pointer-events-auto rounded-full bg-white/95 px-3 py-1.5 text-xs font-semibold text-ink shadow-sm backdrop-blur" x-show="itens.length" x-text="itens.length + ' paradas'"></span>
                <span class="pointer-events-auto rounded-full bg-brand px-3 py-1.5 text-xs font-semibold text-white shadow-sm" x-show="gps" x-cloak>GPS ok</span>
            </div>
        </section>

        {{-- Coluna esquerda / conteúdo --}}
        <div class="order-2 space-y-3 lg:order-1 lg:col-span-5">
            <section class="pwa-btn-row-3">
                <button
                    type="button"
                    class="pwa-btn col-span-2 sm:col-span-1"
                    :class="seguirNoMapa ? 'pwa-btn-primary' : 'pwa-btn-secondary'"
                    @click="toggleSeguir()"
                    x-text="seguirNoMapa ? 'Parar mapa' : 'Seguir no mapa'"
                ></button>
                <a
                    x-show="urlMaps"
                    x-cloak
                    :href="urlMaps"
                    target="_blank"
                    rel="noopener"
                    class="pwa-btn pwa-btn-ghost"
                >Google Maps</a>
                <a href="{{ route('app.checkin') }}" class="pwa-btn pwa-btn-primary">Check-in</a>
            </section>

            <p class="rounded-xl border border-brand/20 bg-brand-soft px-3 py-2 text-sm text-brand-strong" x-show="status" x-text="status" x-cloak></p>
            <template x-for="aviso in avisos" :key="aviso">
                <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" x-text="aviso" x-cloak></p>
            </template>
            <p class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" x-show="erro" x-text="erro" x-cloak></p>

            {{-- Strip mobile --}}
            <section class="lg:hidden" x-show="itens.length" x-cloak>
                <div class="mb-2 flex items-center justify-between px-0.5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Paradas</p>
                    <p class="text-xs text-ink-soft" x-text="selecionado ? 'Detalhe aberto ↓' : 'Deslize e toque'"></p>
                </div>
                <div class="pwa-rota-strip -mx-4 flex gap-3 overflow-x-auto px-4 pb-2">
                    <template x-for="item in itens" :key="'m-' + item.id">
                        <button
                            type="button"
                            class="pwa-rota-strip-card shrink-0 snap-start overflow-hidden rounded-2xl border bg-white text-left shadow-panel"
                            :class="selecionado?.id === item.id ? 'border-brand ring-2 ring-brand/25' : 'border-surface-line'"
                            @click="focarItem(item)"
                        >
                            <div class="relative h-24 w-[9.5rem] bg-surface-muted">
                                <img
                                    x-show="item.foto_thumb || item.foto"
                                    :src="item.foto_thumb || item.foto"
                                    alt=""
                                    class="size-full object-cover"
                                    loading="lazy"
                                >
                                <div
                                    class="absolute inset-0 flex items-center justify-center"
                                    x-show="!(item.foto_thumb || item.foto)"
                                    :class="item.is_cliente ? 'bg-brand/15' : 'bg-rose-50'"
                                >
                                    <span class="text-2xl font-bold text-ink-faint" x-text="item.ordem"></span>
                                </div>
                                <span
                                    class="absolute left-2 top-2 flex size-7 items-center justify-center rounded-full text-xs font-bold text-white shadow"
                                    :class="item.is_cliente ? 'bg-brand' : 'bg-rose-600'"
                                    x-text="item.ordem"
                                ></span>
                            </div>
                            <div class="w-[9.5rem] space-y-0.5 p-2.5">
                                <p class="truncate text-sm font-semibold text-ink" x-text="item.razao_social"></p>
                                <p class="truncate text-[11px] font-medium text-brand" x-show="item.horario_estimado" x-text="horarioCurto(item.horario_estimado) + (item.bloco ? ' · ' + item.bloco : '')"></p>
                                <p class="truncate text-[11px] text-ink-soft" x-text="item.endereco || item.guia_bolso"></p>
                            </div>
                        </button>
                    </template>
                </div>
            </section>

            {{-- Lista desktop --}}
            <section class="hidden space-y-2 lg:block" x-show="itens.length" x-cloak>
                <template x-for="item in itens" :key="'d-' + item.id">
                    <article
                        class="cursor-pointer rounded-xl border bg-white p-3 shadow-panel transition"
                        :class="selecionado?.id === item.id ? 'border-brand ring-2 ring-brand/20' : 'border-surface-line hover:border-brand/40'"
                        @click="focarItem(item)"
                    >
                        <div class="flex gap-3">
                            <div class="relative size-14 shrink-0 overflow-hidden rounded-lg bg-surface-muted">
                                <img x-show="item.foto_thumb || item.foto" :src="item.foto_thumb || item.foto" alt="" class="size-full object-cover">
                                <span
                                    class="absolute inset-0 flex items-center justify-center text-sm font-semibold text-white"
                                    :class="item.is_cliente ? 'bg-brand/80' : 'bg-rose-600/80'"
                                    x-show="!(item.foto_thumb || item.foto)"
                                    x-text="item.ordem"
                                ></span>
                                <span
                                    class="absolute bottom-0.5 left-0.5 flex size-5 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                    :class="item.is_cliente ? 'bg-brand' : 'bg-rose-600'"
                                    x-text="item.ordem"
                                    x-show="item.foto_thumb || item.foto"
                                ></span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink" x-text="item.razao_social"></p>
                                <p class="mt-0.5 truncate text-xs font-medium text-brand" x-show="item.horario_estimado" x-text="horarioCurto(item.horario_estimado) + (item.bloco ? ' · ' + item.bloco : '')"></p>
                                <p class="mt-0.5 truncate text-xs text-ink-faint" x-text="item.cnpj"></p>
                                <p class="mt-0.5 truncate text-xs text-ink-soft" x-text="item.endereco || item.guia_bolso"></p>
                                <p class="mt-1 text-xs text-brand" x-show="item.telefone" x-text="item.telefone" x-cloak></p>
                            </div>
                        </div>
                    </article>
                </template>
            </section>

            {{-- Detalhe: sheet no mobile, card estático no desktop --}}
            <div
                class="pwa-rota-sheet"
                x-show="selecionado"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full opacity-0 lg:translate-y-0 lg:opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-full opacity-0 lg:translate-y-0 lg:opacity-0"
            >
                <div class="pwa-rota-sheet-handle lg:hidden" aria-hidden="true"></div>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand">Estabelecimento</p>
                        <p class="mt-1 text-lg font-semibold leading-snug text-ink" x-text="selecionado?.razao_social || selecionado?.nome"></p>
                        <p class="mt-1 text-sm text-ink-soft" x-text="selecionado?.endereco"></p>
                    </div>
                    <button
                        type="button"
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-surface-muted text-lg leading-none text-ink-soft"
                        @click="fecharDetalhe()"
                        aria-label="Fechar"
                    >×</button>
                </div>

                <div class="mt-3 overflow-hidden rounded-xl bg-surface-muted" x-show="selecionado?.foto || selecionado?.foto_thumb || selecionado?.fotos?.length" x-cloak>
                    <img
                        :src="selecionado?.foto || selecionado?.foto_thumb || selecionado?.fotos?.[0]?.url"
                        :alt="selecionado?.razao_social || 'Foto'"
                        class="h-44 w-full object-cover lg:h-40"
                        loading="lazy"
                    >
                </div>
                <div class="mt-2 flex gap-2 overflow-x-auto pb-1" x-show="selecionado?.fotos?.length > 1" x-cloak>
                    <template x-for="(f, fi) in (selecionado?.fotos || []).slice(0, 4)" :key="fi">
                        <button type="button" class="shrink-0 overflow-hidden rounded-lg border border-surface-line" @click="selecionado.foto = f.url">
                            <img :src="f.url_thumb || f.url" alt="" class="size-16 object-cover">
                        </button>
                    </template>
                </div>

                <p class="mt-2 text-xs text-ink-faint" x-show="carregandoDetalhe" x-cloak>Buscando dados no Google…</p>

                <div class="mt-3 space-y-3" x-show="!carregandoDetalhe">
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-surface-muted px-3 py-1 text-xs font-medium text-ink" x-show="selecionado?.rating != null">
                            ★ <span x-text="selecionado?.rating"></span>
                            <span x-show="selecionado?.total_avaliacoes" x-text="'(' + selecionado?.total_avaliacoes + ')'"></span>
                        </span>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            x-show="selecionado?.aberto_agora != null"
                            :class="selecionado?.aberto_agora ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800'"
                            x-text="selecionado?.aberto_agora ? 'Aberto agora' : 'Fechado agora'"
                        ></span>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <a
                            x-show="selecionado?.telefone"
                            class="pwa-btn pwa-btn-primary"
                            :href="'tel:' + selecionado?.telefone"
                            x-text="selecionado?.telefone"
                        ></a>
                        <a
                            x-show="selecionado?.maps_url || selecionado?.google_place_id || selecionado?.place_id"
                            class="pwa-btn pwa-btn-ghost"
                            :href="selecionado?.maps_url || ('https://www.google.com/maps/search/?api=1&query_place_id=' + (selecionado?.google_place_id || selecionado?.place_id))"
                            target="_blank"
                            rel="noopener"
                        >Abrir no Maps</a>
                    </div>

                    <p class="text-sm text-ink-soft" x-show="selecionado?.resumo" x-text="selecionado?.resumo"></p>
                    <p class="text-sm font-medium text-brand-strong" x-show="selecionado?.guia_bolso" x-text="selecionado?.guia_bolso"></p>
                    <p class="text-xs text-ink-faint" x-show="selecionado?.cnpj">ID: <span x-text="selecionado?.cnpj"></span></p>
                </div>
            </div>
        </div>

        <div
            class="pwa-rota-backdrop lg:hidden"
            x-show="selecionado"
            x-cloak
            @click="fecharDetalhe()"
        ></div>
    </div>
</x-layouts.app-pwa>
