# 🚀 Auto-École API - Guide Complet Authentik

## 📋 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Installation et Configuration](#installation-et-configuration)
3. [Génération des Clés Authentik](#génération-des-clés-authentik)
4. [Configuration Laravel](#configuration-laravel)
5. [Architecture du Système](#architecture-du-système)
6. [Endpoints API](#endpoints-api)
7. [Exemples d'Utilisation](#exemples-dutilisation)
8. [Dépannage](#dépannage)
9. [Sécurité](#sécurité)

---

## 🎯 Vue d'Ensemble

### Objectif
Ce système utilise **Authentik** comme de votre application Auto-École, permettant une gestion centralisée des utilisateurs, des rôles et des permissions.

### Architecture
```
Frontend → Laravel API → Authentik IAM → Base de Données Locale
```

### Types d'Utilisateurs
- **Candidats** : Utilisateurs finaux souhaitant passer leur permis
- **Responsables Auto-École** : Gestionnaires d'auto-écoles
- **Administrateurs** : Super-administrateurs du système

---

## 🔧 Installation et Configuration

### Prérequis
- Laravel 12
- Authentik Server (http://5.189.156.115:31015)
- PHP 8.1+
- Composer

### 1. Installation des Packages Laravel

```bash
# Package principal pour Authentik
composer require socialiteproviders/authentik

# Dépendances
composer require guzzlehttp/guzzle
```

### 2. Configuration des Services

**Fichier : `config/services.php`**
```php
return [
    // ... autres services

    'authentik' => [
        'base_url' => env('AUTHENTIK_BASE_URL', 'http://5.189.156.115:31015'),
        'client_id' => env('AUTHENTIK_CLIENT_ID'),
        'client_secret' => env('AUTHENTIK_CLIENT_SECRET'),
        'redirect' => env('AUTHENTIK_REDIRECT_URI'),
        'api_token' => env('AUTHENTIK_API_TOKEN'),
    ],
];
```

### 3. Variables d'Environnement

**Fichier : `.env`**
```env
# Authentik Configuration
AUTHENTIK_BASE_URL=http://5.189.156.115:31015
AUTHENTIK_CLIENT_ID=your_client_id_here
AUTHENTIK_CLIENT_SECRET=your_client_secret_here
AUTHENTIK_REDIRECT_URI=http://localhost:8000/api/auth/authentik/callback
AUTHENTIK_API_TOKEN=your_api_token_here

# Session Configuration
SESSION_DRIVER=cookie
```

---

## 🔑 Génération des Clés Authentik

### 1. Créer un Token API

#### Étape 1 : Accéder à Authentik
```
URL: http://5.189.156.115:31015
Utilisateur: akadmin
Mot de passe: [Votre mot de passe]
```

#### Étape 2 : Créer le Token
1. **Cliquez sur votre nom** (en haut à droite)
2. **Sélectionnez "Admin Interface"**
3. **Applications** → **Tokens** → **Create**
4. **Remplissez :**
   ```
   Identifier: Auto-Ecole-API-Token
   Intent: api
   Description: Token pour l'API Auto-École
   ```
5. **Cliquez sur "Create"**
6. **Copiez le token généré** (il ne sera affiché qu'une seule fois)

#### Étape 3 : Configurer le Token
```env
AUTHENTIK_API_TOKEN=votre_token_généré_ici
```

### 2. Créer un Provider OAuth

#### Étape 1 : Créer le Provider
1. **Admin Interface** → **Applications** → **Providers**
2. **Create** → **OAuth2/OpenID Provider**
3. **Configuration :**
   ```
   Name: Auto-Ecole-OAuth-Provider
   Client Type: Confidential
   Authorization Flow: Authorization Code
   Redirect URIs: http://localhost:8000/api/auth/authentik/callback
   ```

#### Étape 2 : Récupérer les Credentials
1. **Cliquez sur le Provider créé**
2. **Copiez le Client ID**
3. **Cliquez sur "Show" pour le Client Secret**
4. **Mettez à jour .env :**
   ```env
   AUTHENTIK_CLIENT_ID=votre_client_id
   AUTHENTIK_CLIENT_SECRET=votre_client_secret
   ```

### 3. Créer les Groupes de Rôles

#### Groupe Candidats
1. **Admin Interface** → **Applications** → **Groups**
2. **Create**
3. **Configuration :**
   ```
   Name: Candidats
   Parent: /
   Users: [Laisser vide pour l'instant]
   ```

#### Groupe Responsables
```
Name: Responsables Auto-École
Parent: /
Users: [Laisser vide pour l'instant]
```

#### Groupe Administrateurs
```
Name: Administrateurs
Parent: /
Users: [Laisser vide pour l'instant]
```

---

## ⚙️ Configuration Laravel

### 1. Service Provider

**Fichier : `app/Providers/AppServiceProvider.php`**
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use SocialiteProviders\Authentik\Provider as AuthentikProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Enregistrer le provider Authentik
        $this->app->make(SocialiteFactory::class)->extend('authentik', function ($app) {
            return $this->app->make(AuthentikProvider::class);
        });
    }
}
```

### 2. Routes API

**Fichier : `routes/api.php`**
```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Routes d'authentification publiques
Route::prefix('auth')->group(function () {
    // Authentification OAuth avec Authentik
    Route::get('/authentik/redirect', [AuthController::class, 'redirectToAuthentik']);
    Route::get('/authentik/callback', [AuthController::class, 'handleAuthentikCallback']);

    // Obtenir l'URL d'authentification pour le frontend
    Route::get('/auth-url', [AuthController::class, 'getAuthUrl']);

    // Inscription (crée l'utilisateur et retourne l'URL d'auth)
    Route::post('/register', [AuthController::class, 'register']);

    // 🚀 NOUVELLE ROUTE : Connexion directe avec contournement Password Grant
    Route::post('/login-direct', [AuthController::class, 'loginDirect']);

    // Rafraîchir le token d'accès
    Route::post('/refresh', [AuthController::class, 'refreshToken']);
});

// Routes protégées
Route::middleware('auth:api')->group(function () {
    // Déconnexion
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Obtenir les informations de l'utilisateur connecté
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// Route de test
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API Auto-École fonctionnelle',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

---

## 🏗️ Architecture du Système

### 1. AuthentikService

**Fichier : `app/Services/AuthentikService.php`**

Ce service gère toutes les interactions avec Authentik :

#### Méthodes Principales :
- `createUser()` : Créer un utilisateur dans Authentik
- `authenticateUserDirect()` : **Authentification directe (contournement)**
- `getUserByEmail()` : Récupérer un utilisateur par email
- `addUserToRoleGroup()` : Ajouter un utilisateur à un groupe
- `logout()` : Déconnexion et révocation des tokens

#### Contournement Password Grant :
```php
public function authenticateUserDirect(string $email, string $password): array
{
    // 1. Obtenir un token d'API
    $apiToken = $this->getApiAccessToken();
    
    // 2. Vérifier si l'utilisateur existe
    $user = $this->getUserByEmailWithToken($email, $apiToken);
    
    // 3. Vérifier le mot de passe
    $passwordValid = $this->verifyPasswordDirect($user['pk'], $password, $apiToken);
    
    // 4. Générer des tokens personnalisés
    $tokens = $this->generateCustomTokens($user);
    
    return [
        'success' => true,
        'user' => $user,
        'tokens' => $tokens
    ];
}
```

### 2. AuthController

**Fichier : `app/Http/Controllers/Api/AuthController.php`**

#### Méthodes Principales :
- `register()` : Inscription d'un utilisateur
- `loginDirect()` : **Connexion directe (contournement)**
- `handleAuthentikCallback()` : Gestion du callback OAuth
- `logout()` : Déconnexion
- `refreshToken()` : Rafraîchissement des tokens

### 3. Base de Données

#### Tables Principales :
- `utilisateurs` : Utilisateurs locaux (synchronisés avec Authentik)
- `personnes` : Informations personnelles des utilisateurs
- `candidats` : Données spécifiques aux candidats
- `auto_ecoles` : Informations des auto-écoles
- `dossiers` : Dossiers de formation

---

## 🔗 Endpoints API

### Authentification Publique

#### 1. Inscription
```http
POST /api/auth/register
Content-Type: application/json

{
  "email": "nouveau@test.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Nouveau",
  "prenom": "Utilisateur",
  "contact": "0600000000",
  "role": "candidat"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Inscription réussie. Redirigez l'utilisateur vers Authentik pour se connecter.",
  "user": {
    "id": "uuid",
    "email": "nouveau@test.com",
    "role": "candidat"
  },
  "auth_url": "http://5.189.156.115:31015/application/o/authorize/..."
}
```

#### 2. Connexion Directe (Contournement)
```http
POST /api/auth/login-direct
Content-Type: application/json

{
  "email": "candidat@test.com",
  "password": "Password123!"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Connexion réussie !",
  "user": {
    "id": "uuid",
    "email": "candidat@test.com",
    "role": "candidat"
  },
  "access_token": "eyJ1c2VyX2lkIjoyOCwiZW1haWwiOi...",
  "refresh_token": "eyJ1c2VyX2lkIjoyOCwidHlwZSI6In...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "method": "direct_auth"
}
```

#### 3. Obtenir URL d'Authentification
```http
GET /api/auth/auth-url
```

**Réponse :**
```json
{
  "success": true,
  "auth_url": "http://5.189.156.115:31015/application/o/authorize/...",
  "message": "Redirigez l'utilisateur vers cette URL pour s'authentifier."
}
```

### Authentification Protégée

#### 4. Informations Utilisateur
```http
GET /api/auth/me
Authorization: Bearer {access_token}
```

**Réponse :**
```json
{
  "success": true,
  "user": {
    "id": "uuid",
    "email": "candidat@test.com",
    "role": "candidat",
    "personne": {
      "id": "uuid",
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "candidat@test.com",
      "contact": "0612345678",
      "adresse": "123 Rue de Paris"
    }
  }
}
```

#### 5. Déconnexion
```http
POST /api/auth/logout
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "refresh_token": "eyJ1c2VyX2lkIjoyOCwidHlwZSI6In..."
}
```

#### 6. Rafraîchir Token
```http
POST /api/auth/refresh
Content-Type: application/json

{
  "refresh_token": "eyJ1c2VyX2lkIjoyOCwidHlwZSI6In..."
}
```

---

## 📱 Exemples d'Utilisation

### 1. Frontend JavaScript

#### Connexion Directe
```javascript
const loginUser = async (email, password) => {
  try {
    const response = await fetch('/api/auth/login-direct', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password })
    });

    const result = await response.json();
    
    if (result.success) {
      // Stocker le token
      localStorage.setItem('access_token', result.access_token);
      localStorage.setItem('refresh_token', result.refresh_token);
      
      // Rediriger vers le dashboard
      window.location.href = '/dashboard';
    } else {
      alert('Erreur de connexion: ' + result.message);
    }
  } catch (error) {
    console.error('Erreur:', error);
  }
};
```

#### Requête Authentifiée
```javascript
const fetchUserData = async () => {
  const token = localStorage.getItem('access_token');
  
  const response = await fetch('/api/auth/me', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const result = await response.json();
  return result.user;
};
```

### 2. Postman

#### Configuration
```
Base URL: http://localhost:8000
Method: POST
URL: /api/auth/login-direct
Headers:
  Content-Type: application/json
Body:
{
  "email": "candidat@test.com",
  "password": "Password123!"
}
```

#### Variables Postman
```
{{base_url}} = http://localhost:8000
{{access_token}} = [Récupéré depuis la réponse de connexion]
{{refresh_token}} = [Récupéré depuis la réponse de connexion]
```

### 3. cURL

#### Connexion
```bash
curl -X POST http://localhost:8000/api/auth/login-direct \
  -H "Content-Type: application/json" \
  -d '{
    "email": "candidat@test.com",
    "password": "Password123!"
  }'
```

#### Requête Authentifiée
```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

---

## 🔍 Dépannage

### Problèmes Courants

#### 1. Erreur "Token invalid/expired"
**Cause :** Token API Authentik invalide ou expiré
**Solution :**
1. Vérifiez `AUTHENTIK_API_TOKEN` dans `.env`
2. Créez un nouveau token dans Authentik
3. Mettez à jour `.env`
4. Videz le cache : `php artisan config:clear`

#### 2. Erreur "Utilisateur non trouvé"
**Cause :** L'utilisateur n'existe pas dans Authentik
**Solution :**
1. Vérifiez que l'utilisateur existe dans Authentik
2. Utilisez l'inscription pour créer l'utilisateur
3. Vérifiez les permissions du token API

#### 3. Erreur "Mot de passe incorrect"
**Cause :** Le mot de passe ne correspond pas
**Solution :**
1. Vérifiez le mot de passe dans Authentik
2. Réinitialisez le mot de passe si nécessaire
3. Vérifiez que l'utilisateur est actif

#### 4. Erreur 403 Forbidden
**Cause :** Permissions insuffisantes
**Solution :**
1. Vérifiez les permissions du token API
2. Assurez-vous que le token a le scope `api`
3. Vérifiez les groupes et rôles de l'utilisateur

### Logs et Debug

#### Vérifier les Logs Laravel
```bash
tail -f storage/logs/laravel.log
```

#### Tester la Configuration
```bash
php artisan config:show services.authentik
```

#### Vérifier la Connexion Authentik
```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
  http://5.189.156.115:31015/api/v3/core/users/
```

---

## 🔒 Sécurité

### Bonnes Pratiques

#### 1. Tokens API
- **Ne jamais commiter** les tokens dans le code
- **Utiliser des variables d'environnement**
- **Renouveler régulièrement** les tokens
- **Utiliser des tokens avec des permissions minimales**

#### 2. Mots de Passe
- **Authentik gère** tous les mots de passe
- **Pas de stockage local** des mots de passe
- **Validation côté Authentik** uniquement

#### 3. Tokens d'Accès
- **Durée de vie limitée** (1 heure par défaut)
- **Révocation automatique** lors de la déconnexion
- **Refresh tokens** pour le renouvellement

#### 4. HTTPS en Production
```env
AUTHENTIK_BASE_URL=https://authentik.votre-domaine.com
AUTHENTIK_REDIRECT_URI=https://api.votre-domaine.com/api/auth/authentik/callback
```

### Configuration de Production

#### Variables d'Environnement
```env
APP_ENV=production
APP_DEBUG=false

AUTHENTIK_BASE_URL=https://authentik.votre-domaine.com
AUTHENTIK_CLIENT_ID=production_client_id
AUTHENTIK_CLIENT_SECRET=production_client_secret
AUTHENTIK_REDIRECT_URI=https://api.votre-domaine.com/api/auth/authentik/callback
AUTHENTIK_API_TOKEN=production_api_token
```

#### Sécurité des Headers
```php
// Dans votre middleware ou configuration
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
```

---

## 🎯 Résumé

### Ce qui a été implémenté :

1. **✅ Authentik comme IAM central** - Gestion complète des utilisateurs
2. **✅ Contournement Password Grant** - Authentification directe via API
3. **✅ Synchronisation bidirectionnelle** - Entre Authentik et base locale
4. **✅ Gestion des rôles** - Via les groupes Authentik
5. **✅ Tokens personnalisés** - Génération et gestion des tokens
6. **✅ API complète** - Endpoints pour toutes les opérations

### Flux d'Authentification :

```
1. Inscription → Création dans Authentik + Base locale
2. Connexion → Vérification via API Authentik
3. Tokens → Génération de tokens personnalisés
4. Autorisation → Basée sur les rôles Authentik
5. Déconnexion → Révocation des tokens
```

### Avantages :

- **🔒 Sécurité maximale** - Authentik comme source de vérité
- **🚀 Performance** - Contournement des limitations OAuth
- **🔄 Flexibilité** - Tokens personnalisés et synchronisation
- **📊 Traçabilité** - Logs complets de toutes les opérations
- **🎯 Simplicité** - API claire et documentée

---

## 📞 Support

### En cas de problème :

1. **Vérifiez les logs** Laravel et Authentik
2. **Testez la configuration** avec les scripts fournis
3. **Vérifiez les permissions** des tokens et utilisateurs
4. **Consultez la documentation** Authentik officielle

### Ressources Utiles :

- [Documentation Authentik](https://goauthentik.io/docs/)
- [Laravel Socialite](https://laravel.com/docs/socialite)
- [OAuth2 RFC](https://tools.ietf.org/html/rfc6749)

---

**🎉 Votre système d'authentification est maintenant opérationnel avec Authentik comme IAM central !**
