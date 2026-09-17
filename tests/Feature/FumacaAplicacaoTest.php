<?php

namespace Tests\Feature;

use Tests\TestCase;

class FumacaAplicacaoTest extends TestCase
{
    public function test_pagina_inicial_responde_com_sucesso(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_config_prospecta_esta_carregada(): void
    {
        $this->assertIsArray(config('prospecta'));
    }
}
