<?php

namespace App\Services;

use App\Repositories\LocalizacaoRepository;
use App\Repositories\UsuarioRepository;
use Database\Seeders\DemoSeeder;
use Database\Seeders\MassaCampoSeeder;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Support\Facades\Artisan;

class DemoMassaService
{
    private const EMAIL_VENDEDOR = 'vendedor@prospecta.test';

    /** @var list<string> */
    private const EMAILS_DEMO = [
        'adm@prospecta.test',
        'gestor@prospecta.test',
        'vendedor@prospecta.test',
        'vendedor.livre@prospecta.test',
    ];

    public function __construct(
        private readonly UsuarioRepository $usuarioRepository,
        private readonly LocalizacaoRepository $localizacaoRepository,
    ) {}

    public function habilitado(): bool
    {
        return (bool) config('prospecta.demo_seed_enabled', false);
    }

    /**
     * Regenera massa de campo (visitas DEMO + trajetos GPS frescos para o Painel).
     *
     * @return array{usuarios_demo: int, localizacoes_recentes: int, vendedores_ao_vivo: int}
     */
    public function regenerarCampo(): array
    {
        if (! $this->usuarioRepository->existePorEmail(self::EMAIL_VENDEDOR)) {
            Artisan::call('db:seed', [
                '--class' => PapeisEPermissoesSeeder::class,
                '--force' => true,
            ]);
            Artisan::call('db:seed', [
                '--class' => DemoSeeder::class,
                '--force' => true,
            ]);
        }

        Artisan::call('db:seed', [
            '--class' => MassaCampoSeeder::class,
            '--force' => true,
        ]);

        $janela = (int) config('prospecta.tracking.janela_minutos', 15);

        return [
            'usuarios_demo' => $this->usuarioRepository->contarPorEmails(self::EMAILS_DEMO),
            'localizacoes_recentes' => $this->localizacaoRepository->contarRecentes($janela),
            'vendedores_ao_vivo' => $this->localizacaoRepository->contarVendedoresComSinal($janela),
        ];
    }
}
