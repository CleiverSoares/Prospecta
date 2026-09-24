<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramInscrito extends Model
{
    protected $table = 'telegram_inscritos';

    protected $fillable = [
        'chat_id',
        'nome',
        'username',
        'ativo',
        'inscrito_em',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'inscrito_em' => 'datetime',
        ];
    }
}
