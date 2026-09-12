<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Permet d'utiliser Project::factory() dans les tests
use Illuminate\Database\Eloquent\Model;              // Classe de base pour tous les modèles Eloquent
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Type de retour pour "appartient à"
use Illuminate\Database\Eloquent\Relations\HasMany;   // Type de retour pour "a plusieurs"

// Project étend Model : Eloquent sait qu'il doit le lier à la table "projects"
// (Laravel pluralise automatiquement le nom de la classe : Project → projects)
class Project extends Model
{
    // HasFactory : donne accès à Project::factory() pour générer des données de test
    use HasFactory;
    // $fillable liste les colonnes qu'on peut remplir avec create() ou update()
    // C'est une sécurité : si quelqu'un envoie un champ "user_id" malveillant,
    // il sera ignoré car il n'est pas dans cette liste
    protected $fillable = [
        'title',
        'description',
    ];

    // Relation "BelongsTo" : un projet APPARTIENT À un utilisateur
    // Concrètement : Laravel va chercher User::find($this->user_id)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relation "HasMany" : un projet A PLUSIEURS tâches
    // Concrètement : SELECT * FROM tasks WHERE project_id = {id de ce projet}
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
