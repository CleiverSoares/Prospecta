<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ConsultaReceitaInterface;
use App\Enums\StatusReceita;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\BuscarProspectoRequest;
use App\Http\Requests\App\ConsultarReceitaRequest;
use App\Http\Requests\App\GerarRotaRequest;
use App\Http\Requests\App\ProspectarAreaRequest;
use App\Http\Requests\App\ReservarCercaRequest;
use App\Http\Requests\App\SalvarLocalizacaoRequest;
use App\Http\Requests\App\SalvarSetupRequest;
use App\Http\Requests\App\SalvarVisitaRequest;
use App\Http\Requests\App\VerificarTerritorioRequest;
use App\Repositories\ProspectoRepository;
use App\Services\ArquivoMidiaService;
use App\Services\CercaTemporariaService;
use App\Services\Google\GooglePlacesClient;
use App\Services\HuntingService;
use App\Services\IbgeService;
use App\Services\LocalizacaoService;
use App\Services\ProspectoService;
use App\Services\RotaDiaService;
use App\Services\RotaService;
use App\Services\SetupDiaService;
use App\Services\TerritorioService;
use App\Services\ViaCepService;
use App\Services\VisitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CampoApiController extends Controller
{
    public function __construct(
        private readonly SetupDiaService $setupDiaService,
        private readonly HuntingService $huntingService,
        private readonly IbgeService $ibgeService,
        private readonly ViaCepService $viaCepService,
        private readonly TerritorioService $territorioService,
        private readonly ProspectoService $prospectoService,
        private readonly RotaService $rotaService,
        private readonly RotaDiaService $rotaDiaService,
        private readonly ProspectoRepository $prospectoRepository,
        private readonly VisitaService $visitaService,
        private readonly ArquivoMidiaService $arquivoMidiaService,
        private readonly LocalizacaoService $localizacaoService,
        private readonly CercaTemporariaService $cercaTemporariaService,
        private readonly ConsultaReceitaInterface $consultaReceita,
        private readonly GooglePlacesClient $googlePlacesClient,
    ) {}

    public function obterSetup(Request $request): JsonResponse
    {
        return response()->json([
            'setup' => $this->setupDiaService->buscarHoje($request->user()),
        ]);
    }

    public function salvarSetup(SalvarSetupRequest $request): JsonResponse
    {
        $setup = $this->setupDiaService->salvar($request->user(), $request->validated());

        if ($request->hasSession()) {
            $request->session()->put('prospecta.setup', $setup);
        }

        return response()->json(['ok' => true, 'setup' => $setup]);
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

    public function prospectar(ProspectarAreaRequest $request): JsonResponse
    {
        return response()->json(
            $this->huntingService->prospectar($request->user(), $request->validated()),
        );
    }

    public function verificarTerritorio(VerificarTerritorioRequest $request): JsonResponse
    {
        return response()->json(
            $this->territorioService->verificarCep(
                $request->user(),
                $request->validated('cep'),
            ),
        );
    }

    public function buscarProspecto(BuscarProspectoRequest $request): JsonResponse
    {
        $resultado = $this->prospectoService->buscar(
            $request->user(),
            $request->validated('cnpj'),
        );

        return response()->json([
            'prospecto' => $resultado['prospecto'],
            'territorio' => $resultado['territorio'],
        ]);
    }

    public function gerarRota(GerarRotaRequest $request): JsonResponse
    {
        $ids = $request->validated('prospecto_ids');
        $prospectos = $this->prospectoRepository->buscarPorIds($ids);

        $origem = null;
        if ($request->filled('origem_lat') && $request->filled('origem_lng')) {
            $origem = [
                'lat' => (float) $request->validated('origem_lat'),
                'lng' => (float) $request->validated('origem_lng'),
            ];
        }

        $rota = $this->rotaService->gerar(
            $prospectos,
            $origem,
            $request->validated('limite'),
            [
                'segmento' => $request->validated('segmento'),
                'horas' => $request->validated('horas'),
                'mix_prospeccao' => (float) $request->validated('mix_prospeccao'),
            ],
        );

        $rotaDia = $this->rotaDiaService->publicar($request->user(), $rota['itens'] ?? []);

        return response()->json([
            ...$rota,
            'rota_dia_id' => $rotaDia?->id,
            'plano_publicado' => $rotaDia !== null,
        ]);
    }

    public function rotaHoje(Request $request): JsonResponse
    {
        $rota = $this->rotaDiaService->buscarDoDia($request->user()->id);

        if (! $rota) {
            return response()->json(['rota' => null, 'itens' => []]);
        }

        $itens = $rota->paradas->map(function ($parada) {
            $p = $parada->prospecto;

            return [
                'ordem' => $parada->ordem,
                'parada_id' => $parada->id,
                'status_parada' => $parada->status?->value ?? $parada->status,
                'visita_id' => $parada->visita_id,
                'id' => $p?->id,
                'cnpj' => $p?->cnpj,
                'razao_social' => $p?->razao_social,
                'endereco' => $p?->endereco,
                'telefone' => $p?->telefone,
                'cep' => $p?->cep,
                'lat' => $parada->lat ?? $p?->lat,
                'lng' => $parada->lng ?? $p?->lng,
                'is_cliente' => (bool) ($p?->is_cliente ?? false),
            ];
        })->values()->all();

        return response()->json([
            'rota' => [
                'id' => $rota->id,
                'data' => $rota->data?->toDateString(),
                'total_paradas' => $rota->total_paradas,
            ],
            'itens' => $itens,
        ]);
    }

    public function salvarVisita(SalvarVisitaRequest $request): JsonResponse
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

    public function localizacao(SalvarLocalizacaoRequest $request): JsonResponse
    {
        $loc = $this->localizacaoService->registrar(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'ok' => true,
            'capturado_em' => $loc->capturado_em->toIso8601String(),
        ], 201);
    }

    public function cerca(ReservarCercaRequest $request): JsonResponse
    {
        $cerca = $this->cercaTemporariaService->reservar($request->user(), $request->validated());

        return response()->json([
            'cerca' => [
                'id' => $cerca->id,
                'expira_em' => $cerca->expira_em->toIso8601String(),
                'rotulo' => $cerca->rotulo,
            ],
        ], 201);
    }

    public function receita(ConsultarReceitaRequest $request): JsonResponse
    {
        $cnpj = $request->validated('cnpj');

        try {
            $dados = $this->consultaReceita->consultar($cnpj);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'cnpj' => 'Falha ao consultar Receita: '.$e->getMessage(),
            ]);
        }

        $status = StatusReceita::tryFrom((string) ($dados['status_receita'] ?? ''))
            ?? StatusReceita::Desconhecido;

        if (in_array($status, [StatusReceita::Baixada, StatusReceita::Inapta, StatusReceita::Nulo], true)) {
            return response()->json([
                'ok' => false,
                'bloqueado' => true,
                'motivo' => 'CNPJ '.$status->value.' — fora do ICP / não prospectar.',
                'dados' => $dados,
            ], 422);
        }

        $prospecto = null;
        if ($request->filled('prospecto_id')) {
            $existente = $this->prospectoRepository->buscarPorId((int) $request->validated('prospecto_id'));
            if ($existente) {
                $prospecto = $this->prospectoRepository->atualizar($existente, [
                    'cnpj' => $dados['cnpj'],
                    'razao_social' => $dados['razao_social'] ?: $existente->razao_social,
                    'cep' => $dados['cep'] ?: $existente->cep,
                    'status_receita' => $status->value,
                    'lat' => $dados['lat'] ?? $existente->lat,
                    'lng' => $dados['lng'] ?? $existente->lng,
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'bloqueado' => false,
            'dados' => $dados,
            'prospecto' => $prospecto ? [
                'id' => $prospecto->id,
                'cnpj' => $prospecto->cnpj,
                'razao_social' => $prospecto->razao_social,
                'status_receita' => $prospecto->status_receita?->value ?? $prospecto->status_receita,
                'cep' => $prospecto->cep,
            ] : null,
            'driver' => config('prospecta.receita_ws.driver'),
        ]);
    }

    public function placeDetalhe(Request $request): JsonResponse
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
