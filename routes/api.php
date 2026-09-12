<?php

// On importe les controllers qu'on va utiliser dans ce fichier
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route; // La facade Route permet de définir les routes

// ─────────────────────────────────────────────────────────────────
// ROUTES PUBLIQUES — accessibles sans être connecté
// ─────────────────────────────────────────────────────────────────

// Route::post(url, [Controller::class, 'méthode'])
// Quand une requête POST arrive sur /api/register, Laravel appelle AuthController::register()
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ─────────────────────────────────────────────────────────────────
// ROUTES PROTÉGÉES — le middleware 'auth:sanctum' est appliqué à toutes
// les routes dans ce groupe
//
// 'auth:sanctum' signifie : avant d'exécuter le controller,
// vérifie que la requête contient un header "Authorization: Bearer {token}"
// et que ce token existe en base et n'est pas expiré.
// Si le token est absent ou invalide → 401 Unauthorized automatique
// ─────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Route::apiResource crée automatiquement 5 routes en une seule ligne :
    // GET    /api/projects            → ProjectController::index()   (liste)
    // POST   /api/projects            → ProjectController::store()   (créer)
    // GET    /api/projects/{project}  → ProjectController::show()    (détail)
    // PUT    /api/projects/{project}  → ProjectController::update()  (modifier)
    // DELETE /api/projects/{project}  → ProjectController::destroy() (supprimer)
    Route::apiResource('projects', ProjectController::class);

    // Routes imbriquées : les tâches vivent DANS un projet
    // 'projects.tasks' crée des URLs du type /api/projects/{project}/tasks
    //
    // ->only([...]) : on ne veut PAS la route show() pour les tâches
    // (pas besoin de GET /projects/{project}/tasks/{task} — on voit les tâches via le projet)
    // Les 4 routes créées :
    // GET    /api/projects/{project}/tasks             → TaskController::index()
    // POST   /api/projects/{project}/tasks             → TaskController::store()
    // PUT    /api/projects/{project}/tasks/{task}      → TaskController::update()
    // DELETE /api/projects/{project}/tasks/{task}      → TaskController::destroy()
    Route::apiResource('projects.tasks', TaskController::class)->only([
        'index', 'store', 'update', 'destroy',
    ]);
});
