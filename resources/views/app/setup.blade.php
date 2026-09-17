<x-layouts.app-pwa titulo="Setup do dia" passo="setup">
    <x-slot:subtitulo>Obrigatório — sem isso o app não gera rota (RB04).</x-slot:subtitulo>

    <div
        class="mx-auto max-w-2xl space-y-4 rounded-xl border border-surface-line bg-white p-4 shadow-panel sm:p-6"
        x-data="{
            local: '',
            segmento: 'MISTO',
            horas: '08:00-17:00',
            mixProspeccao: 80,
            salvo: false,
            init() {
                try {
                    const d = JSON.parse(localStorage.getItem('prospecta.setup') || '{}');
                    this.local = d.local || '';
                    this.segmento = d.segmento || 'MISTO';
                    this.horas = d.horas || '08:00-17:00';
                    this.mixProspeccao = d.mixProspeccao ?? 80;
                    if (navigator.geolocation && !this.local) {
                        navigator.geolocation.getCurrentPosition((pos) => {
                            this.local = pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4);
                        });
                    }
                } catch (e) {}
            },
            completo() {
                return this.local && this.segmento && this.horas && this.mixProspeccao !== null;
            },
            salvar() {
                if (!this.completo()) return;
                localStorage.setItem('prospecta.setup', JSON.stringify({
                    local: this.local,
                    segmento: this.segmento,
                    horas: this.horas,
                    mixProspeccao: Number(this.mixProspeccao),
                    mixPosVenda: 100 - Number(this.mixProspeccao),
                }));
                this.salvo = true;
                setTimeout(() => this.salvo = false, 1500);
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

        <p class="text-sm text-emerald-700" x-show="salvo" x-cloak>Setup salvo. Pode prospectar.</p>
        <p class="text-sm text-amber-800" x-show="!completo()" x-cloak>Preencha os 4 campos para liberar a rota.</p>

        <div class="flex flex-col gap-2 sm:flex-row">
            <button type="button" class="pwa-btn pwa-btn-primary sm:w-auto" :disabled="!completo()" @click="salvar()">Salvar setup</button>
            <a href="{{ route('app.area') }}" class="pwa-btn pwa-btn-ghost sm:w-auto" @click.prevent="if (!completo()) { alert('Complete o setup primeiro'); return; } salvar(); window.location='{{ route('app.area') }}'">Onde prospectar hoje</a>
        </div>
    </div>
</x-layouts.app-pwa>
