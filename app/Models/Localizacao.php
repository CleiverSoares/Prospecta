<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Localizacao extends Model
{
    protected $table = 'localizacoes';

    protected $fillable = [
        'user_id',
        'lat',
        'lng',
        'precisao',
        'velocidade',
        'direcao',
        'capturado_em',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'precisao' => 'float',
            'velocidade' => 'float',
            'direcao' => 'float',
            'capturado_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
