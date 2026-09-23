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
        $base = [
            ['90010011000101', 'Cliente Mock — Contábil Barra', -22.9995, -43.3650, '22640102', 'Av. das Américas, 3434 — Barra'],
            ['90010022000112', 'Cliente Mock — Clínica Recreio', -23.0180, -43.4620, '22790701', 'Av. das Américas, 7000 — Recreio'],
            ['90010033000123', 'Cliente Mock — Mercado Copacabana', -22.9728, -43.1845, '22021000', 'Av. Nossa Sra. de Copacabana, 500'],
            ['90010044000134', 'Cliente Mock — Hotel Ipanema', -22.9845, -43.2090, '22410003', 'Rua Visconde de Pirajá, 100'],
            ['90010055000145', 'Cliente Mock — Cowork Botafogo', -22.9525, -43.1835, '22250040', 'Praia de Botafogo, 228'],
            ['90010066000156', 'Cliente Mock — Loja Centro', -22.9075, -43.1760, '20040020', 'Av. Rio Branco, 100'],
            ['90010077000167', 'Cliente Mock — Restaurante Lapa', -22.9125, -43.1795, '20230010', 'Rua do Riachuelo, 50'],
            ['90010088000178', 'Cliente Mock — Oficina São Cristóvão', -22.8990, -43.2215, '20921060', 'Av. Pedro II, 120'],
            ['90010099000189', 'Cliente Mock — Metalúrgica VR', -22.5220, -44.1010, '27253065', 'Av. Paulo Erlei, 200 — Volta Redonda'],
            ['90010100000190', 'Cliente Mock — Contábil VR', -22.5250, -44.1060, '27255115', 'Rua 18 A, 80 — Volta Redonda'],
            ['90010111000101', 'Cliente Mock — Farmácia Leblon', -22.9855, -43.2245, '22440030', 'Av. Ataulfo de Paiva, 500'],
            ['90010122000112', 'Cliente Mock — Academia Flamengo', -22.9310, -43.1765, '22210030', 'Praia do Flamengo, 100'],
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
