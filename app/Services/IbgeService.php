<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class IbgeService
{
    /**
     * @return list<array{id: int, nome: string}>
     */
    public function municipiosPorUf(string $uf): array
    {
        $uf = strtoupper(trim($uf));

        if (strlen($uf) !== 2) {
            return [];
        }

        $base = rtrim((string) config('prospecta.ibge.url_base', 'https://servicodados.ibge.gov.br/api/v1/localidades'), '/');

        try {
            $resposta = Http::withoutVerifying()
                ->timeout(12)
                ->acceptJson()
                ->get("{$base}/estados/{$uf}/municipios");
        } catch (ConnectionException|Throwable) {
            return [];
        }

        if (! $resposta->successful() || ! is_array($resposta->json())) {
            return [];
        }

        $lista = [];

        foreach ($resposta->json() as $item) {
            if (! is_array($item) || empty($item['nome'])) {
                continue;
            }

            $lista[] = [
                'id' => (int) ($item['id'] ?? 0),
                'nome' => (string) $item['nome'],
            ];
        }

        usort($lista, fn (array $a, array $b): int => strcmp($a['nome'], $b['nome']));

        return $lista;
    }
}
