<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\ProspectarAreaRequest;
use App\Services\HuntingService;
use App\Services\IbgeService;
use App\Services\ViaCepService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function __construct(
        private readonly HuntingService $huntingService,
        private readonly IbgeService $ibgeService,
        private readonly ViaCepService $viaCepService,
    ) {}

    public function __invoke(): View
    {
        return view('app.area', [
            'googleMapsKey' => config('prospecta.google.maps_api_key'),
        ]);
    }

    public function prospectar(ProspectarAreaRequest $request): JsonResponse
    {
        $resultado = $this->huntingService->prospectar(
            $request->user(),
            $request->validated(),
        );

        return response()->json($resultado);
    }

    public function municipios(Request $request): JsonResponse
    {
        $uf = strtoupper((string) $request->query('uf', ''));

        if (strlen($uf) !== 2) {
            return response()->json(['municipios' => []]);
        }

        return response()->json([
            'municipios' => $this->ibgeService->municipiosPorUf($uf),
        ]);
    }

    public function bairros(Request $request): JsonResponse
    {
        $uf = strtoupper((string) $request->query('uf', ''));
        $cidade = trim((string) $request->query('cidade', ''));
        $q = trim((string) $request->query('q', ''));

        if (strlen($uf) !== 2 || $cidade === '' || mb_strlen($q) < 3) {
            return response()->json(['bairros' => []]);
        }

        return response()->json([
            'bairros' => $this->viaCepService->sugerirBairros($uf, $cidade, $q),
        ]);
    }
}
