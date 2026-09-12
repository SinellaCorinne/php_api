<?php

namespace App\Http\Controllers;

use App\Models\User;                              // Le modèle User pour interagir avec la table users
use Illuminate\Http\JsonResponse;                 // Type de retour : une réponse JSON
use Illuminate\Http\Request;                      // Représente la requête HTTP entrante (body, headers...)
use Illuminate\Support\Facades\Hash;              // Pour hasher et vérifier les mots de passe
use Illuminate\Validation\ValidationException;    // Exception levée quand la validation échoue

class AuthController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // POST /api/register
    // Crée un nouveau compte utilisateur et retourne un token
    // ─────────────────────────────────────────────────────────────
    public function register(Request $request): JsonResponse
    {
        // validate() vérifie les données envoyées par le client
        // Si une règle est violée, Laravel renvoie automatiquement une erreur 422
        // et n'exécute pas le reste de la méthode
        $validated = $request->validate([
            // 'name' est obligatoire (required), doit être un texte (string),
            // et ne peut pas dépasser 255 caractères (max:255)
            'name'     => 'required|string|max:255',

            // 'email' est obligatoire, doit être un email valide,
            // et doit être unique dans la colonne "email" de la table "users"
            'email'    => 'required|email|unique:users,email',

            // 'password' est obligatoire, texte, au moins 8 caractères,
            // et 'confirmed' vérifie qu'un champ 'password_confirmation' existe et est identique
            'password' => 'required|string|min:8|confirmed',
        ]);

        // User::create([...]) insère une ligne dans la table "users"
        // On hash le mot de passe avec Hash::make() AVANT de le stocker en base
        // Un hash est irréversible : même si la base est compromise, le vrai mot de passe est inconnu
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // createToken('auth_token') crée un nouveau token Sanctum pour cet utilisateur
        // Le token est stocké hashé en base (table personal_access_tokens)
        // ->plainTextToken retourne le token en clair (à envoyer au client UNE SEULE FOIS)
        $token = $user->createToken('auth_token')->plainTextToken;

        // On retourne l'utilisateur créé + son token, avec le code HTTP 201 (Created)
        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/login
    // Vérifie les identifiants et retourne un token si c'est bon
    // ─────────────────────────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        // Validation minimale : on vérifie juste que les champs sont présents
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // On cherche l'utilisateur par email dans la base
        // ->first() retourne le premier résultat ou NULL si aucun
        $user = User::where('email', $validated['email'])->first();

        // On vérifie deux choses :
        // 1. L'utilisateur existe (! $user = il n'existe pas)
        // 2. Le mot de passe correspond au hash stocké en base
        //    Hash::check('mot_de_passe_en_clair', 'hash_en_base')
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            // Si l'un ou l'autre échoue, on lève une exception de validation
            // On met toujours l'erreur sur 'email' (et pas 'password') par sécurité :
            // on ne veut pas dire au client si c'est l'email ou le password qui est faux
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        // On supprime tous les anciens tokens de cet utilisateur
        // Cela évite l'accumulation de tokens inutilisés en base
        $user->tokens()->delete();

        // On crée un nouveau token et on le retourne
        $token = $user->createToken('auth_token')->plainTextToken;

        // Code 200 (OK) par défaut quand on ne précise pas
        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/logout
    // Invalide le token utilisé pour cette requête
    // ─────────────────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        // currentAccessToken() peut être null dans certains contextes (tests avec actingAs)
        // On vérifie d'abord qu'un token existe avant de tenter de le supprimer
        $token = $request->user()->currentAccessToken();

        if ($token) {
            // Supprime ce token de la base → il ne fonctionnera plus jamais
            $token->delete();
        }

        return response()->json([
            'message' => 'Déconnecté avec succès.',
        ]);
    }
}
