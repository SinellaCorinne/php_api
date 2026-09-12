<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private function userAvecProjet(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $project = Project::factory()->create(['user_id' => $user->id]);
        return [$user, $project];
    }

    // ─────────────────────────────────────────────────────────────
    // INDEX + PAGINATION + FILTRES
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_lister_les_taches_de_son_projet(): void
    {
        [, $project] = $this->userAvecProjet();
        Task::factory()->count(4)->create(['project_id' => $project->id]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(200);
        // Les tâches sont maintenant dans "data" (pagination)
        $response->assertJsonCount(4, 'data');
        $response->assertJsonStructure(['data', 'total', 'current_page']);
    }

    #[Test]
    public function le_filtre_par_statut_fonctionne(): void
    {
        [, $project] = $this->userAvecProjet();

        // On crée des tâches avec des statuts différents
        Task::factory()->count(3)->create(['project_id' => $project->id, 'status' => 'todo']);
        Task::factory()->count(2)->create(['project_id' => $project->id, 'status' => 'done']);

        // On filtre par statut "todo"
        $response = $this->getJson("/api/projects/{$project->id}/tasks?status=todo");

        $response->assertStatus(200);
        // On ne doit voir que les 3 tâches "todo"
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function le_filtre_par_date_echeance_fonctionne(): void
    {
        [, $project] = $this->userAvecProjet();

        Task::factory()->create(['project_id' => $project->id, 'due_date' => '2026-10-01']);
        Task::factory()->create(['project_id' => $project->id, 'due_date' => '2026-11-15']);
        Task::factory()->create(['project_id' => $project->id, 'due_date' => '2026-12-31']);

        // On veut les tâches dont l'échéance est avant le 01/11/2026
        $response = $this->getJson("/api/projects/{$project->id}/tasks?due_before=2026-11-01");

        $response->assertStatus(200);
        // Seulement la tâche du 01/10 correspond
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function un_statut_invalide_retourne_une_erreur(): void
    {
        [, $project] = $this->userAvecProjet();

        $response = $this->getJson("/api/projects/{$project->id}/tasks?status=invalide");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function un_utilisateur_ne_peut_pas_voir_les_taches_dun_autre_projet(): void
    {
        $this->userAvecProjet();

        $autreUser   = User::factory()->create();
        $autreProjet = Project::factory()->create(['user_id' => $autreUser->id]);
        Task::factory()->count(2)->create(['project_id' => $autreProjet->id]);

        $response = $this->getJson("/api/projects/{$autreProjet->id}/tasks");

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_creer_une_tache(): void
    {
        [, $project] = $this->userAvecProjet();

        $response = $this->postJson("/api/projects/{$project->id}/tasks", [
            'title'    => 'Ma première tâche',
            'status'   => 'todo',
            'due_date' => '2026-12-31',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['title' => 'Ma première tâche']);
        $this->assertDatabaseHas('tasks', [
            'title'      => 'Ma première tâche',
            'project_id' => $project->id,
            'status'     => 'todo',
        ]);
    }

    #[Test]
    public function le_statut_par_defaut_est_todo(): void
    {
        [, $project] = $this->userAvecProjet();

        $response = $this->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'Tâche sans statut',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['status' => 'todo']);
    }

    #[Test]
    public function la_creation_echoue_avec_un_statut_invalide(): void
    {
        [, $project] = $this->userAvecProjet();

        $response = $this->postJson("/api/projects/{$project->id}/tasks", [
            'title'  => 'Tâche',
            'status' => 'invalide',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function la_creation_echoue_avec_une_date_invalide(): void
    {
        [, $project] = $this->userAvecProjet();

        $response = $this->postJson("/api/projects/{$project->id}/tasks", [
            'title'    => 'Tâche',
            'due_date' => 'pas-une-date',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['due_date']);
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE — validation stricte
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_modifier_une_tache(): void
    {
        [, $project] = $this->userAvecProjet();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status'     => 'todo',
        ]);

        $response = $this->putJson("/api/projects/{$project->id}/tasks/{$task->id}", [
            'status' => 'done',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'done']);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);
    }

    #[Test]
    public function on_ne_peut_pas_changer_le_project_id_dune_tache(): void
    {
        [, $project] = $this->userAvecProjet();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $autreProjet = Project::factory()->create(['user_id' => $task->project->user_id]);
        $ancienProjectId = $task->project_id;

        // On tente d'envoyer un project_id différent
        $this->putJson("/api/projects/{$project->id}/tasks/{$task->id}", [
            'title'      => 'Nouveau titre',
            'project_id' => $autreProjet->id, // ce champ doit être ignoré
        ]);

        // Le project_id ne doit pas avoir changé
        $this->assertDatabaseHas('tasks', [
            'id'         => $task->id,
            'project_id' => $ancienProjectId,
        ]);
    }

    #[Test]
    public function un_utilisateur_ne_peut_pas_modifier_une_tache_dun_autre_projet(): void
    {
        $this->userAvecProjet();

        $autreUser   = User::factory()->create();
        $autreProjet = Project::factory()->create(['user_id' => $autreUser->id]);
        $tache       = Task::factory()->create(['project_id' => $autreProjet->id]);

        $response = $this->putJson("/api/projects/{$autreProjet->id}/tasks/{$tache->id}", [
            'status' => 'done',
        ]);

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_supprimer_une_tache(): void
    {
        [, $project] = $this->userAvecProjet();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    #[Test]
    public function la_suppression_du_projet_supprime_ses_taches(): void
    {
        [, $project] = $this->userAvecProjet();
        $tasks = Task::factory()->count(3)->create(['project_id' => $project->id]);

        $project->delete();

        foreach ($tasks as $task) {
            $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        }
    }
}
