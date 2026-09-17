@php
    $config = [
        'storeUrl' => route('app.visitas.store'),
        'csrf' => csrf_token(),
    ];
@endphp

<x-layouts.app-pwa titulo="Check-in" passo="checkin">
    <x-slot:subtitulo>Só libera a ≤100 m do pin. GPS + foto + áudio (visita feita).</x-slot:subtitulo>

    @if ($googleMapsKey)
        <x-slot:head>
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&language=pt-BR" defer></script>
        </x-slot:head>
    @endif

    <div class="mx-auto max-w-xl space-y-4" x-data="checkinCampo(@js($config))">
        <template x-if="!atual">
            <p class="rounded-xl border border-surface-line bg-white p-5 text-sm text-ink-soft">Nenhuma parada pendente. Gere a rota primeiro.</p>
        </template>

        <template x-if="atual">
            <div class="space-y-4">
                <div class="overflow-hidden rounded-xl border border-surface-line bg-white shadow-panel">
                    <div x-ref="mapaMini" class="h-48 w-full bg-surface-muted"></div>
                </div>

                <div class="space-y-4 rounded-xl border border-surface-line bg-white p-5 shadow-panel">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Parada atual</p>
                        <p class="mt-1 text-lg font-semibold text-ink" x-text="atual.razao_social"></p>
                        <p class="text-xs text-ink-faint" x-text="atual.cnpj"></p>
                        <p class="text-sm text-ink-soft" x-text="atual.endereco || atual.guia_bolso"></p>
                        <p
                            class="mt-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-950"
                            x-show="atual.is_cliente"
                            x-cloak
                        >Oportunidade de upsell — leia o guia antes do check-in.</p>
                    </div>

                    <p
                        class="rounded-md px-3 py-2 text-sm font-medium"
                        :class="noLocal ? 'border border-emerald-200 bg-emerald-50 text-emerald-800' : 'border border-rose-200 bg-rose-50 text-rose-800'"
                        x-text="distancia == null
                            ? (gps ? 'Calculando distância…' : 'Ative a localização')
                            : (noLocal
                                ? ('No local — ' + Math.round(distancia) + 'm do pin')
                                : ('Longe — ' + Math.round(distancia) + 'm (máx. 100m)'))"
                    ></p>

                    <label class="block space-y-1.5">
                        <span class="text-sm font-medium text-ink">Resultado</span>
                        <select x-model="status" class="h-11 w-full rounded-md border border-surface-line px-3 text-sm">
                            <option value="SEM_NINGUEM">Não tinha ninguém</option>
                            <option value="FEITA">Visita feita</option>
                            <option value="RETORNO">Agendado retorno</option>
                        </select>
                    </label>

                    <label class="block space-y-1.5">
                        <span class="text-sm font-medium text-ink">Foto da fachada</span>
                        <input type="file" accept="image/*" capture="environment" class="block w-full text-sm" @change="onFoto($event)">
                    </label>

                    <button type="button" class="pwa-btn pwa-btn-secondary" @click="toggleAudio()" x-text="gravando ? 'Parar áudio' : 'Gravar áudio (15s)'"></button>

                    <p class="text-sm text-emerald-700" x-show="msg" x-text="msg" x-cloak></p>
                    <p class="text-sm text-rose-700" x-show="erro" x-text="erro" x-cloak></p>

                    <button
                        type="button"
                        class="pwa-btn pwa-btn-primary"
                        :disabled="!noLocal"
                        @click="salvar()"
                    >Salvar visita</button>
                </div>
            </div>
        </template>
    </div>
</x-layouts.app-pwa>
