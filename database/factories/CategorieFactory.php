<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categorie>
 */
class CategorieFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'CAT-'.fake()->unique()->numerify('#####'),
            'libelle' => fake()->words(2, true),
            'description' => null,
            'archiver' => 0,
        ];
    }
}
