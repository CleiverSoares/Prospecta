<?php

namespace App\Services;

use App\Enums\StatusParadaPlanejada;
use App\Models\ParadaPlanejada;
use App\Models\RotaDia;
use App\Models\User;
use App\Models\Visita;
use App\Repositories\RotaDiaRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RotaDiaService
{
    public function __construct(
        private readonly RotaDiaRepository $rotaDiaRepository,
    ) {}

    /**
     * Publica (ou substitui) o plano do dia a partir dos itens gerados pela rota.
     *
     * @param  list<array<string, mixed>>  $itens
     */
    public function publicar(User $usuario, array $itens, Carbon|string|null $data = null): ?RotaDia
    {
        $paradas = [];
        foreach ($itens as $i => $item) {
            $prospectoId = (int) ($item['id'] ?? $item['prospecto_id'] ?? 0);
            if ($prospectoId <= 0) {
                continue;
            }
            $paradas[] = [
                'prospecto_id' => $prospectoId,
                'ordem' => (int) ($item['ordem'] ?? ($i + 1)),
                'lat' => isset($item['lat']) ? (float) $item['lat'] : null,
                'lng' => isset($item['lng']) ? (float) $item['lng'] : null,
            ];
        }

        if ($paradas === []) {
            return null;
        }

        return $this->rotaDiaRepository->publicar(
            $usuario->id,
            $data ?? today(),
            $paradas,
        );
    }

    public function marcarCheckin(Visita $visita): ?ParadaPlanejada
    {
        return $this->rotaDiaRepository->marcarParadaFeita(
            (int) $visita->user_id,
            (int) $visita->prospecto_id,
            (int) $visita->id,
            $visita->created_at?->toDateString() ?? today()->toDateString(),
        );
    }

    public function buscarDoDia(int $userId, Carbon|string|null $data = null): ?RotaDia
    {
        return $this->rotaDiaRepository->buscarDoDia($userId, $data);
    }

    /** Cancela o plano do dia no painel (Agenda) e no aparelho. */
    public function cancelarDoDia(int $userId, Carbon|string|null $data = null): bool
    {
        return $this->rotaDiaRepository->cancelarDoDia($userId, $data);
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, RotaDia>
     */
    public function listarDoDiaPorUsuarios(array $userIds, Carbon|string|null $data = null): Collection
    {
        return $this->rotaDiaRepository->listarDoDiaPorUsuarios($userIds, $data);
    }

    public function contarFeitasNoPlano(RotaDia $rota): int
    {
        return $rota->paradas
            ->filter(fn (ParadaPlanejada $p) => $p->status === StatusParadaPlanejada::Feita)
            ->count();
    }
}
