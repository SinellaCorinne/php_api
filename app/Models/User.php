<?php

// Le namespace indique l'emplacement de ce fichier dans l'arborescence
// App\Models = dossier app/Models/
namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable; // Attribut PHP pour définir les champs assignables
use Illuminate\Database\Eloquent\Attributes\Hidden;   // Attribut PHP pour masquer des champs dans les réponses
use Illuminate\Database\Eloquent\Factories\HasFactory; // Permet de créer des faux utilisateurs pour les tests
use Illuminate\Database\Eloquent\Relations\HasMany;    // Type de retour pour les relations "a plusieurs"
use Illuminate\Foundation\Auth\User as Authenticatable; // Classe de base Laravel pour les utilisateurs authentifiables
use Illuminate\Notifications\Notifiable;               // Permet d'envoyer des notifications (email, SMS...)
use Laravel\Sanctum\HasApiTokens;                      // Donne à l'utilisateur la capacité de générer des tokens API

// #[Fillable(...)] est un "attribut PHP" (syntaxe moderne).
// Il liste les colonnes qu'on peut remplir en masse avec User::create([...])
// Sans cette liste, Laravel refuserait de créer l'utilisateur par sécurité (protection contre la mass assignment)
#[Fillable(['name', 'email', 'password'])]

// #[Hidden(...)] liste les colonnes qui seront CACHÉES dans les réponses JSON
// Le mot de passe et le token "remember me" ne doivent jamais être renvoyés au client
#[Hidden(['password', 'remember_token'])]

// User étend Authenticatable : Laravel sait que c'est un utilisateur qui peut se connecter
class User extends Authenticatable
{
    // "use" à l'intérieur de la classe = on importe des "traits" (des blocs de fonctionnalités réutilisables)
    // HasFactory    : permet d'utiliser UserFactory pour créer des users en tests
    // Notifiable    : permet d'envoyer des notifications à cet utilisateur
    // HasApiTokens  : ajoute les méthodes createToken(), tokens(), currentAccessToken()
    //                 c'est ce qui permet à Sanctum de fonctionner
    use HasFactory, Notifiable, HasApiTokens;

    // Relation "HasMany" : un utilisateur A PLUSIEURS projets
    // Laravel comprend automatiquement qu'il faut chercher dans la table "projects"
    // la colonne "user_id" qui correspond à cet utilisateur
    public function projects(): HasMany
    {
        // $this->hasMany(Project::class) retourne une requête :
        // SELECT * FROM projects WHERE user_id = {id de cet utilisateur}
        return $this->hasMany(Project::class);
    }

    // casts() dit à Laravel comment transformer certaines colonnes
    // quand elles sont lues depuis la base de données
    protected function casts(): array
    {
        return [
            // 'email_verified_at' sera automatiquement converti en objet Carbon (date PHP)
            // au lieu d'une simple chaîne de caractères
            'email_verified_at' => 'datetime',

            // 'password' sera automatiquement hashé (bcrypt) quand on l'assigne
            // Ex: $user->password = 'monmotdepasse' → stocké hashé en base
            'password' => 'hashed',
        ];
    }
}
