<?php

namespace Database\Factories;

use App\Models\Agence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Departement>
 */
class DepartementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'DEP-'.fake()->unique()->numerify('#####'),
            'nom' => 'Département '.fake()->unique()->word(),
            'agence_id' => Agence::factory(),
        ];
    }
}
