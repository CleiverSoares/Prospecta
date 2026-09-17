<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class ViaCepService
{
    /**
     * @return array{cep: string, logradouro: string, bairro: string, cidade: string, uf: string}|null
     */
    public function buscar(string $cep): ?array
    {
        $limpo = preg_replace('/\D+/', '', $cep) ?? '';

        if (strlen($limpo) !== 8) {
            return null;
        }

        $base = rtrim((string) config('prospecta.viacep.url_base', 'https://viacep.com.br/ws'), '/');

        try {
            $resposta = Http::withoutVerifying()
                ->timeout(8)
                ->acceptJson()
                ->get("{$base}/{$limpo}/json/");
        } catch (ConnectionException|Throwable) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        $dados = $resposta->json();

        if (! is_array($dados) || ($dados['erro'] ?? false)) {
            return null;
        }

        return [
            'cep' => $limpo,
            'logradouro' => (string) ($dados['logradouro'] ?? ''),
            'bairro' => (string) ($dados['bairro'] ?? ''),
            'cidade' => (string) ($dados['localidade'] ?? ''),
            'uf' => (string) ($dados['uf'] ?? ''),
        ];
    }
}
