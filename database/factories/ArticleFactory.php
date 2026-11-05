<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\TypeArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'ART-'.fake()->unique()->numerify('#####'),
            'libelle' => fake()->words(3, true),
            'prix_unitaire' => fake()->randomFloat(2, 100, 10000),
            'stock_initial' => 0,
            'stock_disponible' => 0,
            'stock_securite' => 5,
            'archiver' => 0,
            'categorie_id' => Categorie::factory(),
            'type_article_id' => TypeArticle::factory(),
        ];
    }
}
