<?php

namespace Tests\Feature;

use Tests\TestCase;

class FumacaAplicacaoTest extends TestCase
{
    public function test_pagina_inicial_redireciona_para_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_config_prospecta_esta_carregada(): void
    {
        $this->assertIsArray(config('prospecta'));
    }
}
