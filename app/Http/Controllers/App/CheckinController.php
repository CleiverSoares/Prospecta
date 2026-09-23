<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\SalvarVisitaRequest;
use App\Services\ArquivoMidiaService;
use App\Services\VisitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CheckinController extends Controller
{
    public function __construct(
        private readonly VisitaService $visitaService,
        private readonly ArquivoMidiaService $arquivoMidiaService,
    ) {}

    public function __invoke(): View
    {
        return view('app.checkin', [
            'googleMapsKey' => config('prospecta.google.maps_api_key'),
        ]);
    }

    public function store(SalvarVisitaRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $fotoPath = null;
        $audioPath = null;

        if ($request->hasFile('foto')) {
            $fotoPath = $this->arquivoMidiaService->salvar($request->file('foto'), 'visitas/fotos');
        }

        if ($request->hasFile('audio')) {
            $audioPath = $this->arquivoMidiaService->salvar($request->file('audio'), 'visitas/audios');
        }

        $visita = $this->visitaService->criar([
            'prospecto_id' => $dados['prospecto_id'],
            'user_id' => $request->user()->id,
            'status' => $dados['status'],
            'checkin_lat' => $dados['checkin_lat'] ?? null,
            'checkin_lng' => $dados['checkin_lng'] ?? null,
            'caminho_foto' => $fotoPath,
            'caminho_audio' => $audioPath,
        ]);

        return response()->json([
            'visita' => [
                'id' => $visita->id,
                'status' => $visita->status->value,
            ],
        ], 201);
    }
}
