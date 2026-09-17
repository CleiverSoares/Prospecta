<x-layouts.admin titulo="Integrações">
    <x-slot:subtitulo>CRM via API — UI pronta; conexão real quando liberar credenciais</x-slot:subtitulo>

    <div class="mb-4 rounded-2xl bg-white/80 px-4 py-3 text-sm text-ink-soft shadow-sm">
        Driver ReceitaWS atual: <strong class="text-ink">{{ config('prospecta.receita_ws.driver') }}</strong>
        · defina <code class="text-xs">RECEITA_WS_DRIVER=http</code> + URL/token no .env para produção.
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($integracoes as $item)
            <div class="admin-panel--soft rounded-2xl p-5" x-data="{ aberto: false }">
                <p class="text-base font-semibold text-ink">{{ $item['nome'] }}</p>
                <p class="mt-1 text-sm text-ink-soft">{{ $item['descricao'] }}</p>
                <button
                    type="button"
                    disabled
                    class="mt-4 inline-flex h-10 cursor-not-allowed items-center rounded-2xl bg-surface-muted px-4 text-sm font-semibold text-ink-faint"
                >Conectar via API · Em breve</button>
                <button type="button" class="mt-2 text-xs font-semibold text-brand" @click="aberto = !aberto" x-text="aberto ? 'Ocultar payload' : 'Ver payload futuro'"></button>
                <pre
                    x-show="aberto"
                    x-cloak
                    class="mt-2 overflow-x-auto rounded-xl bg-ink px-3 py-2 text-[11px] text-brand-soft"
                >{
  "cnpj": "00.000.000/0001-00",
  "razao_social": "Empresa Exemplo",
  "endereco": "Rua…",
  "visita_status": "FEITA",
  "foto_url": "https://…",
  "audio_url": "https://…",
  "vendedor_id": 1
}</pre>
            </div>
        @endforeach

        <div class="rounded-2xl border border-dashed border-brand/30 bg-brand-soft/40 p-5">
            <p class="text-base font-semibold text-ink">+ 70 apps via Pluga</p>
            <p class="mt-1 text-sm text-ink-soft">Webhooks genéricos depois das APIs diretas (Ploomes / RD / Active).</p>
        </div>
    </div>
</x-layouts.admin>
