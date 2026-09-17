<?php

namespace Tests\Feature;

use App\Services\GuiaBolsoService;
use Tests\TestCase;

class GuiaBolsoServiceTest extends TestCase
{
    public function test_retorna_pitch_e_objecoes_por_segmento(): void
    {
        config(['prospecta.ajuda_alterdata_url' => 'https://ajuda.example.test']);

        $guia = app(GuiaBolsoService::class)->para('RESTAURANTE');

        $this->assertSame('Spice', $guia['produto']);
        $this->assertNotEmpty($guia['pitch']);
        $this->assertGreaterThanOrEqual(1, count($guia['objecoes']));
        $this->assertSame('https://ajuda.example.test', $guia['ajuda_url']);
    }

    public function test_cliente_recebe_upsell(): void
    {
        $guia = app(GuiaBolsoService::class)->para('MISTO', true);

        $this->assertStringContainsString('cliente alterdata', mb_strtolower($guia['pitch']));
        $this->assertSame('Upsell base', $guia['produto']);
    }
}
