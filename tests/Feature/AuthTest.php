<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

// RefreshDatabase : avant chaque test, Laravel recrée toutes les tables depuis zéro
// Cela garantit que chaque test part d'une base propre, sans données résiduelles
class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────
    // REGISTER
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_creer_un_compte(): void
    {
        // On envoie une requête POST /api/register avec des données valides
        $response = $this->postJson('/api/register', [
            'name'                  => 'Sinella',
            'email'                 => 'sinella@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // assertStatus(201) : vérifie que le code HTTP retourné est 201 (Created)
        $response->assertStatus(201);

        // assertJsonStructure : vérifie que la réponse JSON contient bien ces clés
        $response->assertJsonStructure([
            'user'  => ['id', 'name', 'email'],
            'token',
        ]);

        // assertDatabaseHas : vérifie qu'un enregistrement existe en base avec ces valeurs
        $this->assertDatabaseHas('users', [
            'email' => 'sinella@test.com',
        ]);
    }

    #[Test]
    public function le_register_echoue_si_email_deja_pris(): void
    {
        // On crée un utilisateur existant avec User::factory()
        // factory() génère des données fictives définies dans database/factories/UserFactory.php
        User::factory()->create(['email' => 'sinella@test.com']);

        $response = $this->postJson('/api/register', [
            'name'                  => 'Autre',
            'email'                 => 'sinella@test.com', // email déjà utilisé
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 422 Unprocessable Entity : la validation a échoué
        $response->assertStatus(422);

        // assertJsonValidationErrors : vérifie qu'il y a une erreur sur le champ 'email'
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function le_register_echoue_si_mot_de_passe_trop_court(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Sinella',
            'email'                 => 'sinella@test.com',
            'password'              => 'court',  // moins de 8 caractères
            'password_confirmation' => 'court',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function le_register_echoue_si_confirmation_incorrecte(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Sinella',
            'email'                 => 'sinella@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'motdepasse_different',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    // ─────────────────────────────────────────────────────────────
    // LOGIN
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_peut_se_connecter(): void
    {
        // On crée un utilisateur en base avec un mot de passe connu
        User::factory()->create([
            'email'    => 'sinella@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'sinella@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user'  => ['id', 'name', 'email'],
            'token',
        ]);
    }

    #[Test]
    public function le_login_echoue_avec_mauvais_mot_de_passe(): void
    {
        User::factory()->create([
            'email'    => 'sinella@test.com',
            'password' => bcrypt('le_vrai_mot_de_passe'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'sinella@test.com',
            'password' => 'mauvais_mot_de_passe',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function le_login_echoue_avec_email_inexistant(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'nexiste@pas.com',
            'password' => 'nimporte',
        ]);

        $response->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function un_utilisateur_connecte_peut_se_deconnecter(): void
    {
        $user = User::factory()->create();

        // actingAs($user, 'sanctum') simule un utilisateur connecté avec un token Sanctum
        // On n'a pas besoin de passer un vrai token dans les headers — Laravel le gère pour nous
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Déconnecté avec succès.']);
    }

    #[Test]
    public function un_utilisateur_non_connecte_ne_peut_pas_acceder_aux_routes_protegees(): void
    {
        // On essaie d'accéder à /api/projects SANS token
        $response = $this->getJson('/api/projects');

        // 401 Unauthorized : pas de token fourni
        $response->assertStatus(401);
    }
}
