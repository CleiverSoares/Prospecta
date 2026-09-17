<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Google\GooglePlacesClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PlaceDetalheController extends Controller
{
    public function __construct(
        private readonly GooglePlacesClient $googlePlacesClient,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $placeId = (string) $request->validate([
            'place_id' => ['required', 'string', 'max:255'],
        ])['place_id'];

        try {
            $detalhe = $this->googlePlacesClient->detalhesCompletos($placeId);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($detalhe === []) {
            return response()->json(['message' => 'Estabelecimento não encontrado no Google.'], 404);
        }

        return response()->json(['lugar' => $detalhe]);
    }
}
