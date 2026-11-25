<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fournisseur>
 */
class FournisseurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'FRN-'.fake()->unique()->numerify('#####'),
            'nom' => fake()->company(),
            'telephone' => null,
            'email' => null,
            'adresse' => null,
            'archiver' => 0,
        ];
    }
}
