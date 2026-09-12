<?php

// On importe les classes nécessaires au démarrage de l'application
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        //
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // Pour toute requête API, on retourne des erreurs JSON structurées
        // au lieu des pages HTML d'erreur par défaut
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // ── 401 : pas de token ou token invalide ───────────────────
        // Par défaut Laravel redirige vers /login — on retourne du JSON à la place
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Non authentifié. Veuillez vous connecter.',
                ], 401);
            }
        });

        // ── 403 : token valide mais accès refusé par la Policy ─────
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Accès refusé. Vous n\'êtes pas autorisé à effectuer cette action.',
                ], 403);
            }
        });

        // ── 404 : modèle introuvable (Route Model Binding) ────────
        // Laravel peut lever ModelNotFoundException ou NotFoundHttpException selon le contexte
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                // On regarde si c'est causé par un ModelNotFoundException
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    $model = class_basename($previous->getModel());
                    return response()->json([
                        'message' => "{$model} introuvable.",
                    ], 404);
                }
                return response()->json(['message' => 'Ressource introuvable.'], 404);
            }
        });

        // ── 422 : erreurs de validation ────────────────────────────
        // Laravel gère déjà le 422, mais on uniformise le format de la réponse
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    // Message global résumé
                    'message' => 'Les données fournies sont invalides.',
                    // Détail des erreurs par champ : {"email": ["L'email est requis."]}
                    'errors'  => $e->errors(),
                ], 422);
            }
        });
    })->create();
