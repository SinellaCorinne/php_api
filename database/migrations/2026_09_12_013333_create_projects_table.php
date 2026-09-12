<?php

// On importe les classes nécessaires pour écrire une migration
use Illuminate\Database\Migrations\Migration; // Classe de base pour toute migration
use Illuminate\Database\Schema\Blueprint;     // Permet de décrire les colonnes d'une table
use Illuminate\Support\Facades\Schema;        // Facade pour manipuler la base de données

// "return new class" : on retourne une classe anonyme (sans nom), c'est la façon moderne
// de déclarer une migration dans Laravel. Elle étend Migration.
return new class extends Migration
{
    // up() est appelée quand on fait "php artisan migrate"
    // C'est ici qu'on CRÉE ou MODIFIE la structure de la base
    public function up(): void
    {
        // Schema::create('projects', ...) crée une table nommée "projects"
        // $table est un objet Blueprint qui représente la table à construire
        Schema::create('projects', function (Blueprint $table) {

            // Crée une colonne "id" : entier auto-incrémenté, clé primaire
            // Chaque ligne aura un id unique : 1, 2, 3...
            $table->id();

            // Crée une colonne "user_id" (entier)
            // ->constrained() signifie : c'est une clé étrangère vers la table "users"
            // ->cascadeOnDelete() : si l'utilisateur est supprimé, tous ses projets le sont aussi
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Crée une colonne "title" de type VARCHAR(255) — texte court obligatoire
            $table->string('title');

            // Crée une colonne "description" de type TEXT — texte long
            // ->nullable() signifie que le champ peut être vide (NULL en base)
            $table->text('description')->nullable();

            // Crée deux colonnes automatiques :
            // "created_at" : date/heure de création de la ligne
            // "updated_at" : date/heure de la dernière modification
            // Laravel les met à jour automatiquement
            $table->timestamps();
        });
    }

    // down() est appelée quand on fait "php artisan migrate:rollback"
    // C'est l'INVERSE de up() — ici on supprime la table
    public function down(): void
    {
        // Supprime la table "projects" si elle existe
        Schema::dropIfExists('projects');
    }
};
