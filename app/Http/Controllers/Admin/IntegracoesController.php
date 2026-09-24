<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class IntegracoesController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.integracoes', [
            'telegram' => [
                'habilitado' => (bool) config('prospecta.telegram.enabled'),
                'tem_token' => filled(config('prospecta.telegram.bot_token')),
                'tem_chat_adm' => filled(config('prospecta.telegram.chat_adm')),
                'tem_chat_gestor' => filled(config('prospecta.telegram.chat_gestor')),
                'avisos' => [
                    'fora' => (bool) config('prospecta.telegram.avisos.fora_territorio'),
                    'visita' => (bool) config('prospecta.telegram.avisos.visita_concluida'),
                ],
            ],
            'integracoes' => [
                [
                    'nome' => config('prospecta.integracoes_fake.ploomes', 'Ploomes'),
                    'descricao' => 'CNPJ, contato, foto e áudio da visita para o funil.',
                ],
                [
                    'nome' => config('prospecta.integracoes_fake.rd', 'RD Station'),
                    'descricao' => 'Leads de campo e status de visita.',
                ],
                [
                    'nome' => config('prospecta.integracoes_fake.active', 'ActiveCampaign'),
                    'descricao' => 'Automação pós-visita e follow-up.',
                ],
            ],
        ]);
    }
}
