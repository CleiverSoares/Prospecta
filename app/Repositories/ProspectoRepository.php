<?php

namespace App\Repositories;

use App\Models\Prospecto;
use Illuminate\Database\Eloquent\Collection;

class ProspectoRepository
{
    public function buscarPorCnpj(string $cnpj): ?Prospecto
    {
        return Prospecto::query()->where('cnpj', $cnpj)->first();
    }

    public function upsertPorGooglePlace(string $placeId, array $dados): Prospecto
    {
        return Prospecto::query()->updateOrCreate(
            ['google_place_id' => $placeId],
            $dados
        );
    }

    public function buscarPorIds(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return Prospecto::query()
            ->whereIn('id', $ids)
            ->get();
    }

    public function buscarPorCep(string $cep): Collection
    {
        return Prospecto::query()->where('cep', $cep)->get();
    }

    public function criar(array $dados): Prospecto
    {
        return Prospecto::query()->create($dados);
    }

    public function upsertPorCnpj(string $cnpj, array $dados): Prospecto
    {
        return Prospecto::query()->updateOrCreate(
            ['cnpj' => $cnpj],
            $dados
        );
    }

    public function buscarPorId(int $id): ?Prospecto
    {
        return Prospecto::query()->find($id);
    }

    public function atualizar(Prospecto $prospecto, array $dados): Prospecto
    {
        $prospecto->update($dados);

        return $prospecto->fresh();
    }
}
