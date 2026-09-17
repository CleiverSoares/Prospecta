<?php

namespace App\Enums;

enum StatusVisita: string
{
    case SemNinguem = 'SEM_NINGUEM';
    case Feita = 'FEITA';
    case Retorno = 'RETORNO';
}
