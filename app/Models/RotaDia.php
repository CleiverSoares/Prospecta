<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'data',
    'total_paradas',
])]
class RotaDia extends Model
{
    protected $table = 'rotas_dia';

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'total_paradas' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paradas(): HasMany
    {
        return $this->hasMany(ParadaPlanejada::class, 'rota_dia_id')->orderBy('ordem');
    }
}
