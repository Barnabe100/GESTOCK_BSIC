<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agence>
 */
class AgenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'AG-'.fake()->unique()->numerify('#####'),
            'nom' => 'Agence '.fake()->unique()->city(),
            'lieu' => fake()->city(),
        ];
    }
}
