<?php

namespace App\Services\Google;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleMapsClient
{
    /**
     * @return array{lat: float, lng: float, endereco: string}|null
     */
    public function geocodificar(string $endereco): ?array
    {
        $json = $this->geocodeRequest(['address' => $endereco]);

        return $this->primeiroResultado($json, $endereco);
    }

    /**
     * Geocodifica bairro/cidade amarrado ao município — evita “Tijuca” cair no Rio
     * quando o usuário pediu Teresópolis.
     *
     * @return array{lat: float, lng: float, endereco: string}|null
     */
    public function geocodificarNoMunicipio(?string $bairro, string $cidade, string $uf): ?array
    {
        $uf = strtoupper(trim($uf));
        $cidade = trim($cidade);
        $bairro = filled($bairro) ? trim((string) $bairro) : null;

        if ($cidade === '' || strlen($uf) !== 2) {
            return null;
        }

        $texto = $bairro
            ? "{$bairro}, {$cidade}, {$uf}, Brasil"
            : "{$cidade}, {$uf}, Brasil";

        $tentativas = $bairro
            ? [
                $texto,
                "Bairro {$bairro}, {$cidade}, {$uf}, Brasil",
            ]
            : [$texto];

        foreach ($tentativas as $address) {
            $json = $this->geocodeRequest([
                'address' => $address,
                'components' => "country:BR|administrative_area:{$uf}|locality:{$cidade}",
            ]);

            foreach ($json['results'] ?? [] as $resultado) {
                if (! is_array($resultado)) {
                    continue;
                }
                if (! $this->resultadoNoMunicipio($resultado, $cidade, $uf)) {
                    continue;
                }
                // Com bairro: o resultado TEM que citar o bairro (senão cai no centro / Várzea).
                if ($bairro !== null && ! $this->resultadoMencionaBairro($resultado, $bairro)) {
                    continue;
                }

                $loc = data_get($resultado, 'geometry.location');
                if (! is_array($loc) || ! isset($loc['lat'], $loc['lng'])) {
                    continue;
                }

                return [
                    'lat' => (float) $loc['lat'],
                    'lng' => (float) $loc['lng'],
                    'endereco' => (string) ($resultado['formatted_address'] ?? $address),
                ];
            }
        }

        // Sem bairro: aceita centro do município. Com bairro: null (não chutar Várzea).
        if ($bairro === null) {
            $json = $this->geocodeRequest([
                'address' => $texto,
                'components' => "country:BR|administrative_area:{$uf}",
            ]);

            return $this->primeiroResultado($json, $texto);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resultado
     */
    private function resultadoMencionaBairro(array $resultado, string $bairro): bool
    {
        $bn = $this->normalizar($bairro);
        if ($bn === '') {
            return false;
        }

        foreach ($resultado['address_components'] ?? [] as $componente) {
            if (! is_array($componente)) {
                continue;
            }
            $tipos = $componente['types'] ?? [];
            $relevante = array_intersect($tipos, [
                'sublocality',
                'sublocality_level_1',
                'sublocality_level_2',
                'neighborhood',
                'political',
            ]);
            if ($relevante === []) {
                continue;
            }
            $nome = $this->normalizar((string) ($componente['long_name'] ?? ''));
            if ($nome === $bn || str_contains($nome, $bn) || (strlen($bn) >= 4 && str_contains($bn, $nome))) {
                return true;
            }
        }

        return str_contains($this->normalizar((string) ($resultado['formatted_address'] ?? '')), $bn);
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array{lat: float, lng: float, endereco: string}|null
     */
    private function primeiroResultado(array $json, string $fallbackEndereco): ?array
    {
        $loc = data_get($json, 'results.0.geometry.location');

        if (! is_array($loc) || ! isset($loc['lat'], $loc['lng'])) {
            return null;
        }

        return [
            'lat' => (float) $loc['lat'],
            'lng' => (float) $loc['lng'],
            'endereco' => (string) data_get($json, 'results.0.formatted_address', $fallbackEndereco),
        ];
    }

    /**
     * @param  array<string, mixed>  $resultado
     */
    private function resultadoNoMunicipio(array $resultado, string $cidade, string $uf): bool
    {
        $cidadeN = $this->normalizar($cidade);
        $ufN = strtoupper($uf);
        $localidade = null;
        $estado = null;

        foreach ($resultado['address_components'] ?? [] as $componente) {
            if (! is_array($componente)) {
                continue;
            }
            $tipos = $componente['types'] ?? [];
            if (in_array('locality', $tipos, true) || in_array('administrative_area_level_2', $tipos, true)) {
                $localidade = (string) ($componente['long_name'] ?? '');
            }
            if (in_array('administrative_area_level_1', $tipos, true)) {
                $estado = (string) ($componente['short_name'] ?? $componente['long_name'] ?? '');
            }
        }

        if ($estado !== null && strtoupper($estado) !== $ufN && $this->normalizar($estado) !== $this->normalizar($ufN)) {
            return false;
        }

        if ($localidade !== null) {
            $locN = $this->normalizar($localidade);

            return $locN === $cidadeN || str_contains($locN, $cidadeN) || str_contains($cidadeN, $locN);
        }

        $formatado = $this->normalizar((string) ($resultado['formatted_address'] ?? ''));

        return str_contains($formatado, $cidadeN) && str_contains($formatado, $this->normalizar($ufN));
    }

    private function normalizar(string $texto): string
    {
        $sem = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $base = $sem !== false ? $sem : $texto;

        return mb_strtolower(trim($base));
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
