<?php

namespace App\Services\Receita;

use App\Contracts\ConsultaReceitaInterface;
use App\Enums\StatusReceita;
use InvalidArgumentException;

class MockConsultaReceita implements ConsultaReceitaInterface
{
    /**
     * Fixtures determinísticas para TDD / demo.
     *
     * @var array<string, array{razao_social: string, cep: string, status_receita: string, lat: float, lng: float}>
     */
    private array $fixtures = [
        '11222333000181' => [
            'razao_social' => 'Padaria Central LTDA',
            'cep' => '30130010',
            'status_receita' => 'ATIVA',
            'lat' => -19.9245,
            'lng' => -43.9352,
        ],
        '99888777000166' => [
            'razao_social' => 'Tech Paulista SA',
            'cep' => '01310100',
            'status_receita' => 'ATIVA',
            'lat' => -23.5614,
            'lng' => -46.6558,
        ],
    ];

    public function consultar(string $cnpj): array
    {
        $cnpjLimpo = preg_replace('/\D+/', '', $cnpj) ?? '';

        if (strlen($cnpjLimpo) !== 14) {
            throw new InvalidArgumentException('CNPJ inválido.');
        }

        $fixture = $this->fixtures[$cnpjLimpo] ?? [
            'razao_social' => 'Empresa Mock '.$cnpjLimpo,
            'cep' => '70040900',
            'status_receita' => StatusReceita::Desconhecido->value,
            'lat' => null,
            'lng' => null,
        ];

        return [
            'cnpj' => $cnpjLimpo,
            'razao_social' => $fixture['razao_social'],
            'cep' => $fixture['cep'],
            'status_receita' => $fixture['status_receita'],
            'lat' => $fixture['lat'],
            'lng' => $fixture['lng'],
        ];
    }
}
