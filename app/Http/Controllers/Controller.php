<?php

namespace App\Http\Controllers;

// On importe le trait AuthorizesRequests qui fournit la méthode $this->authorize()
// En Laravel 13, la classe Controller de base est vide — il faut l'ajouter manuellement
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Ce trait ajoute la méthode authorize() à tous les controllers qui étendent cette classe
    // authorize('view', $model) → consulte la Policy correspondante et lève une 403 si refusé
    use AuthorizesRequests;
}
