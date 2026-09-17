<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CercaTemporaria extends Model
{
    protected $table = 'cercas_temporarias';

    protected $fillable = [
        'user_id',
        'rotulo',
        'cep_inicio',
        'cep_fim',
        'poligono_geojson',
        'expira_em',
    ];

    protected function casts(): array
    {
        return [
            'poligono_geojson' => 'array',
            'expira_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ativa(): bool
    {
        return $this->expira_em->isFuture();
    }
}
