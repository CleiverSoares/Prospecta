<?php

namespace App\Services;

use App\Models\Unidade;
use App\Models\User;
use App\Repositories\UnidadeRepository;

class TerritorioService
{
    public function __construct(
        private readonly UnidadeRepository $unidadeRepository,
    ) {}

    /**
     * @return array{
     *     permitido: bool,
     *     motivo: string,
     *     unidade_id: int|null,
     *     unidade_nome: string|null
     * }
     */
    public function verificarCep(User $usuario, string $cep): array
    {
        $cepLimpo = preg_replace('/\D+/', '', $cep) ?? '';

        if (strlen($cepLimpo) < 8) {
            $cepLimpo = str_pad($cepLimpo, 8, '0');
        } else {
            $cepLimpo = substr($cepLimpo, 0, 8);
        }

        $unidades = $this->unidadeRepository->buscarQueCobremCep($cepLimpo);

        if ($unidades->isEmpty()) {
            return [
                'permitido' => true,
                'motivo' => 'area_livre',
                'unidade_id' => null,
                'unidade_nome' => null,
            ];
        }

        if ($usuario->unidade_id !== null) {
            /** @var Unidade|null $minha */
            $minha = $unidades->firstWhere('id', $usuario->unidade_id);

            if ($minha !== null) {
                return [
                    'permitido' => true,
                    'motivo' => 'minha_unidade',
                    'unidade_id' => $minha->id,
                    'unidade_nome' => $minha->nome,
                ];
            }
        }

        /** @var Unidade $conflito */
        $conflito = $unidades->first();

        return [
            'permitido' => false,
            'motivo' => 'bloqueado',
            'unidade_id' => $conflito->id,
            'unidade_nome' => $conflito->nome,
        ];
    }
}
