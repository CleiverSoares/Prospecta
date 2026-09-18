<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoUnidade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimarCepsPoligonoRequest;
use App\Http\Requests\Admin\SalvarUnidadeRequest;
use App\Models\Prospecto;
use App\Models\Unidade;
use App\Services\PoligonoCepService;
use App\Services\UnidadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnidadeController extends Controller
{
    public function __construct(
        private readonly UnidadeService $unidadeService,
        private readonly PoligonoCepService $poligonoCepService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Unidade::class);

        return view('admin.unidades.index', [
            'unidades' => $this->unidadeService->listar(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Unidade::class);

        $poligonoSessao = session()->pull('unidade.poligono_rascunho');
        $cepsSessao = session()->pull('unidade.ceps_rascunho', []);

        return view('admin.unidades.create', [
            'tipos' => TipoUnidade::cases(),
            'poligonoPrefill' => $poligonoSessao,
            'cepInicioPrefill' => $cepsSessao['cep_inicio'] ?? null,
            'cepFimPrefill' => $cepsSessao['cep_fim'] ?? null,
            'prospectosMapa' => $this->prospectosParaMapa(),
        ]);
    }

    public function rascunhoPoligono(Request $request): RedirectResponse
    {
        $this->authorize('create', Unidade::class);

        $poligono = $request->input('poligono_geojson');
        if (is_string($poligono)) {
            $poligono = json_decode($poligono, true);
        }

        $request->merge(['poligono_geojson' => $poligono]);

        $dados = $request->validate([
            'poligono_geojson' => ['required', 'array'],
            'poligono_geojson.type' => ['required', 'in:Polygon'],
            'poligono_geojson.coordinates' => ['required', 'array', 'min:1'],
        ]);

        $poligono = $dados['poligono_geojson'];
        session(['unidade.poligono_rascunho' => $poligono]);

        try {
            $faixa = $this->poligonoCepService->estimarFaixa($poligono);
            session(['unidade.ceps_rascunho' => $faixa]);
        } catch (\Throwable) {
            session()->forget('unidade.ceps_rascunho');
        }

        return redirect()->route('admin.unidades.create');
    }

    public function store(SalvarUnidadeRequest $request): RedirectResponse
    {
        $this->authorize('create', Unidade::class);
        $unidade = $this->unidadeService->criar($request->validated());

        return redirect()
            ->route('admin.unidades.edit', $unidade)
            ->with('status', 'Unidade criada.');
    }

    public function edit(Unidade $unidade): View
    {
        $this->authorize('update', $unidade);

        return view('admin.unidades.edit', [
            'unidade' => $unidade,
            'tipos' => TipoUnidade::cases(),
            'prospectosMapa' => $this->prospectosParaMapa(),
        ]);
    }

    public function update(SalvarUnidadeRequest $request, Unidade $unidade): RedirectResponse
    {
        $this->authorize('update', $unidade);
        $this->unidadeService->atualizar($unidade, $request->validated());

        return redirect()
            ->route('admin.unidades.edit', $unidade)
            ->with('status', 'Unidade atualizada.');
    }

    public function estimarCeps(EstimarCepsPoligonoRequest $request): JsonResponse
    {
        $faixa = $this->poligonoCepService->estimarFaixa($request->validated('poligono_geojson'));

        return response()->json($faixa);
    }

    /**
     * @return list<array{id: int, nome: string, lat: float, lng: float, is_cliente: bool}>
     */
    private function prospectosParaMapa(): array
    {
        return Prospecto::query()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderByDesc('id')
            ->limit(400)
            ->get(['id', 'razao_social', 'lat', 'lng', 'is_cliente'])
            ->map(fn (Prospecto $p) => [
                'id' => $p->id,
                'nome' => $p->razao_social,
                'lat' => (float) $p->lat,
                'lng' => (float) $p->lng,
                'is_cliente' => (bool) $p->is_cliente,
            ])
            ->all();
    }
}
