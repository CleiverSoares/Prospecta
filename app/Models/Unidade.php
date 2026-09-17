<?php

namespace App\Models;

use App\Enums\TipoUnidade;
use Database\Factories\UnidadeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nome',
    'tipo',
    'cep_inicio',
    'cep_fim',
    'poligono_geojson',
])]
class Unidade extends Model
{
    /** @use HasFactory<UnidadeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoUnidade::class,
            'poligono_geojson' => 'array',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
