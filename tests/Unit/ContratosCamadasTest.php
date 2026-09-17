<?php

namespace Tests\Unit;

use App\Repositories\ProspectoRepository;
use App\Repositories\UnidadeRepository;
use App\Repositories\VisitaRepository;
use App\Services\ProspectoService;
use App\Services\TerritorioService;
use App\Services\UnidadeService;
use App\Services\VisitaService;
use PHPUnit\Framework\TestCase;

class ContratosCamadasTest extends TestCase
{
    public function test_classes_de_repository_e_service_existem(): void
    {
        $this->assertTrue(class_exists(UnidadeRepository::class));
        $this->assertTrue(class_exists(ProspectoRepository::class));
        $this->assertTrue(class_exists(VisitaRepository::class));
        $this->assertTrue(class_exists(UnidadeService::class));
        $this->assertTrue(class_exists(ProspectoService::class));
        $this->assertTrue(class_exists(VisitaService::class));
        $this->assertTrue(class_exists(TerritorioService::class));
    }
}
