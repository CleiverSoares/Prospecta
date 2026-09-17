<?php

namespace App\Models;

use App\Enums\StatusVisita;
use Database\Factories\VisitaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'prospecto_id',
    'user_id',
    'status',
    'checkin_lat',
    'checkin_lng',
    'caminho_foto',
    'caminho_audio',
])]
class Visita extends Model
{
    /** @use HasFactory<VisitaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => StatusVisita::class,
            'checkin_lat' => 'float',
            'checkin_lng' => 'float',
        ];
    }

    public function prospecto(): BelongsTo
    {
        return $this->belongsTo(Prospecto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
