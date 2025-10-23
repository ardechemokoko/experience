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

// Routes protégées (Authentification requise - tous les utilisateurs authentifiés)
Route::middleware(['auth.token'])->group(function () {
    // Routes de lecture (GET) - maintenant protégées
    Route::get('/candidats', [CandidatController::class, 'index']);
    Route::get('/candidats/{candidat}', [CandidatController::class, 'show']);
    Route::get('/auto-ecoles', [AutoEcoleController::class, 'index']);
    Route::get('/auto-ecoles/{auto_ecole}', [AutoEcoleController::class, 'show']);
    Route::get('/auto-ecoles/{auto_ecole}/formations', [AutoEcoleController::class, 'formations'])->name('auto-ecoles.formations');
    Route::get('/formations', [FormationAutoEcoleController::class, 'index']);
    Route::get('/formations/{formation}', [FormationAutoEcoleController::class, 'show']);
    Route::get('/formations/{formation}/documents-requis', [FormationAutoEcoleController::class, 'documentsRequis'])->name('formations.documents-requis');
    Route::get('/dossiers', [DossierController::class, 'index']);
    Route::get('/dossiers/{dossier}', [DossierController::class, 'show']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
    Route::get('/referentiels', [ReferentielController::class, 'index']);
    Route::get('/referentiels/{referentiel}', [ReferentielController::class, 'show']);
    // Candidats
    Route::post('/candidats/complete-profile', [CandidatController::class, 'completeProfile'])->name('candidats.complete-profile');
    Route::post('/candidats/inscription-formation', [CandidatController::class, 'inscriptionFormation'])->name('candidats.inscription-formation');
    Route::get('/candidats/mes-dossiers', [CandidatController::class, 'mesDossiers'])->name('candidats.mes-dossiers');
    Route::post('/candidats', [CandidatController::class, 'store']);
    Route::put('/candidats/{candidat}', [CandidatController::class, 'update']);
    Route::patch('/candidats/{candidat}', [CandidatController::class, 'update']);
    Route::delete('/candidats/{candidat}', [CandidatController::class, 'destroy']);
    
    // Auto-écoles
    Route::get('/auto-ecoles/mes-dossiers', [AutoEcoleController::class, 'mesDossiers'])->name('auto-ecoles.mes-dossiers');
    Route::post('/auto-ecoles', [AutoEcoleController::class, 'store']);
    Route::put('/auto-ecoles/{auto_ecole}', [AutoEcoleController::class, 'update']);
    Route::patch('/auto-ecoles/{auto_ecole}', [AutoEcoleController::class, 'update']);
    Route::delete('/auto-ecoles/{auto_ecole}', [AutoEcoleController::class, 'destroy']);
    
    // Formations
    Route::post('/formations', [FormationAutoEcoleController::class, 'store']);
    Route::put('/formations/{formation}', [FormationAutoEcoleController::class, 'update']);
    Route::patch('/formations/{formation}', [FormationAutoEcoleController::class, 'update']);
    Route::delete('/formations/{formation}', [FormationAutoEcoleController::class, 'destroy']);
    
    // Dossiers
    Route::post('/dossiers', [DossierController::class, 'store']);
    Route::put('/dossiers/{dossier}', [DossierController::class, 'update']);
    Route::patch('/dossiers/{dossier}', [DossierController::class, 'update']);
    Route::delete('/dossiers/{dossier}', [DossierController::class, 'destroy']);
    Route::post('/dossiers/{dossier}/upload-document', [DossierController::class, 'uploadDocument'])->name('dossiers.upload-document');
    Route::post('/dossiers/{dossier}/valider', [DossierController::class, 'valider'])->name('dossiers.valider');
    
    // Documents
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::put('/documents/{document}', [DocumentController::class, 'update']);
    Route::patch('/documents/{document}', [DocumentController::class, 'update']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
    Route::post('/documents/{document}/valider', [DossierController::class, 'validerDocument'])->name('documents.valider');
    
    // Référentiels
    Route::post('/referentiels', [ReferentielController::class, 'store']);
    Route::put('/referentiels/{referentiel}', [ReferentielController::class, 'update']);
    Route::patch('/referentiels/{referentiel}', [ReferentielController::class, 'update']);
    Route::delete('/referentiels/{referentiel}', [ReferentielController::class, 'destroy']);
});

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

