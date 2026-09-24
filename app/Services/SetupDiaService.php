<?php

namespace App\Services;

use App\Models\SetupDia;
use App\Models\User;
use App\Repositories\SetupDiaRepository;
use Illuminate\Support\Carbon;

class SetupDiaService
{
    public function __construct(
        private readonly SetupDiaRepository $setupDiaRepository,
    ) {}

    /**
     * @param  array{local: string, segmento: string, horas: string, mix_prospeccao: float|int|string}  $dados
     * @return array{local: string, segmento: string, horas: string, mix_prospeccao: float, salvo_em: string}
     */
    public function salvar(User $usuario, array $dados, Carbon|string|null $data = null): array
    {
        $setup = $this->setupDiaRepository->salvarDoDia($usuario->id, [
            'local' => $dados['local'],
            'segmento' => $dados['segmento'],
            'horas' => $dados['horas'],
            'mix_prospeccao' => (float) $dados['mix_prospeccao'],
        ], $data);

        return $this->serializar($setup);
    }

    /**
     * @return array{local: string, segmento: string, horas: string, mix_prospeccao: float, salvo_em: string}|null
     */
    public function buscarHoje(User $usuario, Carbon|string|null $data = null): ?array
    {
        $setup = $this->setupDiaRepository->buscarDoDia($usuario->id, $data);

        return $setup ? $this->serializar($setup) : null;
    }

    public function completoHoje(User $usuario, Carbon|string|null $data = null): bool
    {
        return $this->buscarHoje($usuario, $data) !== null;
    }

    /**
     * @return array{local: string, segmento: string, horas: string, mix_prospeccao: float, salvo_em: string}
     */
    public function serializar(SetupDia $setup): array
    {
        return [
            'local' => $setup->local,
            'segmento' => $setup->segmento,
            'horas' => $setup->horas,
            'mix_prospeccao' => (float) $setup->mix_prospeccao,
            'salvo_em' => ($setup->updated_at ?? now())->toIso8601String(),
        ];
    }
}
