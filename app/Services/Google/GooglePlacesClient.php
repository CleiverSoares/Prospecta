<?php

namespace App\Services\Google;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GooglePlacesClient
{
    /**
     * @return list<array<string, mixed>>
     */
    public function buscarNaArea(string $consulta, float $lat, float $lng, int $raioMetros = 2500): array
    {
        $chave = $this->chave();

        try {
            $resposta = Http::withoutVerifying()
                ->timeout(15)
                ->acceptJson()
                ->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                    'location' => "{$lat},{$lng}",
                    'radius' => max(200, min(5000, $raioMetros)),
                    'keyword' => $consulta,
                    'language' => 'pt-BR',
                    'key' => $chave,
                ]);
        } catch (ConnectionException|Throwable $e) {
            throw new RuntimeException('Falha ao consultar Google Places: '.$e->getMessage(), 0, $e);
        }

        if (! $resposta->successful()) {
            throw new RuntimeException('Google Places retornou HTTP '.$resposta->status());
        }

        $status = (string) $resposta->json('status');

        if (! in_array($status, ['OK', 'ZERO_RESULTS'], true)) {
            throw new RuntimeException('Google Places: '.$status.' — '.($resposta->json('error_message') ?? 'erro'));
        }

        $itens = [];

        foreach ($resposta->json('results') ?? [] as $lugar) {
            $placeId = $lugar['place_id'] ?? null;
            $geo = $lugar['geometry']['location'] ?? null;

            if (! $placeId || ! is_array($geo)) {
                continue;
            }

            $detalhe = $this->detalhesCompletos((string) $placeId);

            $itens[] = array_merge([
                'place_id' => (string) $placeId,
                'nome' => (string) ($lugar['name'] ?? 'Empresa'),
                'endereco' => (string) ($lugar['vicinity'] ?? $lugar['formatted_address'] ?? ''),
                'telefone' => null,
                'rating' => isset($lugar['rating']) ? (float) $lugar['rating'] : null,
                'types' => (array) ($lugar['types'] ?? []),
                'lat' => (float) $geo['lat'],
                'lng' => (float) $geo['lng'],
            ], $detalhe, [
                'place_id' => (string) $placeId,
                'lat' => (float) ($detalhe['lat'] ?? $geo['lat']),
                'lng' => (float) ($detalhe['lng'] ?? $geo['lng']),
                'nome' => (string) ($detalhe['nome'] ?? $lugar['name'] ?? 'Empresa'),
                'endereco' => (string) ($detalhe['endereco'] ?? $lugar['vicinity'] ?? ''),
            ]);

            if (count($itens) >= 12) {
                break;
            }
        }

        return $itens;
    }

    /**
     * Detalhe enxuto (compat).
     *
     * @return array<string, mixed>
     */
    public function detalhes(string $placeId): array
    {
        return $this->detalhesCompletos($placeId);
    }

    /**
     * Campos ricos do Place Details (tudo que o vendedor precisa no pin).
     *
     * @return array<string, mixed>
     */
    public function detalhesCompletos(string $placeId): array
    {
        try {
            $resposta = Http::withoutVerifying()
                ->timeout(12)
                ->acceptJson()
                ->get('https://maps.googleapis.com/maps/api/place/details/json', [
                    'place_id' => $placeId,
                    'fields' => implode(',', [
                        'place_id',
                        'name',
                        'formatted_address',
                        'address_components',
                        'formatted_phone_number',
                        'international_phone_number',
                        'website',
                        'url',
                        'opening_hours',
                        'current_opening_hours',
                        'business_status',
                        'rating',
                        'user_ratings_total',
                        'price_level',
                        'types',
                        'geometry',
                        'editorial_summary',
                        'reviews',
                        'photos',
                    ]),
                    'language' => 'pt-BR',
                    'key' => $this->chave(),
                ]);
        } catch (Throwable) {
            return [];
        }

        if (! $resposta->successful() || $resposta->json('status') !== 'OK') {
            return [];
        }

        $r = $resposta->json('result') ?? [];
        $geo = $r['geometry']['location'] ?? [];
        $horarios = $r['current_opening_hours']['weekday_text']
            ?? $r['opening_hours']['weekday_text']
            ?? [];
        $abertoAgora = $r['current_opening_hours']['open_now']
            ?? $r['opening_hours']['open_now']
            ?? null;

        $reviews = [];
        foreach (array_slice($r['reviews'] ?? [], 0, 3) as $review) {
            $reviews[] = [
                'autor' => (string) ($review['author_name'] ?? ''),
                'nota' => isset($review['rating']) ? (float) $review['rating'] : null,
                'texto' => (string) ($review['text'] ?? ''),
                'relativo' => (string) ($review['relative_time_description'] ?? ''),
            ];
        }

        $cep = null;
        foreach ($r['address_components'] ?? [] as $comp) {
            if (in_array('postal_code', $comp['types'] ?? [], true)) {
                $cep = preg_replace('/\D+/', '', (string) ($comp['long_name'] ?? '')) ?: null;
                break;
            }
        }

        $fotos = [];
        foreach (array_slice($r['photos'] ?? [], 0, 4) as $foto) {
            $ref = $foto['photo_reference'] ?? null;
            if (! is_string($ref) || $ref === '') {
                continue;
            }
            $fotos[] = [
                'url' => $this->urlFoto($ref, 800),
                'url_thumb' => $this->urlFoto($ref, 240),
                'atribuicao' => (string) (($foto['html_attributions'][0] ?? '') ?: ''),
            ];
        }

        return [
            'place_id' => (string) ($r['place_id'] ?? $placeId),
            'nome' => isset($r['name']) ? (string) $r['name'] : null,
            'endereco' => isset($r['formatted_address']) ? (string) $r['formatted_address'] : null,
            'telefone' => $r['formatted_phone_number'] ?? $r['international_phone_number'] ?? null,
            'telefone_internacional' => $r['international_phone_number'] ?? null,
            'website' => $r['website'] ?? null,
            'maps_url' => $r['url'] ?? null,
            'status_negocio' => $r['business_status'] ?? null,
            'aberto_agora' => $abertoAgora,
            'horarios' => array_values($horarios),
            'rating' => isset($r['rating']) ? (float) $r['rating'] : null,
            'total_avaliacoes' => isset($r['user_ratings_total']) ? (int) $r['user_ratings_total'] : null,
            'faixa_preco' => isset($r['price_level']) ? (int) $r['price_level'] : null,
            'types' => array_values((array) ($r['types'] ?? [])),
            'resumo' => $r['editorial_summary']['overview'] ?? null,
            'reviews' => $reviews,
            'fotos' => $fotos,
            'foto' => $fotos[0]['url'] ?? null,
            'foto_thumb' => $fotos[0]['url_thumb'] ?? null,
            'cep' => $cep,
            'lat' => isset($geo['lat']) ? (float) $geo['lat'] : null,
            'lng' => isset($geo['lng']) ? (float) $geo['lng'] : null,
        ];
    }

    private function urlFoto(string $photoReference, int $maxWidth = 800): string
    {
        return 'https://maps.googleapis.com/maps/api/place/photo?'.http_build_query([
            'maxwidth' => max(100, min(1600, $maxWidth)),
            'photo_reference' => $photoReference,
            'key' => $this->chave(),
        ]);
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocodificarTexto(string $texto): ?array
    {
        return app(GoogleMapsClient::class)->geocodificar($texto);
    }

    private function chave(): string
    {
        $chave = config('prospecta.google.places_api_key') ?: config('prospecta.google.maps_api_key');

        if (! filled($chave)) {
            throw new RuntimeException('GOOGLE_PLACES_API_KEY / GOOGLE_MAPS_API_KEY não configurado.');
        }

        return (string) $chave;
    }
}
