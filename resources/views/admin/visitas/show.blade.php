@php
    use App\Enums\StatusVisita;
    $rotuloStatus = [
        StatusVisita::Feita->value => 'Visita feita',
        StatusVisita::Retorno->value => 'Retorno agendado',
        StatusVisita::SemNinguem->value => 'Não tinha ninguém',
    ];
@endphp

<x-layouts.admin :titulo="$visita->prospecto?->razao_social ?? 'Visita'">
    <x-slot:subtitulo>
        Check-in {{ $visita->created_at?->format('d/m/Y H:i') }} · {{ $visita->usuario?->name }}
    </x-slot:subtitulo>

    <x-slot:acoes>
        <a href="{{ route('admin.visitas.index') }}" class="text-sm font-semibold text-brand hover:text-brand-strong">← Voltar</a>
    </x-slot:acoes>

    <div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
        <section class="admin-panel rounded-2xl p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Registro</p>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-ink-faint">Status</dt>
                    <dd class="mt-0.5 font-semibold text-ink">{{ $rotuloStatus[$visita->status?->value] ?? $visita->status?->value }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-faint">Vendedor</dt>
                    <dd class="mt-0.5 text-ink">{{ $visita->usuario?->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-faint">CNPJ</dt>
                    <dd class="mt-0.5 font-mono text-sm text-ink">{{ $visita->prospecto?->cnpj }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-faint">GPS check-in</dt>
                    <dd class="mt-0.5 text-sm text-ink">
                        @if ($visita->checkin_lat)
                            {{ number_format($visita->checkin_lat, 5) }}, {{ number_format($visita->checkin_lng, 5) }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-ink-faint">Endereço</dt>
                    <dd class="mt-0.5 text-ink-soft">{{ $visita->prospecto?->endereco ?: '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="space-y-4">
            <div class="admin-panel overflow-hidden rounded-2xl">
                <div class="border-b border-surface-line/70 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Foto da fachada</p>
                </div>
                @if ($visita->caminho_foto)
                    <img
                        src="{{ route('admin.visitas.foto', $visita) }}"
                        alt="Foto da fachada"
                        class="max-h-[22rem] w-full object-cover"
                    >
                @else
                    <p class="px-4 py-10 text-center text-sm text-ink-faint">Sem foto neste check-in.</p>
                @endif
            </div>

            <div class="admin-panel rounded-2xl p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Áudio do resumo</p>
                @if ($visita->caminho_audio)
                    <audio class="mt-3 w-full" controls src="{{ route('admin.visitas.audio', $visita) }}"></audio>
                @else
                    <p class="mt-3 text-sm text-ink-faint">Sem áudio neste check-in.</p>
                @endif
            </div>
        </section>
    </div>
</x-layouts.admin>
