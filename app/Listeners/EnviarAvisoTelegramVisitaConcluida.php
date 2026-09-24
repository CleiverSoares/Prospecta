<?php

namespace App\Listeners;

use App\Enums\StatusVisita;
use App\Events\VisitaConcluida;
use App\Services\TelegramAvisoService;

class EnviarAvisoTelegramVisitaConcluida
{
    public function __construct(
        private readonly TelegramAvisoService $telegramAvisoService,
    ) {}

    public function handle(VisitaConcluida $event): void
    {
        if (! config('prospecta.telegram.avisos.visita_concluida')) {
            return;
        }

        $visita = $event->visita->loadMissing(['prospecto', 'usuario.unidade']);
        $status = $visita->status instanceof StatusVisita
            ? $visita->status->value
            : (string) $visita->status;

        if ($status !== StatusVisita::Feita->value) {
            return;
        }

        $vendedor = $visita->usuario?->name ?? 'Vendedor';
        $empresa = $visita->prospecto?->razao_social ?? 'Prospecto';
        $tipo = $visita->prospecto?->is_cliente ? 'Cliente' : 'Lead';
        $unidade = $visita->usuario?->unidade?->nome ?? '—';

        $texto = implode("\n", [
            '✅ Prospecta — visita feita',
            "Vendedor: {$vendedor}",
            "Unidade: {$unidade}",
            "{$tipo}: {$empresa}",
            'Horário: '.($visita->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i')),
        ]);

        $this->telegramAvisoService->avisar(
            'visita:'.$visita->id,
            $texto,
            ['adm', 'gestor'],
        );
    }
}
