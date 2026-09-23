<?php

namespace Database\Seeders;

use App\Enums\StatusReceita;
use App\Enums\TipoUnidade;
use App\Models\Prospecto;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Unidades com nomes oficiais Alterdata (perto-de-voce) + pessoas realistas.
 * Fonte: https://www.alterdata.com.br/sobre/perto-de-voce/regiao-sudeste
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Migra nomes antigos do seed (se existirem)
        Unidade::query()->where('nome', 'Filial Rio')->update(['nome' => 'Filial Rio de Janeiro — Barra']);
        Unidade::query()->where('nome', 'Representação Sul Fluminense')->update(['nome' => 'Representação Volta Redonda']);

        $filialBarra = Unidade::query()->updateOrCreate(
            ['nome' => 'Filial Rio de Janeiro — Barra'],
            [
                'tipo' => TipoUnidade::Filial,
                'cep_inicio' => '20000000',
                'cep_fim' => '22799999',
                // Contorno Zona Sul / Centro / Barra (aproximado)
                'poligono_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [-43.230, -22.895],
                        [-43.175, -22.888],
                        [-43.145, -22.905],
                        [-43.150, -22.940],
                        [-43.160, -22.970],
                        [-43.168, -22.995],
                        [-43.195, -23.015],
                        [-43.230, -23.008],
                        [-43.255, -22.985],
                        [-43.265, -22.955],
                        [-43.250, -22.925],
                        [-43.230, -22.895],
                    ]],
                ],
            ],
        );

        $repVolta = Unidade::query()->updateOrCreate(
            ['nome' => 'Representação Volta Redonda'],
            [
                'tipo' => TipoUnidade::Representacao,
                'cep_inicio' => '27000000',
                'cep_fim' => '27999999',
                'poligono_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [-44.180, -22.480],
                        [-44.050, -22.455],
                        [-43.960, -22.490],
                        [-43.920, -22.540],
                        [-43.945, -22.600],
                        [-44.020, -22.630],
                        [-44.110, -22.615],
                        [-44.175, -22.570],
                        [-44.195, -22.520],
                        [-44.180, -22.480],
                    ]],
                ],
            ],
        );

        Unidade::query()->updateOrCreate(
            ['nome' => 'Unidade Cabo Frio'],
            [
                'tipo' => TipoUnidade::Representacao,
                'cep_inicio' => '28900000',
                'cep_fim' => '28999999',
                'poligono_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [-42.060, -22.840],
                        [-41.980, -22.820],
                        [-41.920, -22.860],
                        [-41.940, -22.920],
                        [-42.010, -22.940],
                        [-42.070, -22.900],
                        [-42.060, -22.840],
                    ]],
                ],
            ],
        );

        $adm = User::query()->updateOrCreate(
            ['email' => 'adm@prospecta.test'],
            [
                'name' => 'Renata Oliveira',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'unidade_id' => null,
                'gestor_id' => null,
                'ativo' => true,
                'foto_path' => $this->baixarFotoUsuario('renata-oliveira', 5),
            ],
        );
        $adm->syncRoles(['adm']);

        $gestor = User::query()->updateOrCreate(
            ['email' => 'gestor@prospecta.test'],
            [
                'name' => 'Bruno Carvalho',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'unidade_id' => $filialBarra->id,
                'gestor_id' => null,
                'ativo' => true,
                'foto_path' => $this->baixarFotoUsuario('bruno-carvalho', 33),
            ],
        );
        $gestor->syncRoles(['gestor']);

        $vendedorBarra = User::query()->updateOrCreate(
            ['email' => 'vendedor@prospecta.test'],
            [
                'name' => 'Camila Ferreira',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'unidade_id' => $filialBarra->id,
                'gestor_id' => $gestor->id,
                'cep_base_inicio' => '22010000',
                'cep_base_fim' => '22080000',
                'ativo' => true,
                'foto_path' => $this->baixarFotoUsuario('camila-ferreira', 32),
            ],
        );
        $vendedorBarra->syncRoles(['vendedor']);

        $vendedorVolta = User::query()->updateOrCreate(
            ['email' => 'vendedor.livre@prospecta.test'],
            [
                'name' => 'Diego Santos',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'unidade_id' => $repVolta->id,
                'gestor_id' => $gestor->id,
                'cep_base_inicio' => '27200000',
                'cep_base_fim' => '27399999',
                'ativo' => true,
                'foto_path' => $this->baixarFotoUsuario('diego-santos', 68),
            ],
        );
        $vendedorVolta->syncRoles(['vendedor']);

        Prospecto::query()->updateOrCreate(
            ['cnpj' => '11222333000181'],
            [
                'razao_social' => 'Contábil Atlântica Pack DP',
                'endereco' => 'Av. Atlântica, 1702 — Copacabana',
                'telefone' => '2133334444',
                'cep' => '22021001',
                'lat' => -22.9711,
                'lng' => -43.1822,
                'status_receita' => StatusReceita::Ativa,
                'is_cliente' => true,
                'origem' => 'DEMO',
            ],
        );

        Prospecto::query()->updateOrCreate(
            ['cnpj' => '22333444000192'],
            [
                'razao_social' => 'Escritório Torre B Contábil',
                'endereco' => 'Av. Atlântica, 1702 — Copacabana',
                'telefone' => '2133335555',
                'cep' => '22021001',
                'lat' => -22.9712,
                'lng' => -43.1823,
                'status_receita' => StatusReceita::Ativa,
                'is_cliente' => false,
                'origem' => 'DEMO',
            ],
        );

        Prospecto::query()->updateOrCreate(
            ['cnpj' => '33444555000103'],
            [
                'razao_social' => 'Metalúrgica Sul Fluminense LTDA',
                'endereco' => 'Av. Paulo Erlei Alves Abrantes, 90 — Volta Redonda',
                'cep' => '27253065',
                'lat' => -22.5202,
                'lng' => -44.0996,
                'status_receita' => StatusReceita::Ativa,
                'is_cliente' => false,
                'origem' => 'DEMO',
            ],
        );
    }

    /**
     * Baixa avatar (pravatar) ou gera placeholder local.
     */
    private function baixarFotoUsuario(string $slug, int $pravatarId): string
    {
        $path = 'usuarios/fotos/'.$slug.'.jpg';
        $full = Storage::disk('public')->path($path);
        File::ensureDirectoryExists(dirname($full));

        try {
            $resposta = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'ProspectaSeeder/1.0'])
                ->get('https://i.pravatar.cc/256', ['img' => $pravatarId]);

            if ($resposta->successful() && strlen($resposta->body()) > 500) {
                File::put($full, $resposta->body());

                return $path;
            }
        } catch (\Throwable) {
            // fallback abaixo
        }

        return $this->gerarFotoFallback($slug, $path, $full);
    }

    private function gerarFotoFallback(string $slug, string $path, string $full): string
    {
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(256, 256);
            $bg = imagecolorallocate($img, 0, 131, 193);
            $fg = imagecolorallocate($img, 255, 255, 255);
            imagefilledrectangle($img, 0, 0, 256, 256, $bg);
            $iniciais = mb_strtoupper(mb_substr(str_replace('-', ' ', $slug), 0, 2));
            imagestring($img, 5, 108, 120, $iniciais, $fg);
            imagejpeg($img, $full, 88);
            imagedestroy($img);
        } else {
            Storage::disk('public')->put($path, '');
        }

        return $path;
    }
}
