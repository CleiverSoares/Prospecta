<?php

namespace Tests;

trait ComSetupDiario
{
    /**
     * @return array<string, mixed>
     */
    protected function sessionSetup(array $extra = []): array
    {
        return [
            'prospecta.setup' => array_merge([
                'local' => 'Belo Horizonte',
                'segmento' => 'MISTO',
                'horas' => '08:00-17:00',
                'mix_prospeccao' => 80,
                'salvo_em' => now()->toIso8601String(),
            ], $extra),
        ];
    }
}
