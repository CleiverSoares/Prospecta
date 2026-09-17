<?php

namespace App\Services;

class GuiaBolsoService
{
    /**
     * @return array{pitch: string, objecoes: list<array{titulo: string, resposta: string}>, ajuda_url: string|null, produto: string}
     */
    public function para(string $segmento, bool $isCliente = false): array
    {
        $segmento = $this->normalizar($segmento);
        $ajuda = config('prospecta.ajuda_alterdata_url');

        if ($isCliente) {
            return [
                'produto' => 'Upsell base',
                'pitch' => 'Cliente Alterdata — valide módulos em uso e proponha expansão (Pack completo / e-Contador) antes do check-in.',
                'objecoes' => [
                    ['titulo' => 'Já tenho o que preciso', 'resposta' => 'Mostre o gap: o que ele usa vs. o que o porte atual exige. Foque em 1 módulo que fecha dor imediata.'],
                    ['titulo' => 'Depois a gente vê', 'resposta' => 'Agende retorno com proposta curta no WhatsApp e deixe o link da Ajuda Alterdata.'],
                ],
                'ajuda_url' => $ajuda,
            ];
        }

        return match ($segmento) {
            'CONTABIL' => [
                'produto' => 'Linha Pack',
                'pitch' => 'Contábil: confirme porte e software atual. Pitch Pack DP + e-Contador se houver folga de funcionários no eSocial.',
                'objecoes' => [
                    ['titulo' => 'Já tenho sistema', 'resposta' => 'Pergunte o custo de retrabalho na entrega. Mostre integração e suporte Alterdata.'],
                    ['titulo' => 'Tá caro', 'resposta' => 'Compare horas economizadas no fechamento vs. preço do Pack. Ofereça piloto em 1 área.'],
                ],
                'ajuda_url' => $ajuda,
            ],
            'RESTAURANTE' => [
                'produto' => 'Spice',
                'pitch' => 'Food: foque fluxo de caixa, delivery e controle de estoque. Spice encaixa no dia a dia da cozinha.',
                'objecoes' => [
                    ['titulo' => 'Não tenho tempo agora', 'resposta' => 'Volte após 14h (janela de ouro). Deixe cardápio digital do pitch em 30s.'],
                    ['titulo' => 'Uso planilha', 'resposta' => 'Mostre perda de margem no delivery sem conciliação. Spice fecha o caixa no mesmo dia.'],
                ],
                'ajuda_url' => $ajuda,
            ],
            'VAREJO' => [
                'produto' => 'Pack PDV',
                'pitch' => 'Varejo: confirme volume de NF-e e PDV. Pack PDV + estoque reduz ruptura.',
                'objecoes' => [
                    ['titulo' => 'Meu PDV funciona', 'resposta' => 'Pergunte sobre inventário e omnichannel. Mostre ruptura que o Pack evita.'],
                    ['titulo' => 'Sem orçamento', 'resposta' => 'Comece pelo módulo que paga o investimento em 60 dias (estoque ou fiscal).'],
                ],
                'ajuda_url' => $ajuda,
            ],
            default => [
                'produto' => 'Diagnóstico',
                'pitch' => 'Lead novo — confirme porte, decisor e dor principal na recepção antes de pitchar produto.',
                'objecoes' => [
                    ['titulo' => 'Não conheço Alterdata', 'resposta' => 'Abra a Ajuda Alterdata e mostre 2 cases do segmento.'],
                    ['titulo' => 'Só quero preço', 'resposta' => 'Troque por diagnóstico rápido: dor → módulo → faixa. Evite tabela solta.'],
                ],
                'ajuda_url' => $ajuda,
            ],
        };
    }

    private function normalizar(string $segmento): string
    {
        $s = mb_strtoupper(trim($segmento), 'UTF-8');
        $s = str_replace(['Á', 'À', 'Ã', 'Â'], 'A', $s);

        return match ($s) {
            'CONTABIL', 'CONTABILIDADE' => 'CONTABIL',
            'RESTAURANTE' => 'RESTAURANTE',
            'VAREJO' => 'VAREJO',
            default => $s,
        };
    }
}
