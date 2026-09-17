<?php

namespace Database\Factories;

use App\Enums\StatusVisita;
use App\Models\Prospecto;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visita>
 */
class VisitaFactory extends Factory
{
    protected $model = Visita::class;

    public function definition(): array
    {
        return [
            'prospecto_id' => Prospecto::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(StatusVisita::cases()),
            'checkin_lat' => fake()->latitude(-33, -5),
            'checkin_lng' => fake()->longitude(-73, -34),
            'caminho_foto' => null,
            'caminho_audio' => null,
        ];
    }
}
