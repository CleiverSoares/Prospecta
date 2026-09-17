<?php

namespace App\Models;

use App\Enums\StatusReceita;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'cnpj',
    'razao_social',
    'cep',
    'lat',
    'lng',
    'status_receita',
    'is_cliente',
])]
class Prospecto extends Model
{
    protected function casts(): array
    {
        return [
            'status_receita' => StatusReceita::class,
            'is_cliente' => 'boolean',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }
}
