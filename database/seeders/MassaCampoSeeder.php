<?php

namespace Database\Seeders;

use App\Enums\StatusReceita;
use App\Enums\StatusVisita;
use App\Models\Localizacao;
use App\Models\Prospecto;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Massa visual para painel, mapa e tela de visitas (foto + áudio).
 */
class MassaCampoSeeder extends Seeder
{
    public function run(): void
    {
        $vendedor = User::query()->where('email', 'vendedor@prospecta.test')->first();
        $vendedorLivre = User::query()->where('email', 'vendedor.livre@prospecta.test')->first();

        if (! $vendedor) {
            $this->command?->warn('Rode DemoSeeder antes (usuários demo ausentes).');

            return;
        }

        $vendedores = array_values(array_filter([$vendedor, $vendedorLivre]));
        $vendedorIds = collect($vendedores)->pluck('id');

        $prospectos = [];
        foreach ($this->prospectosDemo() as $lead) {
            $prospectos[] = Prospecto::query()->updateOrCreate(
                ['cnpj' => $lead['cnpj']],
                $lead,
            );
        }

        Visita::query()
            ->whereIn('user_id', $vendedorIds)
            ->whereHas('prospecto', fn ($q) => $q->where('origem', 'DEMO'))
            ->delete();

        Storage::disk('local')->makeDirectory('visitas/fotos');
        Storage::disk('local')->makeDirectory('visitas/audios');

        $statuses = [
            StatusVisita::Feita,
            StatusVisita::Feita,
            StatusVisita::Retorno,
            StatusVisita::SemNinguem,
        ];

        foreach ($prospectos as $i => $prospecto) {
            $user = $vendedores[$i % count($vendedores)];
            $status = $statuses[$i % count($statuses)];
            $quando = now()->subHours($i * 2)->subMinutes($i * 7);

            $fotoPath = null;
            $audioPath = null;

            if (in_array($status, [StatusVisita::Feita, StatusVisita::Retorno], true)) {
                $fotoPath = $this->gerarFotoDemo($prospecto->razao_social, $i);
                if ($status === StatusVisita::Feita) {
                    $audioPath = $this->gerarAudioDemo($i);
                }
            }

            Visita::query()->create([
                'prospecto_id' => $prospecto->id,
                'user_id' => $user->id,
                'status' => $status,
                'checkin_lat' => $prospecto->lat + fake()->randomFloat(5, -0.0008, 0.0008),
                'checkin_lng' => $prospecto->lng + fake()->randomFloat(5, -0.0008, 0.0008),
                'caminho_foto' => $fotoPath,
                'caminho_audio' => $audioPath,
                'created_at' => $quando,
                'updated_at' => $quando,
            ]);
        }

        Localizacao::query()->whereIn('user_id', $vendedorIds)->delete();

        foreach ($vendedores as $vi => $user) {
            $base = $prospectos[$vi % max(1, count($prospectos))];
            for ($p = 0; $p < 4; $p++) {
                Localizacao::query()->create([
                    'user_id' => $user->id,
                    'lat' => $base->lat + ($p * 0.004) + ($vi * 0.01),
                    'lng' => $base->lng + ($p * 0.003) - ($vi * 0.008),
                    'precisao' => 12 + $p,
                    'velocidade' => $p === 0 ? 0 : 4.2,
                    'direcao' => 90 + ($p * 20),
                    'capturado_em' => now()->subMinutes(2 + $p),
                ]);
            }
        }

        $this->command?->info('Massa de campo: '.count($prospectos).' prospectos DEMO, visitas com mídia e GPS ao vivo.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function prospectosDemo(): array
    {
        $base = [
            ['Contábil Atlântica LTDA', '11222333000181', -22.9711, -43.1822, true, 'Av. Atlântica, 1702 — Copacabana'],
            ['Escritório Torre B ME', '22333444000192', -22.9712, -43.1823, false, 'Av. Atlântica, 1702 — Copacabana'],
            ['Padaria Posto 5', '44555666000114', -22.9735, -43.1850, false, 'Rua Francisco Otaviano, 50'],
            ['Café Ipanema Gourmet', '55666777000125', -22.9838, -43.2025, false, 'Rua Visconde de Pirajá, 414'],
            ['Restaurante Spice Demo', '66777888000136', -22.9865, -43.1980, true, 'Rua Aníbal de Mendonça, 120'],
            ['Mercado Leblon Mix', '77888999000147', -22.9840, -43.2230, false, 'Av. Ataulfo de Paiva, 980'],
            ['Tech Botafogo SA', '88999000000158', -22.9510, -43.1825, false, 'Praia de Botafogo, 300'],
            ['Clínica Leme Saúde', '99000111000169', -22.9630, -43.1705, false, 'Av. Prado Júnior, 88'],
            ['Ótica Copacabana', '10111222000170', -22.9700, -43.1860, false, 'Rua Barata Ribeiro, 502'],
            ['Academia Zona Sul', '12131415000181', -22.9755, -43.1910, true, 'Rua Santa Clara, 210'],
            ['Pet Shop Leme', '13141516000192', -22.9620, -43.1680, false, 'Rua Gustavo Sampaio, 55'],
            ['Barbearia Arpoador', '14151617000103', -22.9885, -43.1920, false, 'Rua Francisco Otaviano, 90'],
            ['Farmácia 24h Posto 6', '15161718000114', -22.9780, -43.1895, false, 'Av. Nossa Sra. de Copacabana, 800'],
            ['Escola de Idiomas', '16171819000125', -22.9685, -43.1840, false, 'Rua Figueiredo Magalhães, 300'],
            ['Cowork Centro RJ', '17181920000136', -22.9068, -43.1729, false, 'Av. Rio Branco, 156'],
            ['Contábil Centro Pack', '18192021000147', -22.9050, -43.1755, true, 'Rua da Assembleia, 10'],
            ['Restaurante Flamengo', '19202122000158', -22.9320, -43.1750, false, 'Praia do Flamengo, 66'],
            ['Loja Catete Moda', '20212223000169', -22.9265, -43.1780, false, 'Rua do Catete, 228'],
            ['Dentista Humaitá', '21222324000170', -22.9560, -43.1985, false, 'Rua Humaitá, 80'],
            ['Startup Urca Soft', '22232425000181', -22.9480, -43.1650, false, 'Av. Pasteur, 250'],
            ['Hotel Copa Business', '23242526000192', -22.9690, -43.1805, false, 'Av. Atlântica, 4240'],
            ['Advocacia Lapa', '24252627000103', -22.9130, -43.1800, false, 'Rua do Riachuelo, 124'],
            ['Studio Yoga Leblon', '25262728000114', -22.9870, -43.2270, false, 'Rua Dias Ferreira, 190'],
            ['Pizzaria Jardim Botânico', '26272829000125', -22.9635, -43.2200, false, 'Rua Jardim Botânico, 700'],
            ['Auto Peças São Cristóvão', '27282930000136', -22.9005, -43.2205, false, 'Av. Pedro II, 50'],
            ['Lead Volta Redonda', '33444555000103', -22.5202, -44.0996, false, 'Av. Paulo Erlei Alves Abrantes, 90'],
        ];

        $out = [];
        foreach ($base as [$nome, $cnpj, $lat, $lng, $cliente, $end]) {
            $out[] = [
                'cnpj' => $cnpj,
                'razao_social' => $nome,
                'endereco' => $end,
                'telefone' => '21'.fake()->numerify('########'),
                'cep' => fake()->numerify('22######'),
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
            Storage::disk('local')->put($path, base64_decode(
                '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//Z'
            ));
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
