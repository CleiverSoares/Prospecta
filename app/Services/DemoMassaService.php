<?php

namespace App\Services;

use App\Enums\StatusReceita;
use App\Repositories\LocalizacaoRepository;
use App\Repositories\ProspectoRepository;
use App\Repositories\UsuarioRepository;
use Database\Seeders\DemoSeeder;
use Database\Seeders\MassaCampoSeeder;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class DemoMassaService
{
    private const EMAIL_VENDEDOR = 'vendedor@prospecta.test';

    private const ORIGEM_MOCK_CLIENTE = 'MOCK_CLIENTE';

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
        private readonly ProspectoRepository $prospectoRepository,
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

        try {
            Artisan::call('db:seed', [
                '--class' => MassaCampoSeeder::class,
                '--force' => true,
            ]);
        } catch (Throwable $e) {
            report($e);
            throw $e;
        }

        $janela = (int) config('prospecta.tracking.janela_minutos', 15);

        return [
            'usuarios_demo' => $this->usuarioRepository->contarPorEmails(self::EMAILS_DEMO),
            'localizacoes_recentes' => $this->localizacaoRepository->contarRecentes($janela),
            'vendedores_ao_vivo' => $this->localizacaoRepository->contarVendedoresComSinal($janela),
        ];
    }

    /**
     * Gera pins azuis de cliente no mapa (origem MOCK_CLIENTE).
     *
     * @return array{criados: int, clientes_no_mapa: int}
     */
    public function gerarClientesMock(): array
    {
        $criados = $this->prospectoRepository->upsertClientesMock($this->clientesMock());

        return [
            'criados' => $criados,
            'clientes_no_mapa' => $this->prospectoRepository->contarClientes(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function clientesMock(): array
    {
        // Espalhados pelo Brasil (não só RJ) para o Painel ler como rede nacional.
        $base = [
            ['90010011000101', 'Cliente Mock — Contábil São Paulo', -23.5505, -46.6333, '01310100', 'Av. Paulista, 1000 — São Paulo'],
            ['90010022000112', 'Cliente Mock — Clínica Campinas', -22.9099, -47.0626, '13010000', 'Av. Francisco Glicério, 800 — Campinas'],
            ['90010033000123', 'Cliente Mock — Mercado Rio Centro', -22.9068, -43.1729, '20040020', 'Av. Rio Branco, 156 — Rio de Janeiro'],
            ['90010044000134', 'Cliente Mock — Hotel BH Savassi', -19.9386, -43.9345, '30130100', 'Rua Pernambuco, 1000 — Belo Horizonte'],
            ['90010055000145', 'Cliente Mock — Cowork Curitiba', -25.4284, -49.2733, '80010000', 'Rua XV de Novembro, 500 — Curitiba'],
            ['90010066000156', 'Cliente Mock — Loja Porto Alegre', -30.0346, -51.2177, '90010000', 'Av. Borges de Medeiros, 400 — Porto Alegre'],
            ['90010077000167', 'Cliente Mock — Restaurante Florianópolis', -27.5954, -48.5480, '88010000', 'Rua Felipe Schmidt, 200 — Florianópolis'],
            ['90010088000178', 'Cliente Mock — Oficina Brasília', -15.7801, -47.9292, '70040902', 'SCS Quadra 2 — Brasília'],
            ['90010099000189', 'Cliente Mock — Metalúrgica Goiânia', -16.6869, -49.2648, '74003010', 'Av. Goiás, 300 — Goiânia'],
            ['90010100000190', 'Cliente Mock — Contábil Salvador', -12.9714, -38.5014, '40020000', 'Av. Sete de Setembro, 50 — Salvador'],
            ['90010111000101', 'Cliente Mock — Farmácia Recife', -8.0476, -34.8770, '50010000', 'Av. Conde da Boa Vista, 100 — Recife'],
            ['90010122000112', 'Cliente Mock — Academia Fortaleza', -3.7319, -38.5267, '60010000', 'Av. Beira Mar, 2500 — Fortaleza'],
            ['90010133000123', 'Cliente Mock — Tech Manaus', -3.1190, -60.0217, '69005040', 'Av. Eduardo Ribeiro, 520 — Manaus'],
            ['90010144000134', 'Cliente Mock — Comércio Belém', -1.4558, -48.4902, '66010000', 'Av. Presidente Vargas, 800 — Belém'],
            ['90010155000145', 'Cliente Mock — Advocacia Natal', -5.7945, -35.2110, '59010000', 'Av. Rio Branco, 300 — Natal'],
            ['90010166000156', 'Cliente Mock — Escola Vitória', -20.3155, -40.3128, '29010010', 'Av. Nossa Senhora da Penha, 100 — Vitória'],
        ];

        $out = [];
        foreach ($base as [$cnpj, $nome, $lat, $lng, $cep, $endereco]) {
            $out[] = [
                'cnpj' => $cnpj,
                'razao_social' => $nome,
                'lat' => $lat,
                'lng' => $lng,
                'cep' => $cep,
                'endereco' => $endereco,
                'is_cliente' => true,
                'origem' => self::ORIGEM_MOCK_CLIENTE,
                'status_receita' => StatusReceita::Ativa,
            ];
        }

        return $out;
    }
}
