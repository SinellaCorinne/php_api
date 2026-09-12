<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // GET /api/projects
    // Retourne les projets de l'utilisateur connecté, paginés
    //
    // Paramètres optionnels :
    //   ?per_page=10   → nombre de résultats par page (défaut : 10)
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        // ->paginate() découpe les résultats en pages
        // La réponse inclut "data", "total", "current_page", "last_page", etc.
        $projects = $request->user()
            ->projects()
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json($projects);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/projects
    // Crée un nouveau projet pour l'utilisateur connecté
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        // Validation des données envoyées par le client
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            // 'nullable' = le champ peut être absent ou null, ce n'est pas une erreur
            'description' => 'nullable|string',
        ]);

        // $request->user()->projects() cible la relation projets de l'utilisateur connecté
        // ->create($validated) insère en base ET ajoute automatiquement user_id = {id de l'user}
        // C'est la magie des relations Eloquent : pas besoin de spécifier user_id manuellement
        $project = $request->user()->projects()->create($validated);

        // Code 201 : ressource créée avec succès
        return response()->json($project, 201);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/projects/{project}
    // Retourne un projet avec toutes ses tâches
    // ─────────────────────────────────────────────────────────────
    // "Project $project" → c'est le "Route Model Binding" de Laravel :
    // Laravel voit {project} dans l'URL, récupère automatiquement le Project
    // correspondant dans la base, et l'injecte directement.
    // Si l'id n'existe pas → 404 automatique, pas besoin de le gérer manuellement
    public function show(Request $request, Project $project): JsonResponse
    {
        // $this->authorize('view', $project) appelle ProjectPolicy::view()
        // Si la méthode retourne false → Laravel renvoie automatiquement une erreur 403 (Forbidden)
        // Si elle retourne true → on continue
        $this->authorize('view', $project);

        // ->load('tasks') charge les tâches liées à ce projet EN UNE SEULE requête supplémentaire
        // Sans load(), $project->tasks ne serait pas inclus dans la réponse JSON
        // C'est ce qu'on appelle "l'eager loading"
        return response()->json($project->load('tasks'));
    }

    // ─────────────────────────────────────────────────────────────
    // PUT /api/projects/{project}
    // Met à jour un projet existant
    // ─────────────────────────────────────────────────────────────
    public function update(Request $request, Project $project): JsonResponse
    {
        // Vérification que l'user connecté est bien le propriétaire
        $this->authorize('update', $project);

        $validated = $request->validate([
            // 'sometimes' : cette règle ne s'applique QUE si le champ est présent dans la requête
            // Utile pour les mises à jour partielles (PATCH) : on peut envoyer seulement "description"
            // sans envoyer "title", et ça ne générera pas d'erreur "title is required"
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // update($data) fait un UPDATE SQL avec uniquement les colonnes fournies
        // Eloquent met aussi à jour "updated_at" automatiquement
        $project->update($validated);

        return response()->json($project);
    }

    // ─────────────────────────────────────────────────────────────
    // DELETE /api/projects/{project}
    // Supprime un projet (et ses tâches par cascade)
    // ─────────────────────────────────────────────────────────────
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        // delete() fait un DELETE SQL sur cette ligne
        // Les tâches liées sont supprimées automatiquement grâce au cascadeOnDelete()
        // défini dans la migration de tasks
        $project->delete();

        return response()->json(['message' => 'Projet supprimé.']);
    }
}
