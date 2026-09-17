<?php

namespace App\Services\Mapbox;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MapboxGeocodingClient
{
    public function reverso(float $longitude, float $latitude): ?string
    {
        $token = config('prospecta.mapbox.token');

        if (! filled($token)) {
            throw new RuntimeException('MAPBOX_ACCESS_TOKEN não configurado.');
        }

        $resposta = $this->http()
            ->get("https://api.mapbox.com/geocoding/v5/mapbox.places/{$longitude},{$latitude}.json", [
                'access_token' => $token,
                'types' => 'postcode',
                'limit' => 1,
                'language' => 'pt',
            ]);

        if (! $resposta->successful()) {
            return null;
        }

        $texto = data_get($resposta->json(), 'features.0.text')
            ?? data_get($resposta->json(), 'features.0.place_name');

        if (! is_string($texto) || $texto === '') {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', $texto);

        return ($digitos !== null && strlen($digitos) >= 5) ? substr($digitos, 0, 8) : null;
    }

    private function http(): PendingRequest
    {
        return Http::timeout(10)->acceptJson();
    }
}
