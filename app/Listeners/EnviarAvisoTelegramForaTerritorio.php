<?php

namespace App\Listeners;

use App\Events\VendedorForaTerritorio;
use App\Services\TelegramAvisoService;

class EnviarAvisoTelegramForaTerritorio
{
    public function __construct(
        private readonly TelegramAvisoService $telegramAvisoService,
    ) {}

    public function handle(VendedorForaTerritorio $event): void
    {
        if (! config('prospecta.telegram.avisos.fora_territorio')) {
            return;
        }

        $v = $event->vendedor;
        $loc = $event->localizacao;
        $unidade = $v->unidade?->nome ?? 'sem unidade';
        $lat = number_format((float) $loc->lat, 5, '.', '');
        $lng = number_format((float) $loc->lng, 5, '.', '');

        $texto = implode("\n", [
            '🚨 Prospecta — fora do território',
            "Vendedor: {$v->name}",
            "Unidade: {$unidade}",
            "GPS: {$lat}, {$lng}",
            'Mapa: https://www.google.com/maps?q='.$lat.','.$lng,
            'Horário: '.now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ]);

        $this->telegramAvisoService->avisar(
            'fora:'.$v->id,
            $texto,
            ['adm', 'gestor'],
        );
    }
}
