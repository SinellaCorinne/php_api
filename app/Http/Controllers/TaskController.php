<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // GET /api/projects/{project}/tasks
    // Retourne les tâches d'un projet, avec filtres et pagination
    //
    // Paramètres de query string optionnels :
    //   ?status=todo               → filtre par statut
    //   ?due_before=2026-12-31     → tâches dont l'échéance est avant cette date
    //   ?due_after=2026-10-01      → tâches dont l'échéance est après cette date
    //   ?per_page=10               → nombre de résultats par page (défaut : 15)
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        // On valide les paramètres de filtrage envoyés dans l'URL
        // Ces paramètres sont optionnels — s'ils sont absents, ils ne filtrent pas
        $request->validate([
            // 'nullable' : le paramètre peut être absent, null, ou une valeur valide
            'status'     => 'nullable|in:todo,in_progress,done',
            'due_before' => 'nullable|date',
            'due_after'  => 'nullable|date',
            // 'integer|min:1|max:100' : entier entre 1 et 100
            'per_page'   => 'nullable|integer|min:1|max:100',
        ]);

        // On part de la relation tasks du projet (au lieu de Task::all())
        // Cela garantit qu'on ne voit que les tâches DE CE projet
        $query = $project->tasks()->latest();

        // Filtre par statut si le paramètre est présent dans l'URL
        // $request->filled('status') = true si le paramètre existe ET n'est pas vide
        if ($request->filled('status')) {
            // ->where('status', 'todo') ajoute un WHERE status = 'todo' à la requête SQL
            $query->where('status', $request->status);
        }

        // Filtre : tâches dont la date d'échéance est AVANT la date donnée
        if ($request->filled('due_before')) {
            // whereDate() compare uniquement la partie date (sans l'heure)
            // '<=' signifie : "inférieur ou égal à"
            $query->whereDate('due_date', '<=', $request->due_before);
        }

        // Filtre : tâches dont la date d'échéance est APRÈS la date donnée
        if ($request->filled('due_after')) {
            $query->whereDate('due_date', '>=', $request->due_after);
        }

        // ->paginate() remplace ->get() et découpe les résultats en pages
        // $request->input('per_page', 15) : prend la valeur du paramètre ?per_page=
        // ou 15 par défaut si absent
        //
        // La réponse JSON contiendra :
        // {
        //   "data": [...],          ← les tâches de la page courante
        //   "current_page": 1,
        //   "last_page": 3,
        //   "per_page": 15,
        //   "total": 42,            ← nombre total de tâches
        //   "next_page_url": "...", ← URL de la page suivante
        //   "prev_page_url": null
        // }
        $tasks = $query->paginate($request->input('per_page', 15));

        return response()->json($tasks);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/projects/{project}/tasks
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:todo,in_progress,done',
            'due_date'    => 'nullable|date',
        ]);

        $task = $project->tasks()->create($validated);

        // ->fresh() recharge depuis la base pour inclure les valeurs par défaut (ex: status='todo')
        return response()->json($task->fresh(), 201);
    }

    // ─────────────────────────────────────────────────────────────
    // PUT /api/projects/{project}/tasks/{task}
    // Met à jour une tâche — validation stricte : seuls les bons champs sont acceptés
    // ─────────────────────────────────────────────────────────────
    public function update(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', $project);

        // VALIDATION STRICTE :
        // On ne liste QUE les champs qu'on autorise à modifier.
        // Tout autre champ envoyé par le client (project_id, id, created_at...)
        // sera ignoré par validate() → impossible de corrompre les données.
        //
        // 'sometimes' : valide la règle seulement si le champ est PRÉSENT dans la requête.
        // Cela permet les mises à jour partielles : envoyer {"status":"done"} sans
        // envoyer title ne génère pas d'erreur "title is required".
        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status'      => 'sometimes|required|in:todo,in_progress,done',
            'due_date'    => 'sometimes|nullable|date',
        ]);

        // update() ne touche que les colonnes présentes dans $validated
        // project_id, id, created_at ne peuvent PAS être modifiés — ils ne sont pas dans $fillable
        $task->update($validated);

        // ->fresh() pour retourner l'objet avec toutes ses valeurs à jour depuis la base
        return response()->json($task->fresh());
    }

    // ─────────────────────────────────────────────────────────────
    // DELETE /api/projects/{project}/tasks/{task}
    // ─────────────────────────────────────────────────────────────
    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', $project);

        $task->delete();

        return response()->json(['message' => 'Tâche supprimée.']);
    }
}
