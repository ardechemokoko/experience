<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CandidatController;
use App\Http\Controllers\Api\AutoEcoleController;
use App\Http\Controllers\Api\FormationAutoEcoleController;
use App\Http\Controllers\Api\DossierController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ReferentielController;
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

    // 🚀 NOUVELLE ROUTE : Connexion directe avec contournement Password Grant
    Route::post('/login-direct', [AuthController::class, 'loginDirect'])
        ->name('auth.login.direct');

    // Rafraîchir le token d'accès (sans authentification car le token est expiré)
    Route::post('/refresh', [AuthController::class, 'refreshToken'])
        ->name('auth.refresh');
    
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');
});

// Routes spécifiques pour le flux d'inscription candidat
Route::prefix('candidats')->group(function () {
    Route::post('/complete-profile', [CandidatController::class, 'completeProfile'])->name('candidats.complete-profile');
    Route::post('/inscription-formation', [CandidatController::class, 'inscriptionFormation'])->name('candidats.inscription-formation');
    Route::get('/mes-dossiers', [CandidatController::class, 'mesDossiers'])->name('candidats.mes-dossiers');
});

// Routes spécifiques pour auto-écoles
Route::get('/auto-ecoles/{auto_ecole}/formations', [AutoEcoleController::class, 'formations'])->name('auto-ecoles.formations');
Route::get('/auto-ecoles/mes-dossiers', [AutoEcoleController::class, 'mesDossiers'])->name('auto-ecoles.mes-dossiers');

// Routes spécifiques pour formations
Route::get('/formations/{formation}/documents-requis', [FormationAutoEcoleController::class, 'documentsRequis'])->name('formations.documents-requis');

// Routes spécifiques pour dossiers
Route::post('/dossiers/{dossier}/upload-document', [DossierController::class, 'uploadDocument'])->name('dossiers.upload-document');
Route::post('/dossiers/{dossier}/valider', [DossierController::class, 'valider'])->name('dossiers.valider');

// Routes spécifiques pour documents
Route::post('/documents/{document}/valider', [DossierController::class, 'validerDocument'])->name('documents.valider');

// Routes des ressources métier (CRUD standard)
Route::apiResource('candidats', CandidatController::class);
Route::apiResource('auto-ecoles', AutoEcoleController::class);
Route::apiResource('formations', FormationAutoEcoleController::class);
Route::apiResource('dossiers', DossierController::class);
Route::apiResource('documents', DocumentController::class);
Route::apiResource('referentiels', ReferentielController::class);

// Route de test (publique)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API Auto-École fonctionnelle',
        'timestamp' => now()->toIso8601String(),
        'version' => '1.0.0',
        'environment' => config('app.env'),
    ]);
})->name('api.health');

