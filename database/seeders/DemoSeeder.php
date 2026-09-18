<?php

namespace Database\Seeders;

use App\Enums\StatusReceita;
use App\Enums\TipoUnidade;
use App\Models\Prospecto;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $filialRio = Unidade::query()->updateOrCreate(
            ['nome' => 'Filial Rio'],
            [
                'tipo' => TipoUnidade::Filial,
                'cep_inicio' => '20000000',
                'cep_fim' => '22799999',
                // Contorno irregular Zona Sul / Centro (não retângulo)
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

        $repSul = Unidade::query()->updateOrCreate(
            ['nome' => 'Representação Sul Fluminense'],
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

        $adm = User::query()->updateOrCreate(
            ['email' => 'adm@prospecta.test'],
            [
                'name' => 'Admin Prospecta',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'unidade_id' => null,
                'gestor_id' => null,
            ],
        );
        $adm->syncRoles(['adm']);

        $gestor = User::query()->updateOrCreate(
            ['email' => 'gestor@prospecta.test'],
            [
                'name' => 'Gestor Filial Rio',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'unidade_id' => $filialRio->id,
                'gestor_id' => null,
            ],
        );
        $gestor->syncRoles(['gestor']);

        $vendedor = User::query()->updateOrCreate(
            ['email' => 'vendedor@prospecta.test'],
            [
                'name' => 'Vendedor Copacabana',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'unidade_id' => $filialRio->id,
                'gestor_id' => $gestor->id,
                'cep_base_inicio' => '22010000',
                'cep_base_fim' => '22080000',
            ],
        );
        $vendedor->syncRoles(['vendedor']);

        $vendedorLivre = User::query()->updateOrCreate(
            ['email' => 'vendedor.livre@prospecta.test'],
            [
                'name' => 'Vendedor Área Livre',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'unidade_id' => null,
                'gestor_id' => $gestor->id,
            ],
        );
        $vendedorLivre->syncRoles(['vendedor']);

        // Prospecto cliente (upsell) + lead sintético para demos offline de rota
        Prospecto::query()->updateOrCreate(
            ['cnpj' => '11222333000181'],
            [
                'razao_social' => 'Contábil Demo Pack DP',
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
                'razao_social' => 'Escritório Vizinho Torre B',
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
                'razao_social' => 'Lead Volta Redonda (área bloqueada p/ Rio)',
                'endereco' => 'Av. Paulo Erlei Alves Abrantes, 90',
                'cep' => '27253065',
                'lat' => -22.5202,
                'lng' => -44.0996,
                'status_receita' => StatusReceita::Ativa,
                'is_cliente' => false,
                'origem' => 'DEMO',
            ],
        );

        unset($repSul);
    }
}
