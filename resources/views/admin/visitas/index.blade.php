@php
    use App\Enums\StatusVisita;
    $rotuloStatus = [
        StatusVisita::Feita->value => 'Visita feita',
        StatusVisita::Retorno->value => 'Retorno',
        StatusVisita::SemNinguem->value => 'Sem ninguém',
    ];
@endphp

<x-layouts.admin titulo="Visitas">
    <x-slot:subtitulo>Check-ins do campo — foto, áudio e status ficam no Prospecta</x-slot:subtitulo>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Empresa ou CNPJ"
            class="h-10 min-w-[12rem] flex-1 rounded-2xl border-0 bg-white/90 px-4 text-sm shadow-sm focus:ring-2 focus:ring-brand/25"
        >
        <select name="status" class="h-10 rounded-2xl border-0 bg-white/90 px-3 text-sm shadow-sm focus:ring-2 focus:ring-brand/25">
            <option value="">Status</option>
            @foreach ($rotuloStatus as $valor => $label)
                <option value="{{ $valor }}" @selected(request('status') === $valor)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="vendedor_id" class="h-10 rounded-2xl border-0 bg-white/90 px-3 text-sm shadow-sm focus:ring-2 focus:ring-brand/25">
            <option value="">Vendedor</option>
            @foreach ($vendedores as $v)
                <option value="{{ $v->id }}" @selected(request('vendedor_id') == $v->id)>{{ $v->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="h-10 rounded-2xl bg-brand px-4 text-sm font-semibold text-white">Filtrar</button>
    </form>

    <div class="admin-table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-surface-line/80">
                    <tr>
                        <th class="px-5 py-3.5">Quando</th>
                        <th class="px-5 py-3.5">Empresa</th>
                        <th class="px-5 py-3.5">Vendedor</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Mídia</th>
                        <th class="px-5 py-3.5"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-line/70">
                    @forelse ($visitas as $visita)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3.5 text-ink-soft">
                                {{ $visita->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-ink">{{ $visita->prospecto?->razao_social ?? '—' }}</p>
                                <p class="text-xs text-ink-faint">{{ $visita->prospecto?->cnpj }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $visita->usuario?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex rounded-full bg-brand-soft px-2.5 py-0.5 text-xs font-semibold text-brand-strong">
                                    {{ $rotuloStatus[$visita->status?->value] ?? $visita->status?->value }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-ink-soft">
                                @if ($visita->caminho_foto)
                                    <span class="mr-2">Foto</span>
                                @endif
                                @if ($visita->caminho_audio)
                                    <span>Áudio</span>
                                @elseif (! $visita->caminho_foto)
                                    <span class="text-ink-faint">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.visitas.show', $visita) }}" class="font-medium text-brand hover:text-brand-strong">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-ink-soft">
                                Nenhuma visita registrada ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($visitas->hasPages())
            <div class="border-t border-surface-line/70 px-5 py-3">
                {{ $visitas->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
