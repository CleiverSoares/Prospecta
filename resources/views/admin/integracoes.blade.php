<x-layouts.admin titulo="Integrações">
    <x-slot:subtitulo>Conexões CRM — fase 1 só UI (API quando liberar)</x-slot:subtitulo>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($integracoes as $item)
            <div class="admin-panel rounded-xl p-5">
                <p class="text-base font-semibold text-ink">{{ $item['nome'] }}</p>
                <p class="mt-1 text-sm text-ink-soft">{{ $item['descricao'] }}</p>
                <button
                    type="button"
                    disabled
                    class="mt-4 inline-flex h-9 cursor-not-allowed items-center rounded-md bg-surface-muted px-3 text-sm font-semibold text-ink-faint"
                >Conectar via API · Em breve</button>
                <p class="mt-2 text-xs text-ink-faint">Payload futuro: cnpj, razão, endereço, status visita, foto.</p>
            </div>
        @endforeach

        <div class="admin-panel rounded-xl border-dashed p-5">
            <p class="text-base font-semibold text-ink">+ 70 apps via Pluga</p>
            <p class="mt-1 text-sm text-ink-soft">Expansão futura sem depender de TI da Alterdata no dia 1.</p>
        </div>
    </div>
</x-layouts.admin>
