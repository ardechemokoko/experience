# 🔐 Configuration Authentik pour l'Authentification OAuth/OpenID Connect

## 📋 Variables d'environnement à ajouter dans votre `.env`

Ajoutez ces lignes à la fin de votre fichier `.env` :

```env
# ============================================
# Configuration Authentik OAuth/OpenID Connect
# ============================================

# URL de base de votre instance Authentik
AUTHENTIK_BASE_URL=https://your-authentik-instance.com

# Client ID de l'application OAuth créée dans Authentik
AUTHENTIK_CLIENT_ID=your_client_id_here

# Client Secret de l'application OAuth créée dans Authentik
AUTHENTIK_CLIENT_SECRET=your_client_secret_here

# URL de redirection après authentification (callback)
AUTHENTIK_REDIRECT_URI=http://localhost:8000/api/auth/authentik/callback
```

---

## 🛠️ Configuration dans Authentik

### Étape 1 : Créer un Provider OAuth2/OpenID

1. Connectez-vous à votre instance Authentik
2. Allez dans **Applications** > **Providers**
3. Cliquez sur **Create** et sélectionnez **OAuth2/OpenID Provider**
4. Configurez les paramètres suivants :

| Paramètre | Valeur |
|-----------|--------|
| **Name** | `Auto École API` |
| **Authorization flow** | `Authorization Code` |
| **Client type** | `Confidential` |
| **Client ID** | *Laissez générer automatiquement* |
| **Client Secret** | *Laissez générer automatiquement* |
| **Redirect URIs** | `http://localhost:8000/api/auth/authentik/callback` |
| **Signing Key** | *Sélectionnez une clé existante* |

5. Dans la section **Advanced protocol settings** :
   - **Scopes** : `openid`, `email`, `profile`
   - **Subject mode** : `Based on the User's Email`

6. Cliquez sur **Create**

### Étape 2 : Créer une Application

1. Allez dans **Applications** > **Applications**
2. Cliquez sur **Create**
3. Configurez :

| Paramètre | Valeur |
|-----------|--------|
| **Name** | `Auto École Application` |
| **Slug** | `auto-ecole-app` |
| **Provider** | *Sélectionnez le provider créé à l'étape 1* |
| **UI Settings** | *Configurez selon vos préférences* |

4. Cliquez sur **Create**

### Étape 3 : Récupérer les credentials

1. Retournez dans **Applications** > **Providers**
2. Cliquez sur votre provider `Auto École API`
3. Copiez le **Client ID** et le **Client Secret**
4. Ajoutez-les dans votre fichier `.env`

---

## 🚀 Routes API Disponibles

### Routes Publiques (sans authentification)

#### 1. Obtenir l'URL d'authentification Authentik
```http
GET /api/auth/authentik/redirect
```

**Réponse :**
```json
{
  "success": true,
  "auth_url": "https://authentik.com/authorize?client_id=...",
  "message": "Redirigez l'utilisateur vers cette URL pour s'authentifier"
}
```

#### 2. Callback après authentification Authentik
```http
GET /api/auth/authentik/callback?code=xxx&state=yyy
```

**Réponse :**
```json
{
  "success": true,
  "message": "Authentification réussie",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "role": "candidat"
  },
  "access_token": "token_here",
  "token_type": "Bearer"
}
```

#### 3. Inscription locale (sans OAuth)
```http
POST /api/auth/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "nom": "Dupont",
  "prenom": "Jean",
  "contact": "0612345678",
  "adresse": "123 rue Example",
  "role": "candidat"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Inscription réussie",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "role": "candidat"
  },
  "access_token": "token_here",
  "token_type": "Bearer"
}
```

#### 4. Connexion locale (sans OAuth)
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "SecurePass123"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "role": "candidat"
  },
  "access_token": "token_here",
  "token_type": "Bearer"
}
```

### Routes Protégées (authentification requise)

#### 5. Obtenir les informations de l'utilisateur connecté
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
    "email": "user@example.com",
    "role": "candidat",
    "personne": {
      "nom": "Dupont",
      "prenom": "Jean",
      "contact": "0612345678"
    }
  }
}
```

#### 6. Déconnexion
```http
POST /api/auth/logout
Authorization: Bearer {access_token}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

#### 7. Health Check (test de l'API)
```http
GET /api/health
```

**Réponse :**
```json
{
  "status": "ok",
  "message": "API Auto-École fonctionnelle",
  "timestamp": "2025-10-22T21:30:00+00:00"
}
```

---

## 🔄 Flux d'authentification OAuth complet

### Depuis votre Frontend

```javascript
// 1. Obtenir l'URL d'authentification
const response = await fetch('http://localhost:8000/api/auth/authentik/redirect');
const data = await response.json();

// 2. Rediriger l'utilisateur vers Authentik
window.location.href = data.auth_url;

// 3. Après authentification, Authentik redirige vers votre callback
// avec un code: /api/auth/authentik/callback?code=xxx

// 4. Le backend traite le callback et retourne un token
// que vous stockez dans localStorage ou un cookie
localStorage.setItem('access_token', data.access_token);

// 5. Utiliser le token pour les requêtes authentifiées
const userResponse = await fetch('http://localhost:8000/api/auth/me', {
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('access_token')}`
  }
});
```

---

## 🔒 Sécurité

### ⚠️ Important : Token d'accès

Le système actuel utilise un token simple encodé en base64. Pour la production, il est **fortement recommandé** d'implémenter :

- **Laravel Sanctum** pour les tokens API
- **JWT (tymon/jwt-auth)** pour les tokens JSON Web Token

### Installation de Laravel Sanctum (recommandé)

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

Puis modifiez `AuthController` pour utiliser Sanctum :

```php
// Dans generateAccessToken()
return $utilisateur->createToken('auth_token')->plainTextToken;
```

---

## 📝 Notes

- Les routes API sont préfixées par `/api`
- CORS doit être configuré pour permettre les requêtes depuis votre frontend
- En production, utilisez HTTPS pour toutes les URLs
- Configurez correctement les `Redirect URIs` dans Authentik

---

## 🧪 Tester l'API

### Avec cURL

```bash
# Test health check
curl http://localhost:8000/api/health

# Inscription
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "SecurePass123",
    "password_confirmation": "SecurePass123",
    "nom": "Test",
    "prenom": "User",
    "contact": "0600000000"
  }'

# Connexion
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "SecurePass123"
  }'
```

### Avec Postman

Importez la collection Postman (à créer) avec toutes les routes pré-configurées.

---

## 🆘 Support

En cas de problème, vérifiez :

1. Les logs Laravel : `storage/logs/laravel.log`
2. La configuration `.env` est correcte
3. Les migrations sont exécutées : `php artisan migrate`
4. Le cache est clear : `php artisan config:clear`

---

**Documentation créée le 22 octobre 2025** 🚀

