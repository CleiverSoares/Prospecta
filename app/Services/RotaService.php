<?php

namespace App\Services;

use App\Models\Prospecto;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RotaService
{
    public function __construct(
        private readonly GuiaBolsoService $guiaBolsoService,
    ) {}

    /**
     * @param  iterable<int, Prospecto>  $prospectos
     * @param  array{lat: float, lng: float}|null  $origem
     * @param  array{segmento?: string, horas?: string, mix_prospeccao?: float}|null  $setup
     * @return array{
     *     itens: list<array<string, mixed>>,
     *     url_maps: string|null,
     *     url_waze: string|null,
     *     avisos: list<string>,
     *     blocos: array{manha: array{inicio: string, fim: string}, almoco: array{inicio: string, fim: string}, tarde: array{inicio: string, fim: string}}
     * }
     */
    public function gerar(iterable $prospectos, ?array $origem = null, ?int $limite = null, ?array $setup = null): array
    {
        $setup ??= [];
        $avisos = [];
        $limite ??= (int) config('prospecta.rota.limite_paradas', config('prospecta.rota.janela_ouro', 12));
        $predioMetros = (float) config('prospecta.rota.predio_metros', 50);
        $segmento = $this->normalizarSegmento((string) ($setup['segmento'] ?? ''));
        $mix = (float) ($setup['mix_prospeccao'] ?? 50);
        $horas = (string) ($setup['horas'] ?? '08:00-17:00');

        [$inicioDia, $fimDia] = $this->parseHoras($horas);
        $almocoInicio = Carbon::parse($inicioDia->toDateString().' '.config('prospecta.rota.almoco_inicio', '12:00'));
        $almocoFim = $almocoInicio->copy()->addMinutes((int) config('prospecta.rota.almoco_duracao_min', 60));

        $blocos = [
            'manha' => ['inicio' => $inicioDia->format('H:i'), 'fim' => $almocoInicio->format('H:i')],
            'almoco' => ['inicio' => $almocoInicio->format('H:i'), 'fim' => $almocoFim->format('H:i')],
            'tarde' => ['inicio' => $almocoFim->format('H:i'), 'fim' => $fimDia->format('H:i')],
        ];

        if ($this->bloqueadoPorDiaSegmento($segmento)) {
            $avisos[] = 'Janela de ouro: contabilidade — não agenda nos dias 01–05 do mês.';

            return ['itens' => [], 'url_maps' => null, 'url_waze' => null, 'avisos' => $avisos, 'blocos' => $blocos];
        }

        $comCoordenadas = Collection::make($prospectos)
            ->filter(fn (Prospecto $p) => $p->lat !== null && $p->lng !== null)
            ->values();

        if ($origem !== null && $mix >= 80) {
            $raio = (float) config('prospecta.rota.raio_prospeccao_km', 2);
            $antes = $comCoordenadas->count();
            $comCoordenadas = $comCoordenadas
                ->filter(fn (Prospecto $p) => $this->haversineKm($origem['lat'], $origem['lng'], (float) $p->lat, (float) $p->lng) <= $raio)
                ->values();
            if ($comCoordenadas->count() < $antes) {
                $avisos[] = "Combustível: mix ≥80% — só leads em até {$raio} km da origem.";
            }
        } elseif ($origem !== null && $mix < 50) {
            $raio = (float) config('prospecta.rota.raio_pos_venda_km', 15);
            $comCoordenadas = $comCoordenadas
                ->filter(fn (Prospecto $p) => $this->haversineKm($origem['lat'], $origem['lng'], (float) $p->lat, (float) $p->lng) <= $raio)
                ->values();
        }

        $comCoordenadas = $this->aplicarMix($comCoordenadas, $mix, $limite);

        $grupos = $this->agruparPredios($comCoordenadas, $predioMetros);
        $gruposGrandes = $grupos->filter(fn (Collection $g) => $g->count() >= 3)->count();
        if ($gruposGrandes > 0) {
            $avisos[] = "Prédio(s) com oportunidades agrupadas: {$gruposGrandes} — 1 deslocamento.";
        }

        $ordenados = $this->ordenarGrupos($grupos, $origem);
        $agendados = $this->agendar(
            $ordenados,
            $origem,
            $inicioDia,
            $fimDia,
            $almocoInicio,
            $almocoFim,
            $segmento,
            $limite,
            $avisos,
        );

        $visitadosHoje = \App\Models\Visita::query()
            ->whereDate('created_at', today())
            ->pluck('prospecto_id')
            ->flip();

        $itens = [];
        foreach ($agendados as $i => $slot) {
            /** @var Prospecto $p */
            $p = $slot['prospecto'];
            $guia = $this->guiaBolsoService->para($segmento, (bool) $p->is_cliente);
            $itens[] = [
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
                'visitado' => $visitadosHoje->has($p->id),
                'guia_bolso' => $guia['pitch'],
                'guia' => $guia,
                'horario_estimado' => $slot['horario']->toIso8601String(),
                'bloco' => $slot['bloco'],
                'grupo_predio' => $slot['grupo_predio'],
            ];
        }

        return [
            'itens' => $itens,
            'url_maps' => $this->montarUrlMaps($itens),
            'url_waze' => $this->montarUrlWaze($itens),
            'avisos' => $avisos,
            'blocos' => $blocos,
        ];
    }

    /**
     * @param  Collection<int, Prospecto>  $prospectos
     * @return Collection<int, Prospecto>
     */
    private function aplicarMix(Collection $prospectos, float $mix, int $limite): Collection
    {
        if ($prospectos->isEmpty()) {
            return $prospectos;
        }

        $leads = $prospectos->filter(fn (Prospecto $p) => ! $p->is_cliente)->values();
        $clientes = $prospectos->filter(fn (Prospecto $p) => (bool) $p->is_cliente)->values();

        $qtdLeads = (int) round($limite * ($mix / 100));
        $qtdClientes = max(0, $limite - $qtdLeads);

        $selecionados = $leads->take($qtdLeads)
            ->concat($clientes->take($qtdClientes))
            ->values();

        if ($selecionados->count() < min($limite, $prospectos->count())) {
            $ids = $selecionados->pluck('id')->all();
            $faltam = min($limite, $prospectos->count()) - $selecionados->count();
            $selecionados = $selecionados
                ->concat($prospectos->reject(fn (Prospecto $p) => in_array($p->id, $ids, true))->take($faltam))
                ->values();
        }

        return $selecionados;
    }

    /**
     * @param  Collection<int, Prospecto>  $prospectos
     * @return Collection<int, Collection<int, Prospecto>>
     */
    private function agruparPredios(Collection $prospectos, float $metros): Collection
    {
        $lista = $prospectos->values();
        $n = $lista->count();
        $parent = range(0, max(0, $n - 1));

        $find = function (int $i) use (&$parent, &$find): int {
            if ($parent[$i] !== $i) {
                $parent[$i] = $find($parent[$i]);
            }

            return $parent[$i];
        };
        $union = function (int $a, int $b) use (&$parent, $find): void {
            $ra = $find($a);
            $rb = $find($b);
            if ($ra !== $rb) {
                $parent[$rb] = $ra;
            }
        };

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                /** @var Prospecto $a */
                $a = $lista[$i];
                /** @var Prospecto $b */
                $b = $lista[$j];
                $endA = $this->normalizarEndereco((string) $a->endereco);
                $endB = $this->normalizarEndereco((string) $b->endereco);
                $mesmo = $endA !== '' && $endA === $endB;
                $perto = $this->haversineMetros((float) $a->lat, (float) $a->lng, (float) $b->lat, (float) $b->lng) <= $metros;
                if ($mesmo || $perto) {
                    $union($i, $j);
                }
            }
        }

        /** @var array<int, Collection<int, Prospecto>> $buckets */
        $buckets = [];
        for ($i = 0; $i < $n; $i++) {
            $root = $find($i);
            $buckets[$root] ??= collect();
            $buckets[$root]->push($lista[$i]);
        }

        return collect(array_values($buckets));
    }

    /**
     * @param  Collection<int, Collection<int, Prospecto>>  $grupos
     * @param  array{lat: float, lng: float}|null  $origem
     * @return Collection<int, array{prospecto: Prospecto, grupo_predio: int}>
     */
    private function ordenarGrupos(Collection $grupos, ?array $origem): Collection
    {
        // Vizinho mais próximo a partir do GPS (ou do 1º ponto).
        // Prédios grandes NÃO pulam na frente — quem está do lado do vendedor vai primeiro.
        $restantes = $grupos->values()->all();
        $flat = collect();
        $lat = $origem['lat'] ?? null;
        $lng = $origem['lng'] ?? null;
        $nGrupo = 0;

        if ($lat === null || $lng === null) {
            // Sem GPS: oeste → leste pelo centróide (estável, sem “prédio grande primeiro”)
            usort($restantes, function (Collection $a, Collection $b): int {
                $lngA = (float) $a->avg(fn (Prospecto $p) => $p->lng);
                $lngB = (float) $b->avg(fn (Prospecto $p) => $p->lng);

                return $lngA <=> $lngB;
            });
            foreach ($restantes as $grupo) {
                $nGrupo++;
                foreach ($grupo->sortBy(fn (Prospecto $p) => (float) $p->lat)->values() as $p) {
                    $flat->push(['prospecto' => $p, 'grupo_predio' => $nGrupo]);
                }
            }

            return $flat;
        }

        while ($restantes !== []) {
            usort($restantes, function (Collection $a, Collection $b) use ($lat, $lng): int {
                $da = $this->distanciaGrupo($a, $lat, $lng);
                $db = $this->distanciaGrupo($b, $lat, $lng);
                if (abs($da - $db) < 0.01) {
                    // empate: grupo maior primeiro (ainda no mesmo quarteirão)
                    return $b->count() <=> $a->count();
                }

                return $da <=> $db;
            });

            /** @var Collection<int, Prospecto> $grupo */
            $grupo = array_shift($restantes);
            $nGrupo++;
            $pontos = $grupo->values()->all();

            while ($pontos !== []) {
                usort($pontos, function (Prospecto $a, Prospecto $b) use ($lat, $lng): int {
                    return $this->haversineKm($lat, $lng, (float) $a->lat, (float) $a->lng)
                        <=> $this->haversineKm($lat, $lng, (float) $b->lat, (float) $b->lng);
                });
                $p = array_shift($pontos);
                $flat->push(['prospecto' => $p, 'grupo_predio' => $nGrupo]);
                $lat = (float) $p->lat;
                $lng = (float) $p->lng;
            }
        }

        return $flat;
    }

    /**
     * @param  Collection<int, Prospecto>  $grupo
     */
    private function distanciaGrupo(Collection $grupo, float $lat, float $lng): float
    {
        $gLat = (float) $grupo->avg(fn (Prospecto $p) => $p->lat);
        $gLng = (float) $grupo->avg(fn (Prospecto $p) => $p->lng);

        return $this->haversineKm($lat, $lng, $gLat, $gLng);
    }

    /**
     * @param  Collection<int, array{prospecto: Prospecto, grupo_predio: int}>  $ordenados
     * @param  array{lat: float, lng: float}|null  $origem
     * @param  list<string>  $avisos
     * @return list<array{prospecto: Prospecto, grupo_predio: int, horario: Carbon, bloco: string}>
     */
    private function agendar(
        Collection $ordenados,
        ?array $origem,
        Carbon $inicioDia,
        Carbon $fimDia,
        Carbon $almocoInicio,
        Carbon $almocoFim,
        string $segmento,
        int $limite,
        array &$avisos,
    ): array {
        $duracao = (int) config('prospecta.rota.duracao_visita_min', 30);
        $velocidade = (float) config('prospecta.rota.velocidade_kmh', 20);
        $cursor = $inicioDia->copy();
        $prevLat = $origem['lat'] ?? null;
        $prevLng = $origem['lng'] ?? null;
        $slots = [];
        $janelaRest = $segmento === 'RESTAURANTE';

        if ($janelaRest) {
            $avisos[] = 'Janela de ouro: restaurante — visitas fora de 11:30–14:00.';
        }

        foreach ($ordenados as $item) {
            if (count($slots) >= $limite) {
                break;
            }

            /** @var Prospecto $p */
            $p = $item['prospecto'];

            if ($prevLat !== null && $prevLng !== null) {
                $km = $this->haversineKm($prevLat, $prevLng, (float) $p->lat, (float) $p->lng);
                $minDesloc = (int) max(5, ceil(($km / max($velocidade, 1)) * 60));
                $cursor->addMinutes($minDesloc);
            }

            $cursor = $this->empurrarForaDeBloqueios($cursor, $almocoInicio, $almocoFim, $janelaRest, $fimDia);
            if ($cursor === null || $cursor->gte($fimDia)) {
                $avisos[] = 'Dia cheio — demais leads ficaram de fora da janela de horas.';
                break;
            }

            $fimVisita = $cursor->copy()->addMinutes($duracao);
            if ($fimVisita->gt($fimDia)) {
                $avisos[] = 'Dia cheio — demais leads ficaram de fora da janela de horas.';
                break;
            }

            // Se a visita atravessa almoço / janela restaurante, empurra o início
            $cursor = $this->empurrarForaDeBloqueios($cursor, $almocoInicio, $almocoFim, $janelaRest, $fimDia);
            if ($cursor === null || $cursor->copy()->addMinutes($duracao)->gt($fimDia)) {
                break;
            }

            $bloco = $cursor->lt($almocoInicio) ? 'manha' : 'tarde';
            $slots[] = [
                'prospecto' => $p,
                'grupo_predio' => $item['grupo_predio'],
                'horario' => $cursor->copy(),
                'bloco' => $bloco,
            ];

            $cursor = $cursor->copy()->addMinutes($duracao);
            $prevLat = (float) $p->lat;
            $prevLng = (float) $p->lng;
        }

        return $slots;
    }

    private function empurrarForaDeBloqueios(
        Carbon $cursor,
        Carbon $almocoInicio,
        Carbon $almocoFim,
        bool $janelaRestaurante,
        Carbon $fimDia,
    ): ?Carbon {
        $c = $cursor->copy();

        // Almoço obrigatório
        if ($c->gte($almocoInicio) && $c->lt($almocoFim)) {
            $c = $almocoFim->copy();
        }

        // Restaurante: 11:30–14:00
        if ($janelaRestaurante) {
            $bloqIni = Carbon::parse($c->toDateString().' 11:30');
            $bloqFim = Carbon::parse($c->toDateString().' 14:00');
            if ($c->gte($bloqIni) && $c->lt($bloqFim)) {
                $c = $bloqFim->copy();
            }
        }

        if ($c->gte($fimDia)) {
            return null;
        }

        return $c;
    }

    private function bloqueadoPorDiaSegmento(string $segmento): bool
    {
        if (! in_array($segmento, ['CONTABIL', 'CONTABILIDADE'], true)) {
            return false;
        }

        $dia = (int) now()->format('d');

        return $dia >= 1 && $dia <= 5;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parseHoras(string $horas): array
    {
        $hoje = now()->toDateString();
        if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $horas, $m)) {
            return [
                Carbon::parse("{$hoje} {$m[1]}"),
                Carbon::parse("{$hoje} {$m[2]}"),
            ];
        }

        return [
            Carbon::parse("{$hoje} 08:00"),
            Carbon::parse("{$hoje} 17:00"),
        ];
    }

    private function normalizarSegmento(string $segmento): string
    {
        $s = mb_strtoupper(trim($segmento), 'UTF-8');
        $s = str_replace(['Á', 'À', 'Ã', 'Â'], 'A', $s);

        return match ($s) {
            'CONTABIL', 'CONTABILIDADE' => 'CONTABIL',
            'RESTAURANTE' => 'RESTAURANTE',
            'VAREJO' => 'VAREJO',
            default => $s,
        };
    }

    private function normalizarEndereco(string $endereco): string
    {
        $e = mb_strtolower(trim($endereco), 'UTF-8');
        $e = preg_replace('/\s+/', ' ', $e) ?? $e;

        return $e;
    }

    private function guiaPorSegmento(string $segmento): string
    {
        return $this->guiaBolsoService->para($segmento)['pitch'];
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->haversineMetros($lat1, $lng1, $lat2, $lng2) / 1000;
    }

    private function haversineMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);
        $a = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /**
     * @param  list<array{lat: float, lng: float}>  $itens
     */
    private function montarUrlWaze(array $itens): ?string
    {
        if ($itens === []) {
            return null;
        }

        $ultimo = $itens[array_key_last($itens)];

        return 'https://waze.com/ul?ll='.$ultimo['lat'].','.$ultimo['lng'].'&navigate=yes';
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
