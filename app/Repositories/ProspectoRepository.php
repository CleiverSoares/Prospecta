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
}
