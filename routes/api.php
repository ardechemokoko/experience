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
    // Authentification OAuth avec Authentik
    Route::get('/authentik/redirect', [AuthController::class, 'redirectToAuthentik'])
        ->name('auth.authentik.redirect');
    
    Route::get('/authentik/callback', [AuthController::class, 'handleAuthentikCallback'])
        ->name('auth.authentik.callback');

    // Authentification locale (inscription et connexion)
    Route::post('/register', [AuthController::class, 'register'])
        ->name('auth.register');
    
    Route::post('/login', [AuthController::class, 'login'])
        ->name('auth.login');
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

