<?php

namespace App\Services\Receita;

use App\Contracts\ConsultaReceitaInterface;
use App\Enums\StatusReceita;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpConsultaReceita implements ConsultaReceitaInterface
{
    public function consultar(string $cnpj): array
    {
        $cnpjLimpo = preg_replace('/\D+/', '', $cnpj) ?? '';
        $base = rtrim((string) config('prospecta.receita_ws.url_base'), '/');
        $token = config('prospecta.receita_ws.token');

        if ($base === '') {
            throw new RuntimeException('RECEITA_WS_URL_BASE não configurada.');
        }

        $request = Http::timeout((int) config('prospecta.receita_ws.timeout', 10))
            ->acceptJson();

        if (filled($token)) {
            $request = $request->withToken((string) $token);
        }

        $resposta = $request->get("{$base}/{$cnpjLimpo}");

        if (! $resposta->successful()) {
            throw new RuntimeException('Falha na consulta à ReceitaWS.');
        }

        $json = $resposta->json();

        return [
            'cnpj' => $cnpjLimpo,
            'razao_social' => (string) ($json['nome'] ?? $json['razao_social'] ?? 'Sem razão social'),
            'cep' => preg_replace('/\D+/', '', (string) ($json['cep'] ?? '')) ?: '00000000',
            'status_receita' => $this->mapearStatus((string) ($json['situacao'] ?? '')),
            'lat' => isset($json['lat']) ? (float) $json['lat'] : null,
            'lng' => isset($json['lng']) ? (float) $json['lng'] : null,
        ];
    }

    private function mapearStatus(string $situacao): string
    {
        $normalizado = mb_strtoupper(trim($situacao));

        return match (true) {
            str_contains($normalizado, 'ATIVA') => StatusReceita::Ativa->value,
            str_contains($normalizado, 'BAIXADA') => StatusReceita::Baixada->value,
            str_contains($normalizado, 'INAPTA') => StatusReceita::Inapta->value,
            str_contains($normalizado, 'SUSPENSA') => StatusReceita::Suspensa->value,
            str_contains($normalizado, 'NULA') => StatusReceita::Nulo->value,
            default => StatusReceita::Desconhecido->value,
        };
    }
}
