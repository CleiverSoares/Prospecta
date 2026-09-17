<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UnidadeRepository;

/**
 * Regras de território (minha / livre / bloqueada) — implementação na fase 4.
 */
class TerritorioService
{
    public function __construct(
        private readonly UnidadeRepository $unidadeRepository,
    ) {}

    /**
     * @return array{permitido: bool, motivo: string, unidade_id: int|null}
     */
    public function verificarCep(User $usuario, string $cep): array
    {
        // Stub 1.6 — usar $this->unidadeRepository na fase 4.
        return [
            'permitido' => false,
            'motivo' => 'nao_implementado',
            'unidade_id' => null,
        ];
    }
}
