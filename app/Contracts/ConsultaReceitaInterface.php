<?php

namespace App\Contracts;

interface ConsultaReceitaInterface
{
    /**
     * @return array{
     *     cnpj: string,
     *     razao_social: string,
     *     cep: string,
     *     status_receita: string,
     *     lat: float|null,
     *     lng: float|null
     * }
     */
    public function consultar(string $cnpj): array;
}
