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

    /**
     * Busca por endereço (UF + cidade + trecho). ViaCEP exige ≥3 caracteres no trecho.
     *
     * @return list<array{cep: string, logradouro: string, bairro: string, cidade: string, uf: string}>
     */
    public function buscarPorEndereco(string $uf, string $cidade, string $trecho): array
    {
        $uf = strtoupper(trim($uf));
        $cidade = trim($cidade);
        $trecho = trim($trecho);

        if (strlen($uf) !== 2 || $cidade === '' || mb_strlen($trecho) < 3) {
            return [];
        }

        $base = rtrim((string) config('prospecta.viacep.url_base', 'https://viacep.com.br/ws'), '/');
        $cidadePath = rawurlencode($this->semAcento($cidade));
        $trechoPath = rawurlencode($this->semAcento($trecho));

        try {
            $resposta = Http::withoutVerifying()
                ->timeout(10)
                ->acceptJson()
                ->get("{$base}/{$uf}/{$cidadePath}/{$trechoPath}/json/");
        } catch (ConnectionException|Throwable) {
            return [];
        }

        if (! $resposta->successful()) {
            return [];
        }

        $dados = $resposta->json();

        if (! is_array($dados) || ($dados['erro'] ?? false)) {
            return [];
        }

        // Um único objeto ou lista
        $itens = array_is_list($dados) ? $dados : [$dados];
        $saida = [];

        foreach ($itens as $item) {
            if (! is_array($item) || ($item['erro'] ?? false)) {
                continue;
            }

            $saida[] = [
                'cep' => preg_replace('/\D+/', '', (string) ($item['cep'] ?? '')) ?? '',
                'logradouro' => (string) ($item['logradouro'] ?? ''),
                'bairro' => (string) ($item['bairro'] ?? ''),
                'cidade' => (string) ($item['localidade'] ?? ''),
                'uf' => (string) ($item['uf'] ?? ''),
            ];
        }

        return $saida;
    }

    /**
     * Bairros únicos a partir da busca por endereço na cidade.
     *
     * @return list<string>
     */
    public function sugerirBairros(string $uf, string $cidade, string $trecho): array
    {
        $bairros = [];

        foreach ($this->buscarPorEndereco($uf, $cidade, $trecho) as $item) {
            $bairro = trim($item['bairro']);
            if ($bairro === '') {
                continue;
            }
            if (! $this->mesmaCidade($item['cidade'], $cidade)) {
                continue;
            }
            $bairros[$this->normalizar($bairro)] = $bairro;
        }

        return array_values($bairros);
    }

    private function semAcento(string $texto): string
    {
        $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);

        return $convertido !== false ? $convertido : $texto;
    }

    private function normalizar(string $texto): string
    {
        return mb_strtolower($this->semAcento($texto));
    }

    private function mesmaCidade(string $a, string $b): bool
    {
        return $this->normalizar($a) === $this->normalizar($b);
    }
}
