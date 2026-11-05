<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Demande>
 */
class DemandeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => now()->toDateString(),
            'objet' => fake()->sentence(4),
            'description' => null,
            'statut' => 'En préparation',
            'archiver' => 0,
            'user_id' => User::factory(),
        ];
    }
}
