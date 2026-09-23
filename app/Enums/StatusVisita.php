<?php

namespace App\Enums;

enum StatusVisita: string
{
    case SemNinguem = 'SEM_NINGUEM';
    case Feita = 'FEITA';
    case Retorno = 'RETORNO';

    public function rotulo(): string
    {
        return match ($this) {
            self::SemNinguem => 'Sem ninguém',
            self::Feita => 'Feita',
            self::Retorno => 'Retorno',
        };
    }
}
