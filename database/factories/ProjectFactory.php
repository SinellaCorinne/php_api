<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

// Une Factory génère des données fictives réalistes pour les tests
// Elle est liée au Model "Project" par convention de nommage : ProjectFactory → Project
class ProjectFactory extends Factory
{
    // definition() retourne un tableau de valeurs par défaut pour chaque projet créé
    public function definition(): array
    {
        return [
            // fake()->sentence(3) génère une phrase de 3 mots aléatoires
            // Ex: "Refonte site vitrine", "Application mobile beta"
            'title'       => fake()->sentence(3),

            // fake()->paragraph() génère un paragraphe de texte aléatoire
            'description' => fake()->paragraph(),

            // User::factory() crée automatiquement un User associé si on ne précise pas user_id
            // Dans les tests, on le remplace souvent par ['user_id' => $user->id]
            'user_id'     => User::factory(),
        ];
    }
}
