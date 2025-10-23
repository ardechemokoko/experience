<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes API pour l'authentification et la gestion de l'auto-école
|
*/

// Routes d'authentification publiques (sans authentification requise)
Route::prefix('auth')->group(function () {
    // Authentification OAuth avec Authentik (Authorization Code Flow)
    Route::get('/authentik/redirect', [AuthController::class, 'redirectToAuthentik'])
        ->name('auth.authentik.redirect');
    
    Route::get('/authentik/callback', [AuthController::class, 'handleAuthentikCallback'])
        ->name('auth.authentik.callback');

    // Obtenir l'URL d'authentification pour le frontend
    Route::get('/auth-url', [AuthController::class, 'getAuthUrl'])
        ->name('auth.url');

    // Inscription (crée l'utilisateur et retourne l'URL d'auth)
    Route::post('/register', [AuthController::class, 'register'])
        ->name('auth.register');

    // Rafraîchir le token d'accès (sans authentification car le token est expiré)
    Route::post('/refresh', [AuthController::class, 'refreshToken'])
        ->name('auth.refresh');
});

// Routes protégées (authentification requise)
Route::middleware('auth:api')->group(function () {
    // Déconnexion
    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');
    
    // Obtenir les informations de l'utilisateur connecté
    Route::get('/auth/me', [AuthController::class, 'me'])
        ->name('auth.me');

    // Ici, vous pouvez ajouter d'autres routes protégées pour votre application
    // Exemple:
    // Route::apiResource('candidats', CandidatController::class);
    // Route::apiResource('auto-ecoles', AutoEcoleController::class);
    // Route::apiResource('dossiers', DossierController::class);
});

// Route de test (publique)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API Auto-École fonctionnelle',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('api.health');

