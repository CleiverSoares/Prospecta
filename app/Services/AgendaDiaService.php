<?php

namespace App\Services;

use App\Enums\StatusParadaPlanejada;
use App\Enums\StatusVisita;
use App\Models\ParadaPlanejada;
use App\Models\User;
use App\Models\Visita;

class AgendaDiaService
{
    public function __construct(
        private readonly RotaDiaService $rotaDiaService,
    ) {}

    /**
     * @return array{
     *     cards: list<array<string, mixed>>,
     *     metaCasa: int,
     *     escopoGestor: bool
     * }
     */
    public function montar(?User $usuario = null): array
    {
        $escopoGestor = $usuario !== null
            && $usuario->hasRole('gestor')
            && ! $usuario->hasRole('adm');

        $vendedores = User::query()
            ->role('vendedor')
            ->where('ativo', true)
            ->when($escopoGestor, fn ($q) => $q->where('gestor_id', $usuario->id))
            ->with('unidade:id,nome')
            ->orderBy('name')
            ->get(['id', 'name', 'unidade_id', 'foto_path']);

        $vendedorIds = $vendedores->pluck('id')->all();

        $visitas = Visita::query()
            ->with(['prospecto:id,razao_social,endereco,lat,lng'])
            ->whereIn('user_id', $vendedorIds ?: [0])
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->groupBy('user_id');

        $rotas = $this->rotaDiaService->listarDoDiaPorUsuarios($vendedorIds);
        $metaCasa = (int) config('prospecta.meta_visitas_dia', 8);

        $cards = [];
        foreach ($vendedores as $vendedor) {
            $rota = $rotas->get($vendedor->id);
            $visitasVendedor = $visitas->get($vendedor->id, collect());

            $paradas = [];
            $visitaIdsNoPlano = [];
            $feitasNoPlano = 0;
            $totalPlano = 0;

            if ($rota) {
                $totalPlano = (int) $rota->total_paradas;
                foreach ($rota->paradas as $parada) {
                    /** @var ParadaPlanejada $parada */
                    $rotuloStatus = $parada->status->rotulo();
                    if ($parada->status === StatusParadaPlanejada::Feita) {
                        $feitasNoPlano++;
                        if ($parada->visita?->status instanceof StatusVisita) {
                            $rotuloStatus = $parada->visita->status->rotulo();
                        }
                    }
                    if ($parada->visita_id) {
                        $visitaIdsNoPlano[] = (int) $parada->visita_id;
                    }
                    $paradas[] = [
                        'ordem' => $parada->ordem,
                        'nome' => $parada->prospecto?->razao_social ?: '—',
                        'endereco' => $parada->prospecto?->endereco,
                        'status' => $parada->status->value,
                        'status_rotulo' => $rotuloStatus,
                        'no_plano' => true,
                    ];
                }
            }

            $foraDoPlano = $visitasVendedor
                ->reject(fn (Visita $v) => in_array((int) $v->id, $visitaIdsNoPlano, true))
                ->map(fn (Visita $v) => [
                    'ordem' => null,
                    'nome' => $v->prospecto?->razao_social ?: '—',
                    'endereco' => $v->prospecto?->endereco,
                    'status' => $v->status instanceof StatusVisita ? $v->status->value : (string) $v->status,
                    'status_rotulo' => $v->status instanceof StatusVisita ? $v->status->rotulo() : (string) $v->status,
                    'no_plano' => false,
                ])
                ->values()
                ->all();

            $checkinsHoje = $visitasVendedor->count();

            $cards[] = [
                'vendedor' => $vendedor,
                'tem_plano' => $rota !== null,
                'feitas_plano' => $feitasNoPlano,
                'total_plano' => $totalPlano,
                'checkins_hoje' => $checkinsHoje,
                'meta_casa' => $metaCasa,
                'atingiu_meta_casa' => $checkinsHoje >= $metaCasa,
                'paradas' => $paradas,
                'fora_do_plano' => $foraDoPlano,
            ];
        }

        return [
            'cards' => $cards,
            'metaCasa' => $metaCasa,
            'escopoGestor' => $escopoGestor,
        ];
    }
}
