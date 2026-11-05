<?php

namespace Database\Factories;

use App\Models\Departement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'SRV-'.fake()->unique()->numerify('#####'),
            'nom' => 'Service '.fake()->unique()->word(),
            'localisation' => null,
            'departement_id' => Departement::factory(),
        ];
    }
}
