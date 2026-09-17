<?php

namespace App\Enums;

enum StatusReceita: string
{
    case Ativa = 'ATIVA';
    case Baixada = 'BAIXADA';
    case Inapta = 'INAPTA';
    case Suspensa = 'SUSPENSA';
    case Nulo = 'NULO';
    case Desconhecido = 'DESCONHECIDO';
}
