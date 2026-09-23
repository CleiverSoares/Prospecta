<?php

namespace App\Enums;

enum StatusParadaPlanejada: string
{
    case Pendente = 'pendente';
    case Feita = 'feita';
    case Pulada = 'pulada';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Feita => 'Feita',
            self::Pulada => 'Pulada',
        };
    }
}
