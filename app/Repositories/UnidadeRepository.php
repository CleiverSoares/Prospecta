<?php

namespace App\Repositories;

use App\Models\Unidade;
use Illuminate\Database\Eloquent\Collection;

class UnidadeRepository
{
    public function todos(): Collection
    {
        return Unidade::query()->orderBy('nome')->get();
    }

    public function buscarPorId(int $id): ?Unidade
    {
        return Unidade::query()->find($id);
    }

    public function criar(array $dados): Unidade
    {
        return Unidade::query()->create($dados);
    }

    public function atualizar(Unidade $unidade, array $dados): Unidade
    {
        $unidade->update($dados);

        return $unidade->fresh();
    }

    public function excluir(Unidade $unidade): bool
    {
        return (bool) $unidade->delete();
    }

    /**
     * @return Collection<int, Unidade>
     */
    public function buscarQueCobremCep(string $cep): Collection
    {
        return Unidade::query()
            ->whereNotNull('cep_inicio')
            ->whereNotNull('cep_fim')
            ->where('cep_inicio', '<=', $cep)
            ->where('cep_fim', '>=', $cep)
            ->get();
    }
}
