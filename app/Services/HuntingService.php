<?php

namespace App\Services;

use App\Models\Prospecto;
use App\Models\User;
use App\Repositories\ProspectoRepository;
use App\Services\Google\GooglePlacesClient;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class HuntingService
{
    public function __construct(
        private readonly GooglePlacesClient $googlePlacesClient,
        private readonly TerritorioService $territorioService,
        private readonly ProspectoRepository $prospectoRepository,
        private readonly CercaTemporariaService $cercaTemporariaService,
    ) {}

    /**
     * @param  array{bairro?: ?string, cidade?: ?string, uf?: ?string, cep?: ?string, lat?: ?float, lng?: ?float, segmento?: ?string, horas?: ?string, poligono?: ?array}  $area
     * @return array{territorio: array<string, mixed>, prospectos: list<array<string, mixed>>, centro: array{lat: float, lng: float}, local_resolvido: string|null, avisos: list<string>, cerca?: array<string, mixed>|null}
     */
    public function prospectar(User $usuario, array $area): array
    {
        $coordenadas = $this->resolverCoordenadas($area);
        $cep = $this->resolverCep($area, $coordenadas);
        $avisos = [];

        if ($conflito = $this->cercaTemporariaService->conflitoComOutro($usuario, $cep)) {
            throw ValidationException::withMessages([
                'area' => 'Cerca temporária de outro vendedor até '.$conflito->expira_em->format('d/m/Y').'.',
            ]);
        }

        $territorio = $this->territorioService->verificarCep($usuario, $cep);

        if (! $territorio['permitido']) {
            throw ValidationException::withMessages([
                'area' => 'Área de outra unidade'.($territorio['unidade_nome'] ? ': '.$territorio['unidade_nome'] : '').'.',
            ]);
        }

        $segmento = $area['segmento'] ?? 'empresa';
        $consulta = $this->consultaPorSegmento((string) $segmento);
        $bairroFiltro = filled($area['bairro'] ?? null) ? trim((string) $area['bairro']) : null;
        $cidade = trim((string) ($area['cidade'] ?? ''));
        $uf = strtoupper(trim((string) ($area['uf'] ?? '')));
        // PWA usa 2000–3500; com bairro fechamos mais pra não vazar pra Várzea.
        $raio = (int) ($area['raio_metros'] ?? ($bairroFiltro ? 1200 : 2500));

        $lugares = [];
        if ($bairroFiltro !== null && $cidade !== '') {
            // Text Search amarra segmento + bairro + cidade (como o vendedor espera no PWA)
            $query = trim("{$consulta} {$bairroFiltro} {$cidade}".($uf !== '' ? " {$uf}" : ''));
            $lugares = $this->googlePlacesClient->buscarPorTexto(
                $query,
                $coordenadas['lat'],
                $coordenadas['lng'],
                $raio,
            );
        }
        if ($lugares === []) {
            $keyword = $bairroFiltro ? trim($consulta.' '.$bairroFiltro) : $consulta;
            $lugares = $this->googlePlacesClient->buscarNaArea(
                $keyword,
                $coordenadas['lat'],
                $coordenadas['lng'],
                $raio,
            );
        }

        $prospectos = [];

        foreach ($lugares as $lugar) {
            if (isset($area['poligono']) && is_array($area['poligono']) && ! $this->pontoNoPoligono($lugar['lng'], $lugar['lat'], $area['poligono'])) {
                continue;
            }

            $prospecto = $this->prospectoRepository->upsertPorGooglePlace($lugar['place_id'], [
                'cnpj' => 'G'.substr(md5($lugar['place_id']), 0, 13),
                'razao_social' => $lugar['nome'],
                'endereco' => $lugar['endereco'],
                'telefone' => $lugar['telefone'],
                'cep' => $cep,
                'lat' => $lugar['lat'],
                'lng' => $lugar['lng'],
                'status_receita' => 'DESCONHECIDO',
                'is_cliente' => false,
                'origem' => 'GOOGLE',
                'google_place_id' => $lugar['place_id'],
            ]);

            $serial = $this->serializar($prospecto);
            $serial['bairro'] = $lugar['bairro'] ?? null;
            $serial['rating'] = $lugar['rating'] ?? null;
            $serial['types'] = $lugar['types'] ?? [];
            $serial['website'] = $lugar['website'] ?? null;
            $serial['maps_url'] = $lugar['maps_url'] ?? null;
            $serial['aberto_agora'] = $lugar['aberto_agora'] ?? null;
            $serial['horarios'] = $lugar['horarios'] ?? [];
            $serial['status_negocio'] = $lugar['status_negocio'] ?? null;
            $serial['total_avaliacoes'] = $lugar['total_avaliacoes'] ?? null;
            $serial['resumo'] = $lugar['resumo'] ?? null;
            $serial['reviews'] = $lugar['reviews'] ?? [];
            $serial['foto'] = $lugar['foto'] ?? null;
            $serial['foto_thumb'] = $lugar['foto_thumb'] ?? null;
            $serial['fotos'] = $lugar['fotos'] ?? [];
            $prospectos[] = $serial;
        }

        if ($bairroFiltro !== null) {
            $bn = $this->normalizarTexto($bairroFiltro);
            $antes = count($prospectos);
            $prospectos = array_values(array_filter(
                $prospectos,
                fn (array $p): bool => $this->leadNoBairro($p, $bn),
            ));
            $fora = $antes - count($prospectos);
            if ($prospectos === []) {
                if ($antes > 0) {
                    $avisos[] = "Nenhum lead em “{$bairroFiltro}” — Google trouxe {$antes} de outros bairros e foram descartados.";
                } else {
                    $avisos[] = "Nada encontrado em “{$bairroFiltro}” ({$cidade}). Tente a cerca no mapa ou outro trecho.";
                }
            } elseif ($fora > 0) {
                $avisos[] = "Filtramos {$fora} lead(s) fora de “{$bairroFiltro}”.";
            }
        }

        $encontrados = count($prospectos);
        $limiteJanela = $this->limiteSugeridoPorHoras((string) ($area['horas'] ?? ''));
        if ($encontrados > $limiteJanela) {
            $prospectos = array_slice($prospectos, 0, $limiteJanela);
            $avisos[] = "Janela de horas sugere no máx. {$limiteJanela} parada(s) — mostramos as {$limiteJanela} mais próximas (Google achou {$encontrados}).";
        }

        $segNorm = strtoupper((string) $segmento);
        if (in_array($segNorm, ['RESTAURANTE'], true)) {
            $avisos[] = 'Segmento restaurante: na rota, visitas evitam 11:30–14:00 (almoço).';
        }
        if (in_array($segNorm, ['CONTABIL', 'CONTÁBIL', 'CONTABILIDADE'], true)) {
            $avisos[] = 'Segmento contábil: nos dias 01–05 do mês a rota não agenda (fechamento).';
        }

        $cerca = $this->cercaTemporariaService->reservar($usuario, [
            'rotulo' => trim(($area['bairro'] ?? '').' '.($area['cidade'] ?? '')) ?: 'Cerca do hunting',
            'cep_inicio' => $cep,
            'cep_fim' => $cep,
            'poligono_geojson' => $area['poligono'] ?? null,
        ]);

        return [
            'territorio' => $territorio,
            'prospectos' => $prospectos,
            'centro' => [
                'lat' => $coordenadas['lat'],
                'lng' => $coordenadas['lng'],
            ],
            'local_resolvido' => $coordenadas['endereco'] ?? null,
            'avisos' => $avisos,
            'consulta' => $consulta,
            'cerca' => [
                'id' => $cerca->id,
                'expira_em' => $cerca->expira_em->toIso8601String(),
                'dias' => (int) config('prospecta.cerca_dias', 30),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $area
     * @return array{lat: float, lng: float, endereco?: string}
     */
    private function resolverCoordenadas(array $area): array
    {
        // GPS explícito do vendedor (botão “Prospectar no meu GPS”)
        $origemGps = ! empty($area['origem_gps']);

        if ($origemGps && isset($area['lat'], $area['lng']) && is_numeric($area['lat']) && is_numeric($area['lng'])) {
            return [
                'lat' => (float) $area['lat'],
                'lng' => (float) $area['lng'],
                'endereco' => 'GPS '.number_format((float) $area['lat'], 4).', '.number_format((float) $area['lng'], 4),
            ];
        }

        // Pin do bairro confirmado no app (ex.: Meudon) — igual ao geocode do PWA no mapa
        $origemBairro = ! empty($area['origem_bairro']);
        $bairro = filled($area['bairro'] ?? null) ? trim((string) $area['bairro']) : null;
        if (
            $origemBairro
            && $bairro
            && isset($area['lat'], $area['lng'])
            && is_numeric($area['lat'])
            && is_numeric($area['lng'])
            && (abs((float) $area['lat']) > 0.01 || abs((float) $area['lng']) > 0.01)
        ) {
            $cidade = trim((string) ($area['cidade'] ?? ''));
            $uf = strtoupper(trim((string) ($area['uf'] ?? '')));

            return [
                'lat' => (float) $area['lat'],
                'lng' => (float) $area['lng'],
                'endereco' => trim($bairro.($cidade !== '' ? ", {$cidade}" : '').($uf !== '' ? " - {$uf}" : '')),
            ];
        }

        if (! empty($area['poligono']['coordinates'][0][0])) {
            $ring = $area['poligono']['coordinates'][0];
            $lat = collect($ring)->avg(fn ($p) => $p[1]);
            $lng = collect($ring)->avg(fn ($p) => $p[0]);

            return [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'endereco' => 'Cerca desenhada no mapa',
            ];
        }

        $cidade = trim((string) ($area['cidade'] ?? ''));
        $uf = strtoupper(trim((string) ($area['uf'] ?? '')));

        // Bairro/cidade: geocode no município (Google), sem cair no centro genérico.
        if ($cidade !== '' && strlen($uf) === 2) {
            $coords = app(\App\Services\Google\GoogleMapsClient::class)
                ->geocodificarNoMunicipio($bairro, $cidade, $uf);

            if ($coords !== null) {
                return $coords;
            }
        }

        if (isset($area['lat'], $area['lng']) && is_numeric($area['lat']) && is_numeric($area['lng'])) {
            return [
                'lat' => (float) $area['lat'],
                'lng' => (float) $area['lng'],
                'endereco' => 'Ponto '.number_format((float) $area['lat'], 4).', '.number_format((float) $area['lng'], 4),
            ];
        }

        $partes = array_filter([
            $bairro,
            $cidade !== '' ? $cidade : null,
            strlen($uf) === 2 ? $uf : null,
            ! empty($area['cep']) ? 'CEP '.$area['cep'] : null,
            'Brasil',
        ]);

        if ($partes === []) {
            throw ValidationException::withMessages([
                'area' => 'Informe bairro/CEP ou desenhe um polígono.',
            ]);
        }

        $texto = implode(', ', $partes);
        $coords = $this->googlePlacesClient->geocodificarTexto($texto);

        if ($coords === null) {
            throw ValidationException::withMessages([
                'area' => 'Não encontramos “'.$texto.'” no mapa. Confira bairro, cidade, UF ou CEP.',
            ]);
        }

        return $coords;
    }

    /**
     * Quantas paradas cabem na janela de horas (estimativa conservadora).
     */
    private function limiteSugeridoPorHoras(string $horas): int
    {
        $duracao = (int) config('prospecta.rota.duracao_visita_min', 30);
        $desloc = 15;
        $slot = max(20, $duracao + $desloc);
        $teto = (int) config('prospecta.rota.limite_paradas', 12);

        if (! preg_match('/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $horas, $m)) {
            return $teto;
        }

        $ini = ((int) $m[1] * 60) + (int) $m[2];
        $fim = ((int) $m[3] * 60) + (int) $m[4];
        if ($fim <= $ini) {
            return max(1, min(3, $teto));
        }

        $minutos = $fim - $ini;
        $cabem = (int) max(1, floor($minutos / $slot));

        return min($teto, $cabem);
    }

    /**
     * @param  array<string, mixed>  $area
     * @param  array{lat: float, lng: float}  $coordenadas
     */
    private function resolverCep(array $area, array $coordenadas): string
    {
        $cep = preg_replace('/\D+/', '', (string) ($area['cep'] ?? '')) ?? '';

        if (strlen($cep) >= 8) {
            return substr($cep, 0, 8);
        }

        $reverso = app(\App\Services\Google\GoogleMapsClient::class)
            ->cepReverso($coordenadas['lat'], $coordenadas['lng']);

        if ($reverso !== null) {
            return $reverso;
        }

        // Sem CEP: trata como área livre (nenhuma unidade cobre "00000000" tipicamente).
        return '00000000';
    }

    private function consultaPorSegmento(string $segmento): string
    {
        return match (strtoupper($segmento)) {
            'CONTABIL', 'CONTÁBIL', 'CONTABILIDADE' => 'contabilidade escritório contábil',
            'RESTAURANTE' => 'restaurante',
            'VAREJO' => 'loja varejo comércio',
            default => 'empresa comércio',
        };
    }

    private function normalizarTexto(string $texto): string
    {
        $sem = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $base = $sem !== false ? $sem : $texto;

        return mb_strtolower(trim($base));
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    private function leadNoBairro(array $lead, string $bairroNormalizado): bool
    {
        $bairroLead = $this->normalizarTexto((string) ($lead['bairro'] ?? ''));
        if ($bairroLead !== '') {
            if (
                $bairroLead === $bairroNormalizado
                || str_contains($bairroLead, $bairroNormalizado)
                || str_contains($bairroNormalizado, $bairroLead)
            ) {
                return true;
            }
            // Tem bairro explícito diferente (ex.: Várzea vs Meudon) → fora
            return false;
        }

        return str_contains(
            $this->normalizarTexto((string) ($lead['endereco'] ?? '')),
            $bairroNormalizado,
        );
    }

    /**
     * Ray casting — GeoJSON Polygon coordinates [lng, lat].
     *
     * @param  array{type?: string, coordinates?: array}  $poligono
     */
    private function pontoNoPoligono(float $lng, float $lat, array $poligono): bool
    {
        $ring = $poligono['coordinates'][0] ?? null;

        if (! is_array($ring) || count($ring) < 3) {
            return true;
        }

        $dentro = false;
        $j = count($ring) - 1;

        for ($i = 0; $i < count($ring); $i++) {
            $xi = (float) $ring[$i][0];
            $yi = (float) $ring[$i][1];
            $xj = (float) $ring[$j][0];
            $yj = (float) $ring[$j][1];

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersect) {
                $dentro = ! $dentro;
            }

            $j = $i;
        }

        return $dentro;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(Prospecto $prospecto): array
    {
        return [
            'id' => $prospecto->id,
            'cnpj' => $prospecto->cnpj,
            'razao_social' => $prospecto->razao_social,
            'endereco' => $prospecto->endereco,
            'telefone' => $prospecto->telefone,
            'cep' => $prospecto->cep,
            'lat' => $prospecto->lat,
            'lng' => $prospecto->lng,
            'is_cliente' => (bool) $prospecto->is_cliente,
            'origem' => $prospecto->origem,
            'google_place_id' => $prospecto->google_place_id,
            'guia_bolso' => $prospecto->is_cliente
                ? 'Cliente base — valide oportunidade de upsell antes do check-in.'
                : 'Lead novo — confirme porte e decisor na recepção.',
        ];
    }
}
