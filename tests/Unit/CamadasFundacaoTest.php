<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CamadasFundacaoTest extends TestCase
{
    #[DataProvider('provedorPastasCamadas')]
    public function test_pasta_da_camada_existe(string $caminhoRelativo): void
    {
        $caminho = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $caminhoRelativo);

        $this->assertDirectoryExists($caminho);
    }

    public static function provedorPastasCamadas(): array
    {
        return [
            'Repositories' => ['app/Repositories'],
            'Services' => ['app/Services'],
            'Events' => ['app/Events'],
            'Listeners' => ['app/Listeners'],
            'Enums' => ['app/Enums'],
            'Policies' => ['app/Policies'],
            'Controllers Admin' => ['app/Http/Controllers/Admin'],
            'Controllers App' => ['app/Http/Controllers/App'],
        ];
    }
}
