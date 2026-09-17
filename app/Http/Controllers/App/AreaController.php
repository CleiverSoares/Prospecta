<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\ProspectarAreaRequest;
use App\Services\HuntingService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function __construct(
        private readonly HuntingService $huntingService,
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
}
