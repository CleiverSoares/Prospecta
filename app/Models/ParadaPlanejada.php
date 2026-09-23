<?php

namespace App\Models;

use App\Enums\StatusParadaPlanejada;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'rota_dia_id',
    'prospecto_id',
    'ordem',
    'lat',
    'lng',
    'status',
    'visita_id',
])]
class ParadaPlanejada extends Model
{
    protected $table = 'paradas_planejadas';

    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
            'status' => StatusParadaPlanejada::class,
        ];
    }

    public function rotaDia(): BelongsTo
    {
        return $this->belongsTo(RotaDia::class, 'rota_dia_id');
    }

    public function prospecto(): BelongsTo
    {
        return $this->belongsTo(Prospecto::class);
    }

    public function visita(): BelongsTo
    {
        return $this->belongsTo(Visita::class);
    }
}
