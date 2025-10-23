# 🔄 Solutions de Contournement - Authentik Password Grant

## 🎯 Problème Actuel

Le **Password Grant Flow** n'est pas disponible dans votre version d'Authentik.

## ✅ Solution 1 : Authorization Code Flow (Recommandé)

### Principe
Au lieu de demander email/password directement, on redirige l'utilisateur vers Authentik pour qu'il se connecte.

### Avantages
- ✅ Fonctionne avec toutes les versions d'Authentik
- ✅ Plus sécurisé
- ✅ Supporte les refresh tokens
- ✅ Pas de problème de configuration

### Inconvénients
- ❌ Nécessite une redirection
- ❌ Plus complexe pour l'API

---

## 🔧 Implémentation : Authorization Code Flow

### 1. Modifier AuthController.php

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthentikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class AuthController extends Controller
{
    public function __construct(
        private AuthentikService $authentikService
    ) {}

    /**
     * Redirection vers Authentik pour la connexion
     */
    public function redirectToAuthentik(): JsonResponse
    {
        try {
            $authUrl = Socialite::driver('authentik')->redirect()->getTargetUrl();
            
            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ]);
        } catch (Exception $e) {
            Log::error('Erreur redirection Authentik', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la redirection vers Authentik.'
            ], 500);
        }
    }

    /**
     * Callback d'Authentik après connexion
     */
    public function handleAuthentikCallback(Request $request): JsonResponse
    {
        try {
            $authentikUser = Socialite::driver('authentik')->user();
            
            DB::beginTransaction();
            
            // Synchroniser avec la base locale
            $user = $this->synchronizeUser($authentikUser);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'access_token' => $this->generateAccessToken($authentikUser),
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nom' => $user->personne->nom ?? '',
                    'prenom' => $user->personne->prenom ?? '',
                    'role' => $user->role
                ]
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur callback Authentik', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion.'
            ], 500);
        }
    }

    /**
     * Inscription (utilise toujours l'API Authentik)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            // Créer l'utilisateur dans Authentik
            $authentikUser = $this->authentikService->createUser([
                'email' => $request->email,
                'password' => $request->password,
                'name' => $request->nom . ' ' . $request->prenom,
                'role' => $request->role
            ]);
            
            // Synchroniser avec la base locale
            $user = $this->synchronizeUser([
                'id' => $authentikUser['pk'],
                'email' => $request->email,
                'name' => $request->nom . ' ' . $request->prenom,
                'attributes' => [
                    'email' => $request->email,
                    'name' => $request->nom . ' ' . $request->prenom
                ]
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès. Redirigez vers Authentik pour la connexion.',
                'auth_url' => $this->getAuthentikAuthUrl()
            ], 201);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur inscription', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'URL d'authentification Authentik
     */
    private function getAuthentikAuthUrl(): string
    {
        return Socialite::driver('authentik')->redirect()->getTargetUrl();
    }

    /**
     * Synchroniser un utilisateur Authentik avec la base locale
     */
    private function synchronizeUser($authentikUser)
    {
        $user = \App\Models\Utilisateur::where('email', $authentikUser['email'])->first();
        
        if (!$user) {
            $user = \App\Models\Utilisateur::create([
                'email' => $authentikUser['email'],
                'role' => $this->getUserRole($authentikUser)
            ]);
            
            // Créer la personne associée
            $user->personne()->create([
                'nom' => $authentikUser['name'] ?? 'Nom',
                'prenom' => 'Prénom',
                'email' => $authentikUser['email'],
                'contact' => '0000000000'
            ]);
        }
        
        return $user;
    }

    /**
     * Déterminer le rôle de l'utilisateur
     */
    private function getUserRole($authentikUser): string
    {
        // Logique pour déterminer le rôle basé sur les groupes Authentik
        return 'candidat'; // Par défaut
    }

    /**
     * Générer un token d'accès (simplifié)
     */
    private function generateAccessToken($authentikUser): string
    {
        // TODO: Implémenter avec Sanctum ou JWT
        return base64_encode(json_encode([
            'user_id' => $authentikUser['id'] ?? $authentikUser['pk'],
            'email' => $authentikUser['email'],
            'expires_at' => now()->addHours(1)->timestamp
        ]));
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion.'
            ], 500);
        }
    }
}
```

### 2. Modifier les Routes API

```php
// routes/api.php

Route::prefix('auth')->group(function () {
    // Redirection vers Authentik
    Route::get('/authentik/redirect', [AuthController::class, 'redirectToAuthentik']);
    
    // Callback d'Authentik
    Route::get('/authentik/callback', [AuthController::class, 'handleAuthentikCallback']);
    
    // Inscription
    Route::post('/register', [AuthController::class, 'register']);
    
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

---

## ✅ Solution 2 : Authentification Locale + Synchronisation Authentik

### Principe
Gérer l'authentification localement et synchroniser avec Authentik en arrière-plan.

### Implémentation

```php
// AuthController.php - Version Simplifiée

public function login(LoginRequest $request): JsonResponse
{
    try {
        // Vérifier localement
        $user = \App\Models\Utilisateur::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.'
            ], 404);
        }
        
        // Vérifier le mot de passe (stocké localement)
        if (!password_verify($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.'
            ], 401);
        }
        
        // Synchroniser avec Authentik en arrière-plan (optionnel)
        $this->syncWithAuthentik($user);
        
        return response()->json([
            'success' => true,
            'access_token' => $this->generateAccessToken($user),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la connexion.'
        ], 500);
    }
}

private function syncWithAuthentik($user)
{
    try {
        // Synchroniser avec Authentik sans bloquer l'authentification
        $this->authentikService->syncUser($user);
    } catch (Exception $e) {
        Log::warning('Erreur synchronisation Authentik', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
    }
}
```

---

## ✅ Solution 3 : Utiliser Laravel Sanctum (Plus Simple)

### Principe
Abandonner Authentik pour l'authentification et utiliser Sanctum.

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Configuration

```php
// AuthController.php - Version Sanctum

use Laravel\Sanctum\HasApiTokens;

public function login(LoginRequest $request): JsonResponse
{
    $user = \App\Models\Utilisateur::where('email', $request->email)->first();
    
    if (!$user || !password_verify($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Identifiants incorrects.'
        ], 401);
    }
    
    $token = $user->createToken('auth-token')->plainTextToken;
    
    return response()->json([
        'success' => true,
        'access_token' => $token,
        'user' => $user
    ]);
}
```

---

## 🎯 Recommandation

**Pour votre cas, je recommande la Solution 1 (Authorization Code Flow)** car :

1. ✅ Fonctionne avec votre configuration Authentik actuelle
2. ✅ Garde la sécurité d'Authentik
3. ✅ Pas besoin de reconfigurer le Provider
4. ✅ Plus sécurisé que l'authentification locale

**Voulez-vous que j'implémente la Solution 1 ?** 🚀
