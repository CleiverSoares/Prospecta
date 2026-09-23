<?php

namespace Database\Seeders;

use App\Enums\StatusReceita;
use App\Enums\StatusVisita;
use App\Models\Localizacao;
use App\Models\Prospecto;
use App\Models\RotaDia;
use App\Models\User;
use App\Models\Visita;
use App\Services\RotaDiaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Massa visual: visitas no território certo de cada vendedor + trajeto GPS
 * que passa pelos check-ins (não uma reta aleatória entre 4 pontos).
 */
class MassaCampoSeeder extends Seeder
{
    public function run(): void
    {
        $camila = User::query()->where('email', 'vendedor@prospecta.test')->first();
        $diego = User::query()->where('email', 'vendedor.livre@prospecta.test')->first();

        if (! $camila) {
            $this->command?->warn('Rode DemoSeeder antes (usuários demo ausentes).');

            return;
        }

        $vendedores = array_values(array_filter([$camila, $diego]));
        $vendedorIds = collect($vendedores)->pluck('id');

        $prospectosPorChave = [];
        foreach ($this->prospectosDemo() as $lead) {
            $prospectosPorChave[$lead['chave']] = Prospecto::query()->updateOrCreate(
                ['cnpj' => $lead['cnpj']],
                collect($lead)->except('chave', 'territorio')->all(),
            );
        }

        Visita::query()
            ->whereIn('user_id', $vendedorIds)
            ->whereHas('prospecto', fn ($q) => $q->where('origem', 'DEMO'))
            ->delete();

        RotaDia::query()->whereIn('user_id', $vendedorIds)->whereDate('data', today())->delete();

        Storage::disk('local')->makeDirectory('visitas/fotos');
        Storage::disk('local')->makeDirectory('visitas/audios');

        $roteiros = $this->roteirosHoje($prospectosPorChave);
        $planosExtras = $this->paradasExtrasPendentes($prospectosPorChave);
        $criadas = 0;
        $rotaDiaService = app(RotaDiaService::class);

        foreach ($roteiros as $email => $paradas) {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                continue;
            }

            $visitasHoje = [];
            foreach ($paradas as $i => $parada) {
                $prospecto = $parada['prospecto'];
                $status = $parada['status'];
                $quando = $parada['quando'];

                $fotoPath = null;
                $audioPath = null;
                if (in_array($status, [StatusVisita::Feita, StatusVisita::Retorno], true)) {
                    $fotoPath = $this->gerarFotoDemo($prospecto->razao_social, $criadas);
                    if ($status === StatusVisita::Feita) {
                        $audioPath = $this->gerarAudioDemo($criadas);
                    }
                }

                $visita = Visita::query()->create([
                    'prospecto_id' => $prospecto->id,
                    'user_id' => $user->id,
                    'status' => $status,
                    'checkin_lat' => $prospecto->lat + fake()->randomFloat(5, -0.0004, 0.0004),
                    'checkin_lng' => $prospecto->lng + fake()->randomFloat(5, -0.0004, 0.0004),
                    'caminho_foto' => $fotoPath,
                    'caminho_audio' => $audioPath,
                    'created_at' => $quando,
                    'updated_at' => $quando,
                ]);
                $visitasHoje[] = $visita;
                $criadas++;
            }

            // Plano = visitas do dia + eventuais pendentes (ex.: falta 1 pra meta visual)
            $itensPlano = [];
            foreach ($paradas as $i => $parada) {
                $itensPlano[] = [
                    'id' => $parada['prospecto']->id,
                    'ordem' => $i + 1,
                    'lat' => $parada['prospecto']->lat,
                    'lng' => $parada['prospecto']->lng,
                ];
            }
            foreach ($planosExtras[$email] ?? [] as $extra) {
                $itensPlano[] = [
                    'id' => $extra['prospecto']->id,
                    'ordem' => count($itensPlano) + 1,
                    'lat' => $extra['prospecto']->lat,
                    'lng' => $extra['prospecto']->lng,
                ];
            }

            $rotaDiaService->publicar($user, $itensPlano);
            foreach ($visitasHoje as $visita) {
                $rotaDiaService->marcarCheckin($visita);
            }

            // Histórico (ontem) — só Camila, para a tela de visitas não ficar vazia
            if ($email === 'vendedor@prospecta.test') {
                foreach ($this->paradasOntem($prospectosPorChave) as $j => $parada) {
                    Visita::query()->create([
                        'prospecto_id' => $parada['prospecto']->id,
                        'user_id' => $user->id,
                        'status' => $parada['status'],
                        'checkin_lat' => $parada['prospecto']->lat,
                        'checkin_lng' => $parada['prospecto']->lng,
                        'caminho_foto' => $this->gerarFotoDemo($parada['prospecto']->razao_social, 100 + $j),
                        'caminho_audio' => null,
                        'created_at' => $parada['quando'],
                        'updated_at' => $parada['quando'],
                    ]);
                    $criadas++;
                }
            }
        }

        Localizacao::query()->whereIn('user_id', $vendedorIds)->delete();

        foreach ($roteiros as $email => $paradas) {
            $user = User::query()->where('email', $email)->first();
            if (! $user || $paradas === []) {
                continue;
            }

            $this->semearTrajetoPelosCheckins($user->id, $paradas);
        }

        $this->command?->info("Massa de campo: {$criadas} visitas DEMO + planos do dia + trajetos GPS.");
    }

    /**
     * Paradas extras só no plano (sem check-in) — mostra pendente na Agenda.
     *
     * @param  array<string, Prospecto>  $map
     * @return array<string, list<array{prospecto: Prospecto}>>
     */
    private function paradasExtrasPendentes(array $map): array
    {
        $out = [];
        // Camila: 8 check-ins + 1 pendente → 8/9 no plano
        if (isset($map['sc-auto'])) {
            $out['vendedor@prospecta.test'] = [['prospecto' => $map['sc-auto']]];
        }
        // Diego: 7 check-ins + 1 pendente → 7/8 no plano
        if (isset($map['jb-pizza'])) {
            $out['vendedor.livre@prospecta.test'] = [['prospecto' => $map['jb-pizza']]];
        }

        return $out;
    }

    /**
     * Roteiro do dia: Camila na Filial RJ (Zona Sul/Centro), Diego em Volta Redonda.
     *
     * @param  array<string, Prospecto>  $map
     * @return array<string, list<array{prospecto: Prospecto, status: StatusVisita, quando: \Carbon\Carbon}>>
     */
    private function roteirosHoje(array $map): array
    {
        $hoje = today();

        $camila = [
            ['copa-contabil', StatusVisita::Feita, 8, 10],
            ['copa-torre', StatusVisita::SemNinguem, 8, 45],
            ['botafogo-tech', StatusVisita::Feita, 9, 30],
            ['humaita-dentista', StatusVisita::Feita, 10, 20],
            ['flamengo-rest', StatusVisita::Retorno, 11, 5],
            ['centro-cowork', StatusVisita::Feita, 12, 15],
            ['centro-contabil', StatusVisita::SemNinguem, 13, 0],
            ['botafogo-urca', StatusVisita::Feita, 14, 10],
        ];

        $diego = [
            ['vr-metalurgica', StatusVisita::Feita, 8, 30],
            ['vr-contabil', StatusVisita::Feita, 9, 20],
            ['vr-comercio', StatusVisita::SemNinguem, 10, 10],
            ['vr-oficina', StatusVisita::Feita, 11, 0],
            ['vr-escola', StatusVisita::Retorno, 12, 0],
            ['vr-farmacia', StatusVisita::Feita, 13, 15],
            ['vr-advocacia', StatusVisita::Feita, 14, 20],
        ];

        $montar = function (array $lista) use ($map, $hoje): array {
            $out = [];
            foreach ($lista as [$chave, $status, $h, $m]) {
                if (! isset($map[$chave])) {
                    continue;
                }
                $quando = $hoje->copy()->setTime($h, $m);
                if ($quando->greaterThan(now())) {
                    $quando = now()->subMinutes(8 + (count($out) * 14));
                }
                $out[] = [
                    'prospecto' => $map[$chave],
                    'status' => $status,
                    'quando' => $quando,
                ];
            }

            return $out;
        };

        return [
            'vendedor@prospecta.test' => $montar($camila),
            'vendedor.livre@prospecta.test' => $montar($diego),
        ];
    }

    /**
     * @param  array<string, Prospecto>  $map
     * @return list<array{prospecto: Prospecto, status: StatusVisita, quando: \Carbon\Carbon}>
     */
    private function paradasOntem(array $map): array
    {
        $ontem = today()->subDay();
        $chaves = ['leblon-mercado', 'ipanema-cafe', 'copa-otica', 'leme-clinica'];
        $out = [];
        foreach ($chaves as $i => $chave) {
            if (! isset($map[$chave])) {
                continue;
            }
            $out[] = [
                'prospecto' => $map[$chave],
                'status' => StatusVisita::Feita,
                'quando' => $ontem->copy()->setTime(9 + $i, 15),
            ];
        }

        return $out;
    }

    /**
     * Interpola pontos GPS entre as paradas do dia → linha que “anda” pelo mapa.
     *
     * @param  list<array{prospecto: Prospecto, status: StatusVisita, quando: \Carbon\Carbon}>  $paradas
     */
    private function semearTrajetoPelosCheckins(int $userId, array $paradas): void
    {
        $waypoints = [];
        foreach ($paradas as $parada) {
            $waypoints[] = [
                'lat' => (float) $parada['prospecto']->lat,
                'lng' => (float) $parada['prospecto']->lng,
                'em' => $parada['quando']->copy(),
            ];
        }

        if (count($waypoints) < 2) {
            return;
        }

        $pontos = [];
        for ($i = 0; $i < count($waypoints) - 1; $i++) {
            $a = $waypoints[$i];
            $b = $waypoints[$i + 1];
            $passos = 6;
            for ($s = 0; $s < $passos; $s++) {
                $t = $s / $passos;
                // leve desvio pra não ficar reta geométrica perfeita
                $jitterLat = sin(($i + $s) * 1.7) * 0.00035;
                $jitterLng = cos(($i + $s) * 1.3) * 0.00035;
                $pontos[] = [
                    'lat' => $a['lat'] + (($b['lat'] - $a['lat']) * $t) + $jitterLat,
                    'lng' => $a['lng'] + (($b['lng'] - $a['lng']) * $t) + $jitterLng,
                    'em' => $a['em']->copy()->addSeconds((int) ($s * max(60, $a['em']->diffInSeconds($b['em']) / $passos))),
                ];
            }
        }

        $ultimo = $waypoints[array_key_last($waypoints)];
        $pontos[] = [
            'lat' => $ultimo['lat'] + 0.0002,
            'lng' => $ultimo['lng'] + 0.00015,
            'em' => now()->subMinutes(1),
        ];

        foreach ($pontos as $idx => $p) {
            Localizacao::query()->create([
                'user_id' => $userId,
                'lat' => $p['lat'],
                'lng' => $p['lng'],
                'precisao' => 8 + ($idx % 5),
                'velocidade' => $idx === array_key_last($pontos) ? 0 : 3.8 + ($idx % 3),
                'direcao' => 40 + (($idx * 17) % 280),
                'capturado_em' => $p['em'],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function prospectosDemo(): array
    {
        $base = [
            // Filial RJ — Barra / Zona Sul / Centro
            ['copa-contabil', 'Contábil Atlântica Pack DP', '11222333000181', -22.9711, -43.1822, '22021001', true, 'Av. Atlântica, 1702 — Copacabana'],
            ['copa-torre', 'Escritório Torre B Contábil', '22333444000192', -22.9712, -43.1823, '22021001', false, 'Av. Atlântica, 1702 — Copacabana'],
            ['copa-padaria', 'Padaria Posto 5 ME', '44555666000114', -22.9735, -43.1850, '22070002', false, 'Rua Francisco Otaviano, 50 — Copacabana'],
            ['ipanema-cafe', 'Café Ipanema Gourmet LTDA', '55666777000125', -22.9838, -43.2025, '22410003', false, 'Rua Visconde de Pirajá, 414 — Ipanema'],
            ['ipanema-spice', 'Restaurante Spice Ipanema', '66777888000136', -22.9865, -43.1980, '22410000', true, 'Rua Aníbal de Mendonça, 120 — Ipanema'],
            ['leblon-mercado', 'Mercado Leblon Mix', '77888999000147', -22.9840, -43.2230, '22440030', false, 'Av. Ataulfo de Paiva, 980 — Leblon'],
            ['botafogo-tech', 'Tech Botafogo Sistemas SA', '88999000000158', -22.9510, -43.1825, '22250040', false, 'Praia de Botafogo, 300'],
            ['leme-clinica', 'Clínica Leme Saúde', '99000111000169', -22.9630, -43.1705, '22010040', false, 'Av. Prado Júnior, 88 — Leme'],
            ['copa-otica', 'Ótica Copacabana Visão', '10111222000170', -22.9700, -43.1860, '22040002', false, 'Rua Barata Ribeiro, 502 — Copacabana'],
            ['copa-academia', 'Academia Zona Sul Fit', '12131415000181', -22.9755, -43.1910, '22041010', true, 'Rua Santa Clara, 210 — Copacabana'],
            ['leme-pet', 'Pet Shop Leme Amigo', '13141516000192', -22.9620, -43.1680, '22010000', false, 'Rua Gustavo Sampaio, 55 — Leme'],
            ['arpoador-barba', 'Barbearia Arpoador', '14151617000103', -22.9885, -43.1920, '22080005', false, 'Rua Francisco Otaviano, 90 — Arpoador'],
            ['copa-farmacia', 'Farmácia 24h Posto 6', '15161718000114', -22.9780, -43.1895, '22050002', false, 'Av. Nossa Sra. de Copacabana, 800'],
            ['copa-escola', 'Escola de Idiomas Copacabana', '16171819000125', -22.9685, -43.1840, '22020001', false, 'Rua Figueiredo Magalhães, 300'],
            ['centro-cowork', 'Cowork Centro RJ', '17181920000136', -22.9068, -43.1729, '20040020', false, 'Av. Rio Branco, 156 — Centro'],
            ['centro-contabil', 'Contábil Centro Pack', '18192021000147', -22.9050, -43.1755, '20011000', true, 'Rua da Assembleia, 10 — Centro'],
            ['flamengo-rest', 'Restaurante Flamengo Vista', '19202122000158', -22.9320, -43.1750, '22210030', false, 'Praia do Flamengo, 66'],
            ['catete-moda', 'Loja Catete Moda', '20212223000169', -22.9265, -43.1780, '22220000', false, 'Rua do Catete, 228'],
            ['humaita-dentista', 'Clínica Dentista Humaitá', '21222324000170', -22.9560, -43.1985, '22261000', false, 'Rua Humaitá, 80'],
            ['botafogo-urca', 'Startup Urca Soft', '22232425000181', -22.9480, -43.1650, '22290040', false, 'Av. Pasteur, 250 — Urca'],
            ['copa-hotel', 'Hotel Copa Business', '23242526000192', -22.9690, -43.1805, '22021000', false, 'Av. Atlântica, 4240 — Copacabana'],
            ['lapa-adv', 'Advocacia Lapa & Associados', '24252627000103', -22.9130, -43.1800, '20230010', false, 'Rua do Riachuelo, 124 — Lapa'],
            ['leblon-yoga', 'Studio Yoga Leblon', '25262728000114', -22.9870, -43.2270, '22431050', false, 'Rua Dias Ferreira, 190 — Leblon'],
            ['jb-pizza', 'Pizzaria Jardim Botânico', '26272829000125', -22.9635, -43.2200, '22461000', false, 'Rua Jardim Botânico, 700'],
            ['sc-auto', 'Auto Peças São Cristóvão', '27282930000136', -22.9005, -43.2205, '20921060', false, 'Av. Pedro II, 50 — São Cristóvão'],

            // Representação Volta Redonda
            ['vr-metalurgica', 'Metalúrgica Sul Fluminense LTDA', '33444555000103', -22.5202, -44.0996, '27253065', false, 'Av. Paulo Erlei Alves Abrantes, 90 — Volta Redonda'],
            ['vr-contabil', 'Contábil Vila Santa Cecília', '30313233000148', -22.5235, -44.1040, '27255115', true, 'Rua 18 B, 45 — Vila Santa Cecília'],
            ['vr-comercio', 'Comércio Aço Sul ME', '31323334000159', -22.5180, -44.0920, '27213150', false, 'Av. Amaral Peixoto, 320 — Volta Redonda'],
            ['vr-oficina', 'Oficina Retífica VR', '32333435000160', -22.5280, -44.1085, '27253000', false, 'Rua Domingos Mariano, 110 — Volta Redonda'],
            ['vr-escola', 'Escola Técnica Sul Fluminense', '33343536000171', -22.5155, -44.1010, '27220030', false, 'Rua 42, 200 — Sessenta'],
            ['vr-farmacia', 'Farmácia Popular VR', '34353637000182', -22.5210, -44.0955, '27253120', false, 'Av. Lucas Evangelista, 88 — Aterrado'],
            ['vr-advocacia', 'Advocacia Ponte Alta', '35363738000193', -22.5108, -44.0880, '27210020', false, 'Rua 7 de Setembro, 55 — Centro VR'],
        ];

        $out = [];
        foreach ($base as [$chave, $nome, $cnpj, $lat, $lng, $cep, $cliente, $end]) {
            $out[] = [
                'chave' => $chave,
                'cnpj' => $cnpj,
                'razao_social' => $nome,
                'endereco' => $end,
                'telefone' => '21'.fake()->numerify('########'),
                'cep' => $cep,
                'lat' => $lat,
                'lng' => $lng,
                'status_receita' => StatusReceita::Ativa,
                'is_cliente' => $cliente,
                'origem' => 'DEMO',
            ];
        }

        return $out;
    }

    private function gerarFotoDemo(string $rotulo, int $i): string
    {
        $path = 'visitas/fotos/demo_'.$i.'_'.now()->format('His').'.jpg';
        $full = Storage::disk('local')->path($path);
        File::ensureDirectoryExists(dirname($full));

        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(640, 400);
            $bg = imagecolorallocate($img, 0, 131, 193);
            $mute = imagecolorallocate($img, 230, 244, 251);
            imagefilledrectangle($img, 0, 0, 640, 400, $bg);
            imagefilledrectangle($img, 24, 24, 616, 376, $mute);
            imagestring($img, 5, 40, 180, 'Fachada demo #'.($i + 1), $bg);
            imagestring($img, 3, 40, 210, substr($rotulo, 0, 48), $bg);
            imagejpeg($img, $full, 85);
            imagedestroy($img);
        } else {
            Storage::disk('local')->put($path, '');
        }

        return $path;
    }

    private function gerarAudioDemo(int $i): string
    {
        $path = 'visitas/audios/demo_'.$i.'_'.now()->format('His').'.wav';
        $sampleRate = 8000;
        $numSamples = (int) ($sampleRate * 0.4);
        $data = str_repeat("\x80", $numSamples);
        $dataSize = strlen($data);
        $wav = 'RIFF'.pack('V', 36 + $dataSize).'WAVEfmt '.pack('V', 16)
            .pack('v', 1)
            .pack('v', 1)
            .pack('V', $sampleRate)
            .pack('V', $sampleRate)
            .pack('v', 1)
            .pack('v', 8)
            .'data'.pack('V', $dataSize)
            .$data;

        Storage::disk('local')->put($path, $wav);

        return $path;
    }
}
