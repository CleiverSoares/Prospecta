<?php

namespace App\Models;

use App\Enums\StatusReceita;
use Database\Factories\ProspectoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    /** @use HasFactory<ProspectoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status_receita' => StatusReceita::class,
            'is_cliente' => 'boolean',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class);
    }
}
