<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Permet d'utiliser Task::factory() dans les tests
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    // HasFactory : donne accès à Task::factory() pour générer des données de test
    use HasFactory;
    // Colonnes autorisées à être remplies en masse
    // "status" et "due_date" sont inclus car on veut pouvoir les définir à la création
    protected $fillable = [
        'title',
        'description',
        'status',
        'due_date',
    ];

    // $casts : Laravel transforme automatiquement ces colonnes quand il les lit
    protected $casts = [
        // 'due_date' est stocké en base comme "2026-12-31" (texte)
        // Avec ce cast, Laravel le convertit en objet Carbon (classe de date PHP)
        // On peut alors faire : $task->due_date->format('d/m/Y') ou $task->due_date->addDays(7)
        'due_date' => 'date',
    ];

    // Relation inverse : une tâche APPARTIENT À un projet
    // Concrètement : Project::find($this->project_id)
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
