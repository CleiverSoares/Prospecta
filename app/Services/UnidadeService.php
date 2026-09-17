<?php

namespace App\Services;

use App\Models\Unidade;
use App\Repositories\UnidadeRepository;
use Illuminate\Database\Eloquent\Collection;

class UnidadeService
{
    public function __construct(
        private readonly UnidadeRepository $unidadeRepository,
    ) {}

    public function listar(): Collection
    {
        return $this->unidadeRepository->todos();
    }

    public function criar(array $dados): Unidade
    {
        return $this->unidadeRepository->criar($dados);
    }

    public function atualizar(Unidade $unidade, array $dados): Unidade
    {
        return $this->unidadeRepository->atualizar($unidade, $dados);
    }
}
