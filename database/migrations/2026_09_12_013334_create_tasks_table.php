<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Appelée lors de "php artisan migrate" — crée la table "tasks"
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {

            // Clé primaire auto-incrémentée
            $table->id();

            // Clé étrangère vers la table "projects"
            // Si un projet est supprimé, toutes ses tâches sont supprimées aussi (cascade)
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // Titre de la tâche — texte court, obligatoire
            $table->string('title');

            // Description — texte long, optionnel
            $table->text('description')->nullable();

            // Statut de la tâche : ENUM signifie que la valeur doit être
            // exactement l'une des options listées : 'todo', 'in_progress' ou 'done'
            // ->default('todo') : si on ne précise pas le statut, il sera 'todo' par défaut
            $table->enum('status', ['todo', 'in_progress', 'done'])->default('todo');

            // Date d'échéance — type DATE (sans l'heure), optionnelle
            $table->date('due_date')->nullable();

            // created_at et updated_at gérés automatiquement par Laravel
            $table->timestamps();
        });
    }

    // Appelée lors de "php artisan migrate:rollback" — supprime la table
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
