<?php

namespace App\Services;

use App\Services\Mapbox\MapboxGeocodingClient;
use Illuminate\Validation\ValidationException;

class PoligonoCepService
{
    public function __construct(
        private readonly MapboxGeocodingClient $mapboxGeocodingClient,
    ) {}

    /**
     * Estima faixa de CEP a partir de um polígono GeoJSON (Polygon / Feature / FeatureCollection).
     *
     * @param  array<string, mixed>  $geojson
     * @return array{cep_inicio: string, cep_fim: string}
     */
    public function estimarFaixa(array $geojson): array
    {
        $pontos = $this->extrairPontosAmostra($geojson);

        if ($pontos === []) {
            throw ValidationException::withMessages([
                'poligono_geojson' => 'Polígono inválido ou vazio.',
            ]);
        }

        $ceps = [];

        foreach ($pontos as [$lon, $lat]) {
            $cep = $this->mapboxGeocodingClient->reverso($lon, $lat);

            if ($cep !== null) {
                $ceps[] = str_pad(substr($cep, 0, 8), 8, '0');
            }
        }

        $ceps = array_values(array_unique($ceps));
        sort($ceps);

        if ($ceps === []) {
            throw ValidationException::withMessages([
                'poligono_geojson' => 'Não foi possível obter CEPs para este polígono.',
            ]);
        }

        return [
            'cep_inicio' => $ceps[0],
            'cep_fim' => $ceps[array_key_last($ceps)],
        ];
    }

    /**
     * @param  array<string, mixed>  $geojson
     * @return list<array{0: float, 1: float}>
     */
    private function extrairPontosAmostra(array $geojson): array
    {
        $aneis = $this->extrairAneis($geojson);

        if ($aneis === []) {
            return [];
        }

        $coords = $aneis[0];
        $lons = [];
        $lats = [];

        foreach ($coords as $par) {
            if (! is_array($par) || count($par) < 2) {
                continue;
            }

            $lons[] = (float) $par[0];
            $lats[] = (float) $par[1];
        }

        if ($lons === [] || $lats === []) {
            return [];
        }

        $minLon = min($lons);
        $maxLon = max($lons);
        $minLat = min($lats);
        $maxLat = max($lats);
        $midLon = ($minLon + $maxLon) / 2;
        $midLat = ($minLat + $maxLat) / 2;

        return [
            [$minLon, $minLat],
            [$minLon, $maxLat],
            [$maxLon, $minLat],
            [$maxLon, $maxLat],
            [$midLon, $midLat],
        ];
    }

    /**
     * @param  array<string, mixed>  $geojson
     * @return list<list<array{0: float|int, 1: float|int}>>
     */
    private function extrairAneis(array $geojson): array
    {
        $tipo = $geojson['type'] ?? null;

        if ($tipo === 'FeatureCollection') {
            $features = $geojson['features'] ?? [];

            foreach ($features as $feature) {
                if (is_array($feature)) {
                    $aneis = $this->extrairAneis($feature);

                    if ($aneis !== []) {
                        return $aneis;
                    }
                }
            }

            return [];
        }

        if ($tipo === 'Feature') {
            $geometry = $geojson['geometry'] ?? null;

            return is_array($geometry) ? $this->extrairAneis($geometry) : [];
        }

        if ($tipo === 'Polygon') {
            $coords = $geojson['coordinates'] ?? [];

            return is_array($coords) ? $coords : [];
        }

        if ($tipo === 'MultiPolygon') {
            $primeiro = data_get($geojson, 'coordinates.0');

            return is_array($primeiro) ? $primeiro : [];
        }

        return [];
    }
}
