<x-layouts.admin titulo="Integrações">
    <x-slot:subtitulo>CRM via API — UI pronta; conexão real quando liberar credenciais</x-slot:subtitulo>

    <div class="mb-4 rounded-2xl bg-white/80 px-4 py-3 text-sm text-ink-soft shadow-sm">
        Driver ReceitaWS atual: <strong class="text-ink">{{ config('prospecta.receita_ws.driver') }}</strong>
        · defina <code class="text-xs">RECEITA_WS_DRIVER=http</code> + URL/token no .env para produção.
    </div>

    <section class="admin-panel--soft mb-5 rounded-2xl p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-base font-semibold text-ink">Telegram</p>
                <p class="mt-1 text-sm text-ink-soft">
                    Quem mandar <code class="text-xs">/start</code> no bot passa a receber avisos automaticamente.
                    <code class="text-xs">/parar</code> cancela.
                </p>
            </div>
            @if ($telegram['habilitado'] && $telegram['tem_token'])
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Ligado</span>
            @else
                <span class="rounded-full bg-surface-muted px-3 py-1 text-xs font-semibold text-ink-faint">Desligado</span>
            @endif
        </div>
        <ul class="mt-3 space-y-1 text-xs text-ink-soft">
            <li>Inscritos ativos: <strong class="text-ink">{{ $telegram['inscritos'] }}</strong></li>
            <li>Token: {{ $telegram['tem_token'] ? 'configurado' : 'faltando TELEGRAM_BOT_TOKEN' }}</li>
            <li>Chat adm (fallback): {{ $telegram['tem_chat_adm'] ? 'ok' : 'opcional' }}</li>
            <li>Chat gestor (fallback): {{ $telegram['tem_chat_gestor'] ? 'ok' : 'opcional' }}</li>
            <li>Avisos: {{ $telegram['avisos']['fora'] ? 'fora território' : '—' }}{{ $telegram['avisos']['visita'] ? ' · visita feita' : '' }}</li>
        </ul>
        <p class="mt-3 text-[11px] text-ink-faint">
            Bot: <a class="text-brand font-semibold" href="https://t.me/Prospecta_avisos_bot" target="_blank" rel="noopener">t.me/Prospecta_avisos_bot</a>
            · webhook: <code>php artisan telegram:configurar-webhook</code>
        </p>
    </section>

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
