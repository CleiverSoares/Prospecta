<?php

namespace Database\Factories;

use App\Enums\StatusReceita;
use App\Models\Prospecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospecto>
 */
class ProspectoFactory extends Factory
{
    protected $model = Prospecto::class;

    public function definition(): array
    {
        return [
            'cnpj' => fake()->unique()->numerify('##############'),
            'razao_social' => fake()->company(),
            'cep' => fake()->numerify('########'),
            'lat' => fake()->latitude(-33, -5),
            'lng' => fake()->longitude(-73, -34),
            'status_receita' => fake()->randomElement(StatusReceita::cases()),
            'is_cliente' => false,
        ];
    }

    public function cliente(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cliente' => true,
        ]);
    }
}
