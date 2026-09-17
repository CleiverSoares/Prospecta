<?php

namespace App\Services\Google;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleMapsClient
{
    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocodificar(string $endereco): ?array
    {
        $json = $this->geocodeRequest(['address' => $endereco]);

        $loc = data_get($json, 'results.0.geometry.location');

        if (! is_array($loc) || ! isset($loc['lat'], $loc['lng'])) {
            return null;
        }

        return [
            'lat' => (float) $loc['lat'],
            'lng' => (float) $loc['lng'],
        ];
    }

    /**
     * Reverse geocode → CEP (postcode) quando disponível.
     */
    public function cepReverso(float $lat, float $lng): ?string
    {
        $json = $this->geocodeRequest([
            'latlng' => "{$lat},{$lng}",
            'result_type' => 'postal_code',
        ]);

        foreach ($json['results'] ?? [] as $resultado) {
            foreach ($resultado['address_components'] ?? [] as $componente) {
                $tipos = $componente['types'] ?? [];

                if (in_array('postal_code', $tipos, true)) {
                    $digitos = preg_replace('/\D+/', '', (string) ($componente['long_name'] ?? '')) ?? '';

                    if (strlen($digitos) >= 5) {
                        return str_pad(substr($digitos, 0, 8), 8, '0');
                    }
                }
            }
        }

        // Fallback: qualquer result
        $json = $this->geocodeRequest(['latlng' => "{$lat},{$lng}"]);

        foreach ($json['results'] ?? [] as $resultado) {
            foreach ($resultado['address_components'] ?? [] as $componente) {
                $tipos = $componente['types'] ?? [];

                if (in_array('postal_code', $tipos, true)) {
                    $digitos = preg_replace('/\D+/', '', (string) ($componente['long_name'] ?? '')) ?? '';

                    if (strlen($digitos) >= 5) {
                        return str_pad(substr($digitos, 0, 8), 8, '0');
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function geocodeRequest(array $params): array
    {
        $chave = config('prospecta.google.maps_api_key');

        if (! filled($chave)) {
            throw new RuntimeException('GOOGLE_MAPS_API_KEY não configurado.');
        }

        try {
            $resposta = Http::withoutVerifying()
                ->timeout(10)
                ->acceptJson()
                ->get('https://maps.googleapis.com/maps/api/geocode/json', array_merge($params, [
                    'key' => $chave,
                    'language' => 'pt-BR',
                    'region' => 'br',
                ]));
        } catch (ConnectionException|Throwable) {
            return [];
        }

        if (! $resposta->successful()) {
            return [];
        }

        return $resposta->json() ?? [];
    }
}
