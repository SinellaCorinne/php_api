<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateurConnecte(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    // ─────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_lister_ses_projets(): void
    {
        $user = $this->utilisateurConnecte();
        Project::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200);
        // La liste est maintenant paginée : les résultats sont dans la clé "data"
        // assertJsonCount(3, 'data') vérifie qu'il y a 3 éléments dans data
        $response->assertJsonCount(3, 'data');
        // On vérifie aussi que les métadonnées de pagination sont présentes
        $response->assertJsonStructure(['data', 'total', 'current_page', 'last_page']);
    }

    #[Test]
    public function un_utilisateur_ne_voit_pas_les_projets_des_autres(): void
    {
        $this->utilisateurConnecte();

        $user2 = User::factory()->create();
        Project::factory()->count(2)->create(['user_id' => $user2->id]);

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200);
        // 0 projets dans "data"
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function la_pagination_fonctionne(): void
    {
        $user = $this->utilisateurConnecte();
        // On crée 25 projets
        Project::factory()->count(25)->create(['user_id' => $user->id]);

        // Page 1 avec 10 par page
        $response = $this->getJson('/api/projects?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');          // 10 résultats sur cette page
        $response->assertJsonPath('total', 25);           // 25 au total
        $response->assertJsonPath('last_page', 3);        // 3 pages (10+10+5)
    }

    // ─────────────────────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_creer_un_projet(): void
    {
        $user = $this->utilisateurConnecte();

        $response = $this->postJson('/api/projects', [
            'title'       => 'Mon projet',
            'description' => 'Description du projet',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['title' => 'Mon projet']);
        $this->assertDatabaseHas('projects', [
            'title'   => 'Mon projet',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function la_creation_echoue_sans_titre(): void
    {
        $this->utilisateurConnecte();

        $response = $this->postJson('/api/projects', [
            'description' => 'Sans titre',
        ]);

        // Le message d'erreur est maintenant personnalisé
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Les données fournies sont invalides.']);
        $response->assertJsonValidationErrors(['title']);
    }

    // ─────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_voir_son_projet(): void
    {
        $user    = $this->utilisateurConnecte();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => $project->title]);
        $response->assertJsonStructure(['id', 'title', 'description', 'tasks']);
    }

    #[Test]
    public function un_projet_inexistant_retourne_404(): void
    {
        $this->utilisateurConnecte();

        $response = $this->getJson('/api/projects/99999');

        // On vérifie le message d'erreur personnalisé
        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => 'Project introuvable.']);
    }

    #[Test]
    public function un_utilisateur_ne_peut_pas_voir_le_projet_de_quelquun_dautre(): void
    {
        $this->utilisateurConnecte();

        $autreUser = User::factory()->create();
        $projet    = Project::factory()->create(['user_id' => $autreUser->id]);

        $response = $this->getJson("/api/projects/{$projet->id}");

        $response->assertStatus(403);
        // On vérifie le message d'erreur personnalisé du 403
        $response->assertJsonFragment(['message' => 'Accès refusé. Vous n\'êtes pas autorisé à effectuer cette action.']);
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_modifier_son_projet(): void
    {
        $user    = $this->utilisateurConnecte();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Titre modifié',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Titre modifié']);
        $this->assertDatabaseHas('projects', [
            'id'    => $project->id,
            'title' => 'Titre modifié',
        ]);
    }

    #[Test]
    public function un_utilisateur_ne_peut_pas_modifier_le_projet_de_quelquun_dautre(): void
    {
        $this->utilisateurConnecte();

        $autreUser = User::factory()->create();
        $projet    = Project::factory()->create(['user_id' => $autreUser->id]);

        $response = $this->putJson("/api/projects/{$projet->id}", [
            'title' => 'Tentative de modification',
        ]);

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_supprimer_son_projet(): void
    {
        $user    = $this->utilisateurConnecte();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    #[Test]
    public function un_utilisateur_ne_peut_pas_supprimer_le_projet_de_quelquun_dautre(): void
    {
        $this->utilisateurConnecte();

        $autreUser = User::factory()->create();
        $projet    = Project::factory()->create(['user_id' => $autreUser->id]);

        $response = $this->deleteJson("/api/projects/{$projet->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', ['id' => $projet->id]);
    }
}
