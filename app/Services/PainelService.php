<?php

namespace App\Services;

use App\Enums\StatusVisita;
use App\Models\Prospecto;
use App\Models\Unidade;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PainelService
{
    public function __construct(
        private readonly LocalizacaoService $localizacaoService,
    ) {}

    /**
     * @param  array{unidade_id?: int|null, gestor_id?: int|null, vendedor_id?: int|null, de?: string|null, ate?: string|null, segmento?: string|null}  $filtros
     * @return array{
     *     metricas: array<string, int|float>,
     *     unidadesMapa: Collection<int, array<string, mixed>>,
     *     prospectosMapa: list<array<string, mixed>>,
     *     visitasMapa: list<array<string, mixed>>,
     *     aoVivo: list<array<string, mixed>>,
     *     placar: list<array{nome: string, checkins: int}>,
     *     filtros: array<string, mixed>,
     *     opcoes: array{unidades: Collection, gestores: Collection, vendedores: Collection},
     *     escopoGestor: bool
     * }
     */
    public function montar(array $filtros = [], ?User $usuario = null): array
    {
        $escopoGestor = $usuario !== null
            && $usuario->hasRole('gestor')
            && ! $usuario->hasRole('adm');

        $unidadeId = isset($filtros['unidade_id']) ? (int) $filtros['unidade_id'] : null;
        $gestorId = isset($filtros['gestor_id']) ? (int) $filtros['gestor_id'] : null;
        $vendedorId = isset($filtros['vendedor_id']) ? (int) $filtros['vendedor_id'] : null;

        if ($escopoGestor) {
            $gestorId = (int) $usuario->id;
            if ($usuario->unidade_id) {
                $unidadeId = (int) $usuario->unidade_id;
            }
            if ($vendedorId) {
                $ehDaEquipe = User::query()
                    ->where('id', $vendedorId)
                    ->where('gestor_id', $gestorId)
                    ->exists();
                if (! $ehDaEquipe) {
                    $vendedorId = null;
                }
            }
        }

        $de = filled($filtros['de'] ?? null) ? Carbon::parse((string) $filtros['de'])->startOfDay() : today()->startOfDay();
        $ate = filled($filtros['ate'] ?? null) ? Carbon::parse((string) $filtros['ate'])->endOfDay() : today()->endOfDay();
        $segmento = filled($filtros['segmento'] ?? null) ? strtoupper((string) $filtros['segmento']) : null;

        $visitasQuery = Visita::query()
            ->whereBetween('created_at', [$de, $ate]);

        if ($vendedorId) {
            $visitasQuery->where('user_id', $vendedorId);
        } elseif ($gestorId) {
            $ids = User::query()->where('gestor_id', $gestorId)->pluck('id');
            $visitasQuery->whereIn('user_id', $ids);
        } elseif ($unidadeId) {
            $ids = User::query()->where('unidade_id', $unidadeId)->pluck('id');
            $visitasQuery->whereIn('user_id', $ids);
        }

        $visitasPeriodo = (clone $visitasQuery)->count();
        $visitasFeitas = (clone $visitasQuery)->where('status', StatusVisita::Feita)->count();
        $conversao = $visitasPeriodo > 0 ? round(($visitasFeitas / $visitasPeriodo) * 100, 1) : 0.0;

        $prospectosQuery = Prospecto::query()->whereNotNull('lat')->whereNotNull('lng');

        $kmEstimado = round($visitasPeriodo * 4.2, 1);

        $placarQuery = Visita::query()
            ->selectRaw('user_id, count(*) as total')
            ->whereDate('created_at', today())
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(8)
            ->with('usuario:id,name');

        if ($escopoGestor) {
            $equipeIds = User::query()->where('gestor_id', $gestorId)->pluck('id');
            $placarQuery->whereIn('user_id', $equipeIds);
        }

        $placar = $placarQuery
            ->get()
            ->map(fn (Visita $v) => [
                'nome' => $v->usuario?->name ?? '—',
                'checkins' => (int) $v->total,
                'meta' => (int) config('prospecta.meta_visitas_dia', 8),
            ])
            ->all();

        $unidadesMapa = Unidade::query()
            ->when($unidadeId, fn ($q) => $q->where('id', $unidadeId))
            ->whereNotNull('poligono_geojson')
            ->orderBy('nome')
            ->get(['id', 'nome', 'poligono_geojson', 'cep_inicio', 'cep_fim'])
            ->map(fn (Unidade $u) => [
                'id' => $u->id,
                'nome' => $u->nome,
                'poligono' => $u->poligono_geojson,
                'cep_inicio' => $u->cep_inicio,
                'cep_fim' => $u->cep_fim,
            ])
            ->values();

        $prospectosMapa = $prospectosQuery
            ->orderByDesc('id')
            ->limit(400)
            ->get(['id', 'razao_social', 'lat', 'lng', 'is_cliente', 'cep', 'endereco', 'cnpj', 'telefone'])
            ->map(fn (Prospecto $p) => [
                'id' => $p->id,
                'nome' => $p->razao_social,
                'lat' => (float) $p->lat,
                'lng' => (float) $p->lng,
                'is_cliente' => (bool) $p->is_cliente,
                'cep' => $p->cep,
                'endereco' => $p->endereco,
                'cnpj' => $p->cnpj,
                'telefone' => $p->telefone,
            ])
            ->all();

        $visitasMapa = (clone $visitasQuery)
            ->with([
                'prospecto:id,razao_social,lat,lng,endereco,cep,cnpj,is_cliente',
                'usuario:id,name',
            ])
            ->latest()
            ->limit(200)
            ->get()
            ->filter(fn (Visita $v) => $v->checkin_lat && $v->checkin_lng)
            ->map(fn (Visita $v) => [
                'id' => $v->id,
                'nome' => $v->prospecto?->razao_social ?? 'Visita',
                'lat' => (float) $v->checkin_lat,
                'lng' => (float) $v->checkin_lng,
                'status' => $v->status?->value ?? $v->status,
                'endereco' => $v->prospecto?->endereco,
                'cep' => $v->prospecto?->cep,
                'cnpj' => $v->prospecto?->cnpj,
                'is_cliente' => (bool) ($v->prospecto?->is_cliente ?? false),
                'vendedor' => $v->usuario?->name,
                'quando' => $v->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'url' => route('admin.visitas.show', $v, absolute: false),
            ])
            ->values()
            ->all();

        $vendedoresOpcoes = User::query()
            ->role('vendedor')
            ->when($escopoGestor, fn ($q) => $q->where('gestor_id', $gestorId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $unidadesOpcoes = Unidade::query()
            ->when($escopoGestor && $unidadeId, fn ($q) => $q->where('id', $unidadeId))
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $gestoresOpcoes = User::query()
            ->role('gestor')
            ->when($escopoGestor, fn ($q) => $q->where('id', $gestorId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'metricas' => [
                'unidades' => $escopoGestor ? $unidadesOpcoes->count() : Unidade::query()->count(),
                'usuarios' => User::query()->count(),
                'prospectos' => Prospecto::query()->count(),
                'visitas_hoje' => (clone $visitasQuery)->whereDate('created_at', today())->count(),
                'visitas_periodo' => $visitasPeriodo,
                'conversao' => $conversao,
                'km_estimado' => $kmEstimado,
                'upsells' => Prospecto::query()->where('is_cliente', true)->count(),
            ],
            'unidadesMapa' => $unidadesMapa,
            'prospectosMapa' => $prospectosMapa,
            'visitasMapa' => $visitasMapa,
            'aoVivo' => $this->localizacaoService->aoVivo(
                $unidadeId,
                $gestorId,
                $vendedorId,
                (int) config('prospecta.tracking.janela_minutos', 15),
            ),
            'placar' => $placar,
            'filtros' => [
                'unidade_id' => $unidadeId,
                'gestor_id' => $gestorId,
                'vendedor_id' => $vendedorId,
                'de' => $de->toDateString(),
                'ate' => $ate->toDateString(),
                'segmento' => $segmento,
            ],
            'opcoes' => [
                'unidades' => $unidadesOpcoes,
                'gestores' => $gestoresOpcoes,
                'vendedores' => $vendedoresOpcoes,
            ],
            'escopoGestor' => $escopoGestor,
        ];
    }
}
