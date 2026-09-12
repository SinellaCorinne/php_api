<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

// Une Policy est une classe dédiée aux règles d'AUTORISATION
// Elle répond à la question : "Est-ce que CET utilisateur a le droit de faire CETTE action sur CE projet ?"
//
// Laravel trouve automatiquement cette Policy grâce à la convention de nommage :
// Model "Project" → Policy "ProjectPolicy" (dans app/Policies/)
class ProjectPolicy
{
    // Appelée quand on fait $this->authorize('view', $project) dans le controller
    // $user  = l'utilisateur actuellement connecté (injecté automatiquement par Laravel)
    // $project = l'objet Project concerné
    // Retourne true (autorisé) ou false (refusé → 403)
    public function view(User $user, Project $project): bool
    {
        // === compare l'id ET le type (pas de conversion implicite)
        // Un utilisateur ne peut voir QUE ses propres projets
        return $user->id === $project->user_id;
    }

    // Appelée pour les actions create/update et aussi pour les actions sur les tâches
    // (car on réutilise cette règle dans TaskController)
    public function update(User $user, Project $project): bool
    {
        // Seul le propriétaire peut modifier le projet ou ses tâches
        return $user->id === $project->user_id;
    }

    // Appelée quand on fait $this->authorize('delete', $project)
    public function delete(User $user, Project $project): bool
    {
        // Seul le propriétaire peut supprimer le projet
        return $user->id === $project->user_id;
    }
}
