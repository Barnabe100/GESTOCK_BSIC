<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Demande;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArticleDemande>
 */
class ArticleDemandeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'demande_id' => Demande::factory(),
            'article_id' => Article::factory(),
            'quantite' => fake()->numberBetween(1, 10),
            'quantite_accordee' => null,
            'archiver' => 0,
        ];
    }
}
