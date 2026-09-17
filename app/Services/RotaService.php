<?php

namespace App\Services;

use App\Models\Prospecto;
use Illuminate\Support\Collection;

class RotaService
{
    /**
     * Ordena prospectos pela regra vertical e aplica a janela de ouro.
     *
     * @param  iterable<int, Prospecto>  $prospectos
     * @param  array{lat: float, lng: float}|null  $origem  reservado (MVP ignora)
     * @param  array{segmento?: string, horas?: string, mix_prospeccao?: float}|null  $setup
     * @return array{itens: list<array<string, mixed>>, url_maps: string|null, avisos: list<string>}
     */
    public function gerar(iterable $prospectos, ?array $origem = null, ?int $limite = null, ?array $setup = null): array
    {
        unset($origem);

        $limite ??= (int) config('prospecta.rota.janela_ouro', 12);
        $faixa = (float) config('prospecta.rota.faixa_lng', 0.004);
        $setup ??= [];
        $avisos = [];

        $comCoordenadas = Collection::make($prospectos)
            ->filter(fn (Prospecto $p) => $p->lat !== null && $p->lng !== null)
            ->values();

        $ordenados = $this->ordenarVertical($comCoordenadas, $faixa);

        $gruposPredio = $this->contarGruposPredio($ordenados, $faixa);
        if ($gruposPredio > 0) {
            $avisos[] = "Prédio(s) com oportunidades agrupadas: {$gruposPredio} — 1 deslocamento.";
        }

        $segmento = strtoupper((string) ($setup['segmento'] ?? ''));
        if ($segmento === 'RESTAURANTE') {
            $avisos[] = 'Janela de ouro: evite 11:30–14:00 para restaurantes.';
        }
        if (in_array($segmento, ['CONTABIL', 'CONTÁBIL', 'CONTABILIDADE'], true)) {
            $dia = (int) now()->format('d');
            if ($dia >= 1 && $dia <= 5) {
                $avisos[] = 'Janela de ouro: contabilidade costuma estar fechada nos dias 01–05.';
            }
        }

        $mix = (float) ($setup['mix_prospeccao'] ?? 50);
        if ($mix >= 80) {
            $avisos[] = 'Mix ≥80% prospecção — priorize raio curto (~2 km).';
            $limite = min($limite, 8);
        }

        $ordenados = $ordenados->take(max(0, $limite))->values();

        $itens = $ordenados->map(fn (Prospecto $p, int $i) => [
            'ordem' => $i + 1,
            'id' => $p->id,
            'cnpj' => $p->cnpj,
            'razao_social' => $p->razao_social,
            'endereco' => $p->endereco,
            'telefone' => $p->telefone,
            'cep' => $p->cep,
            'lat' => $p->lat,
            'lng' => $p->lng,
            'origem' => $p->origem,
            'google_place_id' => $p->google_place_id,
            'is_cliente' => (bool) $p->is_cliente,
            'guia_bolso' => $p->is_cliente
                ? 'Cliente base — valide upsell (Pack / e-Contador) antes do check-in.'
                : $this->guiaPorSegmento($segmento),
        ])->all();

        return [
            'itens' => $itens,
            'url_maps' => $this->montarUrlMaps($itens),
            'avisos' => $avisos,
        ];
    }

    private function guiaPorSegmento(string $segmento): string
    {
        return match ($segmento) {
            'CONTABIL', 'CONTÁBIL', 'CONTABILIDADE' => 'Sugestão: Linha Pack — confirme porte e software atual.',
            'RESTAURANTE' => 'Sugestão: Spice — foque fluxo de caixa e delivery.',
            'VAREJO' => 'Sugestão: Pack PDV — confirme volume de NF-e.',
            default => 'Lead novo — confirme porte e decisor na recepção.',
        };
    }

    /**
     * @param  Collection<int, Prospecto>  $prospectos
     */
    private function contarGruposPredio(Collection $prospectos, float $faixaLng): int
    {
        return $prospectos
            ->groupBy(fn (Prospecto $p) => round($p->lat, 4).'|'.round($p->lng / max($faixaLng, 0.0001)))
            ->filter(fn (Collection $g) => $g->count() >= 3)
            ->count();
    }

    /**
     * @param  Collection<int, Prospecto>  $prospectos
     * @return Collection<int, Prospecto>
     */
    private function ordenarVertical(Collection $prospectos, float $faixaLng): Collection
    {
        if ($prospectos->isEmpty() || $faixaLng <= 0) {
            return $prospectos;
        }

        return $prospectos
            ->groupBy(fn (Prospecto $p) => (string) floor($p->lng / $faixaLng))
            ->sortKeys()
            ->flatMap(fn (Collection $grupo) => $grupo->sortByDesc(fn (Prospecto $p) => $p->lat)->values())
            ->values();
    }

    /**
     * @param  list<array{lat: float, lng: float}>  $itens
     */
    private function montarUrlMaps(array $itens): ?string
    {
        if ($itens === []) {
            return null;
        }

        $base = rtrim((string) config('prospecta.google.directions_base_url'), '/').'/';
        $pontos = array_map(
            fn (array $item) => $item['lat'].','.$item['lng'],
            $itens,
        );

        return $base.implode('/', $pontos);
    }
}
