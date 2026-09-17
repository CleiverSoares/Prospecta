<?php

namespace App\Services;

use App\Models\Prospecto;
use App\Repositories\ProspectoRepository;

class ProspectoService
{
    public function __construct(
        private readonly ProspectoRepository $prospectoRepository,
    ) {}

    public function buscarPorCnpj(string $cnpj): ?Prospecto
    {
        return $this->prospectoRepository->buscarPorCnpj($cnpj);
    }

    public function upsertPorCnpj(string $cnpj, array $dados): Prospecto
    {
        return $this->prospectoRepository->upsertPorCnpj($cnpj, $dados);
    }
}
