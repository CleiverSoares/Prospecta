@php
    $setupInicial = $setup ?? [];
@endphp

<x-layouts.app-pwa titulo="Setup do dia" passo="setup">
    <x-slot:subtitulo>Obrigatório — sem isso o app não libera área, rota nem check-in (RB04).</x-slot:subtitulo>

    @if (session('aviso'))
        <p class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ session('aviso') }}</p>
    @endif

    <div
        class="mx-auto max-w-2xl space-y-4 rounded-xl border border-surface-line bg-white p-4 shadow-panel sm:p-6"
        x-data="{
            local: @js($setupInicial['local'] ?? ''),
            segmento: @js($setupInicial['segmento'] ?? 'MISTO'),
            horas: @js($setupInicial['horas'] ?? '08:00-17:00'),
            mixProspeccao: @js($setupInicial['mix_prospeccao'] ?? 80),
            salvo: false,
            erro: '',
            salvando: false,
            storeUrl: @js(route('app.setup.store')),
            csrf: @js(csrf_token()),
            areaUrl: @js(route('app.area')),
            init() {
                try {
                    const d = JSON.parse(localStorage.getItem('prospecta.setup') || '{}');
                    if (!this.local && d.local) this.local = d.local;
                    if (d.segmento) this.segmento = d.segmento;
                    if (d.horas) this.horas = d.horas;
                    if (d.mixProspeccao != null) this.mixProspeccao = d.mixProspeccao;
                    if (navigator.geolocation && !this.local) {
                        navigator.geolocation.getCurrentPosition((pos) => {
                            this.local = pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4);
                        });
                    }
                } catch (e) {}
            },
            completo() {
                return this.local && this.segmento && this.horas && this.mixProspeccao !== null && this.mixProspeccao !== '';
            },
            persistirLocal() {
                localStorage.setItem('prospecta.setup', JSON.stringify({
                    local: this.local,
                    segmento: this.segmento,
                    horas: this.horas,
                    mixProspeccao: Number(this.mixProspeccao),
                    mixPosVenda: 100 - Number(this.mixProspeccao),
                }));
            },
            async salvar(irArea = false) {
                if (!this.completo() || this.salvando) return;
                this.salvando = true;
                this.erro = '';
                this.persistirLocal();
                try {
                    const res = await fetch(this.storeUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                        },
                        body: JSON.stringify({
                            local: this.local,
                            segmento: this.segmento,
                            horas: this.horas,
                            mix_prospeccao: Number(this.mixProspeccao),
                        }),
                    });
                    const dados = await res.json();
                    if (!res.ok) {
                        throw new Error(dados.message || Object.values(dados.errors || {}).flat()[0] || 'Falha ao salvar setup.');
                    }
                    this.salvo = true;
                    if (irArea) {
                        window.location = dados.redirect || this.areaUrl;
                        return;
                    }
                    setTimeout(() => this.salvo = false, 1500);
                } catch (e) {
                    this.erro = e.message || 'Erro ao salvar.';
                } finally {
                    this.salvando = false;
                }
            }
        }"
    >
        <label class="block space-y-1.5">
            <span class="text-sm font-medium text-ink">Local (GPS ou cidade/bairro)</span>
            <input x-model="local" type="text" class="h-11 w-full rounded-md border border-surface-line px-3 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20" placeholder="Ex.: Copacabana, Rio de Janeiro">
        </label>

        <label class="block space-y-1.5">
            <span class="text-sm font-medium text-ink">Segmento do dia</span>
            <select x-model="segmento" class="h-11 w-full rounded-md border border-surface-line px-3 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20">
                <option value="CONTABIL">Contábil</option>
                <option value="RESTAURANTE">Restaurante</option>
                <option value="VAREJO">Varejo</option>
                <option value="MISTO">Misto</option>
            </select>
        </label>

        <label class="block space-y-1.5">
            <span class="text-sm font-medium text-ink">Horas disponíveis</span>
            <input x-model="horas" type="text" class="h-11 w-full rounded-md border border-surface-line px-3 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20" placeholder="08:00-17:00">
        </label>

        <label class="block space-y-1.5">
            <span class="text-sm font-medium text-ink">Mix — Prospecção <span x-text="mixProspeccao"></span>%</span>
            <input x-model="mixProspeccao" type="range" min="0" max="100" step="5" class="w-full accent-[#0083C1]">
            <p class="text-xs text-ink-soft">Pós-venda: <span x-text="100 - Number(mixProspeccao)"></span>%</p>
        </label>

        <p class="text-sm text-emerald-700" x-show="salvo" x-cloak>Setup salvo no servidor. Pode prospectar.</p>
        <p class="text-sm text-rose-700" x-show="erro" x-text="erro" x-cloak></p>
        <p class="text-sm text-amber-800" x-show="!completo()" x-cloak>Preencha os 4 campos para liberar a rota.</p>

        <div class="flex flex-col gap-2 sm:flex-row">
            <button type="button" class="pwa-btn pwa-btn-primary sm:w-auto" :disabled="!completo() || salvando" @click="salvar(false)" x-text="salvando ? 'Salvando…' : 'Salvar setup'"></button>
            <button type="button" class="pwa-btn pwa-btn-ghost sm:w-auto" :disabled="!completo() || salvando" @click="salvar(true)">Onde prospectar hoje</button>
        </div>
    </div>
</x-layouts.app-pwa>
