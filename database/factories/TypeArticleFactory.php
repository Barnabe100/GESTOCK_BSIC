<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TypeArticle>
 */
class TypeArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'TYP-'.fake()->unique()->numerify('#####'),
            'libelle' => fake()->words(2, true),
            'description' => null,
            'archiver' => 0,
        ];
    }
}
