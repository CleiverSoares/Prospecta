<?php

namespace App\Models;

use App\Enums\TipoUnidade;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nome',
    'tipo',
    'cep_inicio',
    'cep_fim',
    'poligono_geojson',
])]
class Unidade extends Model
{
    protected function casts(): array
    {
        return [
            'tipo' => TipoUnidade::class,
            'poligono_geojson' => 'array',
        ];
    }
}
