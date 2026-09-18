@php
    $config = [
        'storeUrl' => route('app.visitas.store'),
        'receitaUrl' => route('app.receita.consultar'),
        'csrf' => csrf_token(),
        'receitaDriver' => config('prospecta.receita_ws.driver'),
    ];
@endphp

<x-layouts.app-pwa titulo="Check-in" passo="checkin">
    <x-slot:subtitulo>No local (≤100 m) · foto + áudio se visita feita</x-slot:subtitulo>

    @if ($googleMapsKey)
        <x-slot:head>
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&language=pt-BR" defer></script>
        </x-slot:head>
    @endif

    <div class="mx-auto max-w-lg space-y-3 pb-28" x-data="checkinCampo(@js($config))">
        <div
            class="rounded-2xl border border-surface-line bg-white p-5 text-sm text-ink-soft shadow-panel"
            x-show="!atual"
            x-cloak
        >
            Nenhuma parada pendente. Gere a rota primeiro.
            <a href="{{ route('app.rota') }}" class="mt-3 inline-flex font-semibold text-brand">Ir para rota →</a>
        </div>

        <div class="space-y-3" x-show="atual" x-cloak>
            {{-- Mapa + distância --}}
            <section class="relative overflow-hidden rounded-2xl border border-surface-line bg-white shadow-panel">
                <div x-ref="mapaMini" class="pwa-checkin-mapa w-full bg-surface-muted"></div>
                <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-3">
                    <span
                        class="rounded-full px-3 py-1.5 text-xs font-semibold shadow-sm backdrop-blur"
                        :class="noLocal
                            ? 'bg-emerald-500 text-white'
                            : 'bg-white/95 text-ink'"
                        x-text="distancia == null
                            ? (gps ? 'GPS…' : 'Ative o GPS')
                            : (noLocal
                                ? ('No local · ' + Math.round(distancia) + ' m')
                                : (Math.round(distancia) + ' m · máx. 100 m'))"
                    ></span>
                    <span
                        class="rounded-full bg-brand px-3 py-1.5 text-xs font-semibold text-white shadow-sm"
                        x-show="itens.length"
                        x-text="'Parada ' + (ordemAtual) + ' / ' + itens.length"
                    ></span>
                </div>
            </section>

            {{-- Empresa --}}
            <section class="rounded-2xl border border-surface-line bg-white p-4 shadow-panel">
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-ink-faint">Parada atual</p>
                <p class="mt-1 text-lg font-semibold leading-snug text-ink" x-text="atual?.razao_social"></p>
                <p class="mt-0.5 text-xs text-ink-faint" x-text="atual?.cnpj"></p>
                <p class="mt-1 text-sm text-ink-soft" x-text="atual?.endereco || ''"></p>

                <div class="mt-3 flex flex-wrap gap-2" x-show="atual?.guia_bolso || atual?.guia?.pitch" x-cloak>
                    <p class="w-full rounded-xl bg-brand-soft px-3 py-2 text-sm text-brand-strong" x-text="atual?.guia?.pitch || atual?.guia_bolso"></p>
                </div>
            </section>

            {{-- Upsell --}}
            <section
                class="space-y-2 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3"
                x-show="atual?.is_cliente"
                x-cloak
            >
                <p class="text-sm font-semibold text-amber-950">Cliente — leia o upsell antes</p>
                <template x-for="(obj, idx) in (atual?.guia?.objecoes || [])" :key="'c-obj-'+idx">
                    <details class="rounded-xl bg-white/90 px-3 py-2 text-sm">
                        <summary class="cursor-pointer font-medium" x-text="obj.titulo"></summary>
                        <p class="mt-1 text-ink-soft" x-text="obj.resposta"></p>
                    </details>
                </template>
                <a
                    x-show="atual?.guia?.ajuda_url"
                    :href="atual?.guia?.ajuda_url"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex text-sm font-semibold text-brand"
                >Ajuda Alterdata →</a>
                <label class="mt-1 flex items-start gap-2 text-sm text-amber-950">
                    <input type="checkbox" class="mt-1 rounded border-amber-400 text-brand" x-model="upsellAck">
                    <span>Li a oportunidade e posso checkar.</span>
                </label>
            </section>

            {{-- Resultado em botões grandes --}}
            <section class="rounded-2xl border border-surface-line bg-white p-4 shadow-panel">
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-ink-faint">Resultado</p>
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <button
                        type="button"
                        class="rounded-xl border px-3 py-3 text-sm font-semibold transition"
                        :class="status === 'SEM_NINGUEM' ? 'border-brand bg-brand text-white' : 'border-surface-line bg-surface-muted text-ink'"
                        @click="status = 'SEM_NINGUEM'"
                    >Não tinha ninguém</button>
                    <button
                        type="button"
                        class="rounded-xl border px-3 py-3 text-sm font-semibold transition"
                        :class="status === 'FEITA' ? 'border-brand bg-brand text-white' : 'border-surface-line bg-surface-muted text-ink'"
                        @click="status = 'FEITA'"
                    >Visita feita</button>
                    <button
                        type="button"
                        class="rounded-xl border px-3 py-3 text-sm font-semibold transition"
                        :class="status === 'RETORNO' ? 'border-brand bg-brand text-white' : 'border-surface-line bg-surface-muted text-ink'"
                        @click="status = 'RETORNO'"
                    >Agendar retorno</button>
                </div>
            </section>

            {{-- Provas --}}
            <section class="space-y-3 rounded-2xl border border-surface-line bg-white p-4 shadow-panel" x-show="status === 'FEITA' || status === 'RETORNO'" x-cloak>
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-ink-faint">
                    Prova <span x-show="status === 'FEITA'">(obrigatória)</span>
                </p>

                <div>
                    <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-brand/40 bg-brand-soft/50 px-4 py-5 text-center">
                        <span class="text-sm font-semibold text-brand-strong" x-text="foto ? 'Trocar foto da fachada' : 'Tirar foto da fachada'"></span>
                        <span class="text-xs text-ink-soft">Câmera traseira</span>
                        <input type="file" accept="image/*" capture="environment" class="sr-only" @change="onFoto($event)">
                    </label>
                    <img
                        x-show="fotoPreview"
                        :src="fotoPreview"
                        alt="Prévia da fachada"
                        class="mt-2 max-h-40 w-full rounded-xl object-cover"
                        x-cloak
                    >
                </div>

                <div class="flex flex-wrap items-center gap-2" x-show="status === 'FEITA'" x-cloak>
                    <button
                        type="button"
                        class="pwa-btn pwa-btn-secondary sm:w-auto"
                        :class="gravando ? 'pwa-btn-danger' : ''"
                        @click="toggleAudio()"
                        x-text="gravando ? 'Parar gravação' : (audioBlob ? 'Regravar áudio (15s)' : 'Gravar áudio (15s)')"
                    ></button>
                    <span class="text-xs font-medium text-emerald-700" x-show="audioBlob && !gravando" x-cloak>Áudio pronto</span>
                    <span class="text-xs font-medium text-rose-700" x-show="gravando" x-cloak>Gravando…</span>
                </div>
            </section>

            {{-- Receita colapsada --}}
            <details class="rounded-2xl border border-surface-line bg-white px-4 py-3 shadow-panel">
                <summary class="cursor-pointer text-sm font-semibold text-ink">Consultar CNPJ (Receita)</summary>
                <div class="mt-3 space-y-2">
                    <div class="flex gap-2">
                        <input type="text" x-model="cnpjConsulta" placeholder="00.000.000/0001-00" class="h-11 flex-1 rounded-xl border border-surface-line px-3 text-sm">
                        <button type="button" class="pwa-btn pwa-btn-secondary shrink-0 sm:w-auto" @click="consultarReceita()" :disabled="consultandoReceita" x-text="consultandoReceita ? '…' : 'OK'"></button>
                    </div>
                    <p class="text-sm text-ink-soft" x-show="receitaMsg" x-text="receitaMsg" x-cloak></p>
                </div>
            </details>

            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800" x-show="msg" x-text="msg" x-cloak></p>
            <p class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" x-show="erro" x-text="erro" x-cloak></p>
        </div>

        {{-- CTA fixo --}}
        <div
            class="fixed inset-x-0 bottom-[4.5rem] z-40 px-4 lg:bottom-6"
            x-show="atual"
            x-cloak
        >
            <div class="mx-auto max-w-lg rounded-2xl border border-white/60 bg-white/95 p-2 shadow-lg backdrop-blur">
                <button
                    type="button"
                    class="pwa-btn pwa-btn-primary"
                    :disabled="salvando || !noLocal || (atual?.is_cliente && !upsellAck)"
                    @click="salvar()"
                    x-text="salvando ? 'Salvando…' : (noLocal ? 'Salvar visita' : 'Aproxime-se do pin (100 m)')"
                ></button>
            </div>
        </div>
    </div>
</x-layouts.app-pwa>
