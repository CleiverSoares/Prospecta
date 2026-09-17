<?php

namespace App\Services;

use App\Models\Visita;
use App\Repositories\VisitaRepository;
use Illuminate\Database\Eloquent\Collection;

class VisitaService
{
    public function __construct(
        private readonly VisitaRepository $visitaRepository,
    ) {}

    public function criar(array $dados): Visita
    {
        return $this->visitaRepository->criar($dados);
    }

    public function listarPorUsuario(int $userId): Collection
    {
        return $this->visitaRepository->listarPorUsuario($userId);
    }
}
