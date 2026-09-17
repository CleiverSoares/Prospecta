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
     * @param  array{bairro?: ?string, cidade?: ?string, uf?: ?string, cep?: ?string, lat?: ?float, lng?: ?float, segmento?: ?string, poligono?: ?array}  $area
     * @return array{territorio: array<string, mixed>, prospectos: list<array<string, mixed>>, cerca?: array<string, mixed>|null}
     */
    public function prospectar(User $usuario, array $area): array
    {
        $coordenadas = $this->resolverCoordenadas($area);
        $cep = $this->resolverCep($area, $coordenadas);

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
        $lugares = $this->googlePlacesClient->buscarNaArea(
            $consulta,
            $coordenadas['lat'],
            $coordenadas['lng'],
            (int) ($area['raio_metros'] ?? 2500),
        );

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

        $cerca = $this->cercaTemporariaService->reservar($usuario, [
            'rotulo' => trim(($area['bairro'] ?? '').' '.($area['cidade'] ?? '')) ?: 'Cerca do hunting',
            'cep_inicio' => $cep,
            'cep_fim' => $cep,
            'poligono_geojson' => $area['poligono'] ?? null,
        ]);

        return [
            'territorio' => $territorio,
            'prospectos' => $prospectos,
            'centro' => $coordenadas,
            'cerca' => [
                'id' => $cerca->id,
                'expira_em' => $cerca->expira_em->toIso8601String(),
                'dias' => (int) config('prospecta.cerca_dias', 30),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $area
     * @return array{lat: float, lng: float}
     */
    private function resolverCoordenadas(array $area): array
    {
        if (isset($area['lat'], $area['lng']) && is_numeric($area['lat']) && is_numeric($area['lng'])) {
            return ['lat' => (float) $area['lat'], 'lng' => (float) $area['lng']];
        }

        if (! empty($area['poligono']['coordinates'][0][0])) {
            $ring = $area['poligono']['coordinates'][0];
            $lat = collect($ring)->avg(fn ($p) => $p[1]);
            $lng = collect($ring)->avg(fn ($p) => $p[0]);

            return ['lat' => (float) $lat, 'lng' => (float) $lng];
        }

        $partes = array_filter([
            $area['bairro'] ?? null,
            $area['cidade'] ?? null,
            $area['uf'] ?? null,
            ! empty($area['cep']) ? 'CEP '.$area['cep'] : null,
            'Brasil',
        ]);

        if ($partes === []) {
            throw ValidationException::withMessages([
                'area' => 'Informe bairro/CEP ou desenhe um polígono.',
            ]);
        }

        $coords = $this->googlePlacesClient->geocodificarTexto(implode(', ', $partes));

        if ($coords === null) {
            throw ValidationException::withMessages([
                'area' => 'Não foi possível localizar essa área no mapa.',
            ]);
        }

        return $coords;
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
