<?php

namespace Database\Factories;

use App\Enums\TipoUnidade;
use App\Models\Unidade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unidade>
 */
class UnidadeFactory extends Factory
{
    protected $model = Unidade::class;

    public function definition(): array
    {
        $cepInicio = fake()->numerify('########');

        return [
            'nome' => fake()->company(),
            'tipo' => fake()->randomElement(TipoUnidade::cases()),
            'cep_inicio' => $cepInicio,
            'cep_fim' => $cepInicio,
            'poligono_geojson' => null,
        ];
    }
}
