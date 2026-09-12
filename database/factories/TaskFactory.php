<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

// TaskFactory génère des données fictives pour le Model "Task"
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Titre court et aléatoire
            'title'       => fake()->sentence(4),

            // Description optionnelle
            'description' => fake()->optional()->paragraph(),

            // fake()->randomElement([...]) choisit une valeur au hasard dans la liste
            // Les 3 valeurs correspondent aux valeurs ENUM définies dans la migration
            'status'      => fake()->randomElement(['todo', 'in_progress', 'done']),

            // fake()->optional(0.7) : 70% de chance d'avoir une date, 30% d'avoir NULL
            // fake()->dateTimeBetween('now', '+6 months') génère une date dans les 6 prochains mois
            'due_date'    => fake()->optional(0.7)->dateTimeBetween('now', '+6 months'),

            // Project::factory() crée un projet associé si on ne précise pas project_id
            'project_id'  => Project::factory(),
        ];
    }
}
