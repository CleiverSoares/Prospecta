<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetupDia extends Model
{
    protected $table = 'setups_dia';

    protected $fillable = [
        'user_id',
        'data',
        'local',
        'segmento',
        'horas',
        'mix_prospeccao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'date',
            'mix_prospeccao' => 'float',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
