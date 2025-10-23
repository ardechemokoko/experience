# 🚪 Guide : Déconnexion avec Authentik

## ✅ Fonctionnalité Implémentée

La déconnexion **révoque les tokens côté Authentik** pour une vraie déconnexion sécurisée !

```
Déconnexion
    ↓
1. Récupère le token depuis Authorization header
    ↓
2. Révoque le access_token dans Authentik
    ↓
3. Révoque le refresh_token dans Authentik (si fourni)
    ↓
✅ Utilisateur complètement déconnecté !
```

---

## 📡 Endpoint de Déconnexion

### Route
```
POST /api/auth/logout
```

### Authentification
✅ **Requise** - Le token doit être envoyé dans le header

---

## 🧪 Test avec Postman

### Méthode 1 : Déconnexion Simple (Access Token uniquement)

**Configuration Postman :**

```
Method: POST
URL: http://localhost:8000/api/auth/logout

Headers:
  Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Réponse Attendue (200 OK) :**
```json
{
  "success": true,
  "message": "Déconnexion réussie. À bientôt !"
}
```

---

### Méthode 2 : Déconnexion Complète (Access + Refresh Token)

**Configuration Postman :**

```
Method: POST
URL: http://localhost:8000/api/auth/logout

Headers:
  Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...
  Content-Type: application/json

Body (raw JSON):
{
  "refresh_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Réponse Attendue (200 OK) :**
```json
{
  "success": true,
  "message": "Déconnexion réussie. À bientôt !"
}
```

**Avantage :** Révoque à la fois l'access token ET le refresh token.

---

## 🔄 Workflow Complet : Connexion → Déconnexion

### 1. Connexion

```bash
POST http://localhost:8000/api/auth/login

{
  "email": "user@example.com",
  "password": "Password123!"
}
```

**Réponse :**
```json
{
  "success": true,
  "user": { ... },
  "access_token": "eyJhbGc...",     ← Sauvegarder
  "refresh_token": "eyJhbGc...",    ← Sauvegarder
  "expires_in": 3600
}
```

### 2. Utilisation de l'API

```bash
GET http://localhost:8000/api/auth/me
Authorization: Bearer eyJhbGc...
```

### 3. Déconnexion

```bash
POST http://localhost:8000/api/auth/logout
Authorization: Bearer eyJhbGc...

{
  "refresh_token": "eyJhbGc..."
}
```

**Résultat :**
- ✅ Access token révoqué dans Authentik
- ✅ Refresh token révoqué dans Authentik
- ✅ L'utilisateur est complètement déconnecté

---

## 🔄 Bonus : Rafraîchissement de Token

J'ai aussi ajouté un endpoint pour **rafraîchir le token** quand il expire !

### Route
```
POST /api/auth/refresh
```

### Utilisation

**Quand le token expire (401), appelez :**

```bash
POST http://localhost:8000/api/auth/refresh
Content-Type: application/json

{
  "refresh_token": "eyJhbGc..."
}
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Token rafraîchi avec succès.",
  "access_token": "eyJhbGc...",      ← Nouveau token
  "refresh_token": "eyJhbGc...",     ← Nouveau refresh token
  "token_type": "Bearer",
  "expires_in": 3600
}
```

---

## 📊 Routes API Complètes

| Route | Méthode | Auth Required | Description |
|-------|---------|---------------|-------------|
| `/api/auth/register` | POST | ❌ Non | Inscription (crée dans Authentik) |
| `/api/auth/login` | POST | ❌ Non | Connexion (via Authentik) |
| `/api/auth/refresh` | POST | ❌ Non | Rafraîchir le token |
| `/api/auth/logout` | POST | ✅ **Oui** | Déconnexion (révoque tokens) |
| `/api/auth/me` | GET | ✅ **Oui** | Profil utilisateur |
| `/api/auth/authentik/redirect` | GET | ❌ Non | URL OAuth Authentik |
| `/api/auth/authentik/callback` | GET | ❌ Non | Callback OAuth |

---

## 💻 Intégration Frontend

### Service JavaScript Complet

```javascript
// services/auth.service.js

class AuthService {
  constructor() {
    this.API_URL = 'http://localhost:8000/api';
    this.TOKEN_KEY = 'access_token';
    this.REFRESH_TOKEN_KEY = 'refresh_token';
  }

  /**
   * Connexion
   */
  async login(email, password) {
    const response = await fetch(`${this.API_URL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    const data = await response.json();

    if (data.success) {
      // Sauvegarder les tokens
      localStorage.setItem(this.TOKEN_KEY, data.access_token);
      localStorage.setItem(this.REFRESH_TOKEN_KEY, data.refresh_token);
      return data.user;
    }

    throw new Error(data.message);
  }

  /**
   * Déconnexion (révoque les tokens)
   */
  async logout() {
    const accessToken = this.getToken();
    const refreshToken = this.getRefreshToken();

    try {
      await fetch(`${this.API_URL}/auth/logout`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${accessToken}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          refresh_token: refreshToken
        })
      });
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error);
    } finally {
      // Supprimer les tokens localement même en cas d'erreur
      this.removeToken();
      this.removeRefreshToken();
      window.location.href = '/login';
    }
  }

  /**
   * Rafraîchir le token automatiquement
   */
  async refreshToken() {
    const refreshToken = this.getRefreshToken();

    if (!refreshToken) {
      throw new Error('Pas de refresh token disponible');
    }

    const response = await fetch(`${this.API_URL}/auth/refresh`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: refreshToken })
    });

    const data = await response.json();

    if (data.success) {
      // Sauvegarder les nouveaux tokens
      localStorage.setItem(this.TOKEN_KEY, data.access_token);
      localStorage.setItem(this.REFRESH_TOKEN_KEY, data.refresh_token);
      return data.access_token;
    }

    // Si le refresh échoue, déconnecter
    this.logout();
    throw new Error('Session expirée, veuillez vous reconnecter');
  }

  /**
   * Intercepteur fetch avec refresh automatique
   */
  async authenticatedFetch(url, options = {}) {
    const token = this.getToken();

    if (!token) {
      throw new Error('Non authentifié');
    }

    // Première tentative
    let response = await fetch(url, {
      ...options,
      headers: {
        ...options.headers,
        'Authorization': `Bearer ${token}`
      }
    });

    // Si 401, rafraîchir le token et réessayer
    if (response.status === 401) {
      try {
        const newToken = await this.refreshToken();
        
        // Réessayer avec le nouveau token
        response = await fetch(url, {
          ...options,
          headers: {
            ...options.headers,
            'Authorization': `Bearer ${newToken}`
          }
        });
      } catch (error) {
        // Rediriger vers login si le refresh échoue
        window.location.href = '/login';
        throw error;
      }
    }

    return response;
  }

  // Helpers
  getToken() {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  getRefreshToken() {
    return localStorage.getItem(this.REFRESH_TOKEN_KEY);
  }

  removeToken() {
    localStorage.removeItem(this.TOKEN_KEY);
  }

  removeRefreshToken() {
    localStorage.removeItem(this.REFRESH_TOKEN_KEY);
  }

  isAuthenticated() {
    return !!this.getToken();
  }
}

export default new AuthService();
```

---

## 📱 Utilisation dans React

### Composant de Déconnexion

```jsx
import React from 'react';
import AuthService from '../services/auth.service';

function LogoutButton() {
  const handleLogout = async () => {
    try {
      await AuthService.logout();
      // Redirection automatique vers /login
    } catch (error) {
      console.error('Erreur déconnexion:', error);
      // Supprimer tokens localement même en cas d'erreur
      AuthService.removeToken();
      AuthService.removeRefreshToken();
      window.location.href = '/login';
    }
  };

  return (
    <button onClick={handleLogout} className="btn btn-danger">
      Se déconnecter
    </button>
  );
}

export default LogoutButton;
```

### Hook React avec Auto-Refresh

```jsx
import { useEffect } from 'react';
import AuthService from '../services/auth.service';

function useAutoRefreshToken() {
  useEffect(() => {
    // Rafraîchir le token 5 minutes avant expiration
    const interval = setInterval(async () => {
      try {
        await AuthService.refreshToken();
        console.log('Token rafraîchi automatiquement');
      } catch (error) {
        console.error('Erreur refresh auto:', error);
        // Redirection automatique vers login
      }
    }, 55 * 60 * 1000); // 55 minutes (si expires_in = 3600s)

    return () => clearInterval(interval);
  }, []);
}

export default useAutoRefreshToken;
```

---

## 🔍 Vérifier les Logs

### Après Déconnexion

```bash
tail -f storage/logs/laravel.log
```

**Logs attendus :**
```
[INFO] Token révoqué dans Authentik {"token_preview":"eyJhbGc..."}
[INFO] Déconnexion utilisateur réussie {"ip":"127.0.0.1","access_token_revoked":true,"refresh_token_revoked":true}
```

---

## 📊 Flux de Déconnexion Complet

```
┌─────────────┐
│  Frontend   │
└─────────────┘
      │
      │ 1. Clic "Se déconnecter"
      │
      ↓
┌─────────────────────────────────────┐
│  POST /api/auth/logout              │
│  Authorization: Bearer {token}      │
│  Body: { "refresh_token": "..." }  │
└─────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────┐
│  Laravel API (AuthController)       │
│                                     │
│  1. Extrait access_token du header │
│  2. Extrait refresh_token du body  │
└─────────────────────────────────────┘
      │
      ↓
┌─────────────────────────────────────┐
│  AuthentikService::logout()         │
│                                     │
│  1. POST /application/o/revoke/     │
│     → Révoque access_token          │
│                                     │
│  2. POST /application/o/revoke/     │
│     → Révoque refresh_token         │
└─────────────────────────────────────┘
      │
      ↓
┌─────────────┐
│  Authentik  │
│             │
│  ✅ Tokens  │
│  révoqués   │
└─────────────┘
      │
      ↓
┌─────────────────────────────────────┐
│  Réponse à l'utilisateur            │
│  { "success": true }                │
└─────────────────────────────────────┘
      │
      ↓
┌─────────────┐
│  Frontend   │
│             │
│  Supprime   │
│  tokens     │
│  localStorage │
│             │
│  Redirect   │
│  → /login   │
└─────────────┘
```

---

## 🧪 Test Complet

### 1. Connexion

```bash
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "Password123!"
}
```

**Sauvegarder la réponse :**
```json
{
  "access_token": "eyJhbGc...",     ← Copier
  "refresh_token": "eyJhbGc..."     ← Copier
}
```

---

### 2. Tester un Endpoint Protégé

```bash
GET http://localhost:8000/api/auth/me
Authorization: Bearer eyJhbGc...
```

**Résultat :** ✅ Retourne vos infos utilisateur

---

### 3. Déconnexion

```bash
POST http://localhost:8000/api/auth/logout
Authorization: Bearer eyJhbGc...
Content-Type: application/json

{
  "refresh_token": "eyJhbGc..."
}
```

**Résultat :** ✅ Déconnexion réussie

---

### 4. Vérifier que le Token est Révoqué

```bash
GET http://localhost:8000/api/auth/me
Authorization: Bearer eyJhbGc...    (même token qu'avant)
```

**Résultat attendu :** 
```json
{
  "success": false,
  "message": "Non authentifié. Veuillez vous connecter."
}
```

✅ **Le token a bien été révoqué !**

---

## 🔄 Rafraîchissement de Token

### Quand Rafraîchir ?

Le token expire après `expires_in` secondes (généralement 3600s = 1h).

### Endpoint

```
POST /api/auth/refresh
```

### Utilisation

```bash
POST http://localhost:8000/api/auth/refresh
Content-Type: application/json

{
  "refresh_token": "eyJhbGc..."
}
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Token rafraîchi avec succès.",
  "access_token": "eyJhbGc...",      ← Nouveau token
  "refresh_token": "eyJhbGc...",     ← Nouveau refresh token
  "token_type": "Bearer",
  "expires_in": 3600
}
```

---

## 💡 Stratégies de Rafraîchissement

### Stratégie 1 : Rafraîchissement Automatique

Rafraîchir le token **avant** qu'il expire :

```javascript
// Rafraîchir toutes les 55 minutes (pour token de 1h)
setInterval(async () => {
  try {
    await AuthService.refreshToken();
  } catch (error) {
    // Rediriger vers login
    window.location.href = '/login';
  }
}, 55 * 60 * 1000);
```

### Stratégie 2 : Rafraîchissement sur Erreur 401

Intercepter les 401 et rafraîchir automatiquement :

```javascript
async function apiCall(url, options) {
  let response = await fetch(url, options);

  // Si 401, rafraîchir et réessayer
  if (response.status === 401) {
    await AuthService.refreshToken();
    // Réessayer avec le nouveau token
    response = await fetch(url, options);
  }

  return response;
}
```

---

## 🔐 Sécurité

### ✅ Avantages

1. **Révocation Réelle** : Les tokens sont révoqués côté serveur Authentik
2. **Sécurité Renforcée** : Impossible d'utiliser un token révoqué
3. **Conformité OAuth2** : Suit les standards OAuth2
4. **Audit** : Tous les événements loggés

### 🛡️ Meilleures Pratiques

```javascript
// 1. Toujours révoquer les deux tokens
logout({
  refresh_token: refreshToken
});

// 2. Nettoyer le localStorage
localStorage.removeItem('access_token');
localStorage.removeItem('refresh_token');

// 3. Rediriger vers login
window.location.href = '/login';

// 4. Ne jamais stocker de données sensibles dans localStorage
```

---

## 📋 Erreurs Possibles

### Erreur : "Token d'authentification manquant"

**Cause :** Header Authorization absent

**Solution :**
```javascript
headers: {
  'Authorization': `Bearer ${token}` // ← Ne pas oublier !
}
```

---

### Erreur : Token déjà révoqué

**Réponse :**
```json
{
  "success": true,
  "message": "Déconnexion effectuée. Le token a peut-être déjà expiré."
}
```

**Explication :** Le token était déjà révoqué ou expiré. Pas grave, l'utilisateur est déconnecté.

---

## 🎯 Résumé

**Fonctionnalités Ajoutées :**
- ✅ **Logout** : Révoque access_token et refresh_token dans Authentik
- ✅ **Refresh** : Renouvelle le token avant expiration
- ✅ **Logging** : Tous les événements sont loggés
- ✅ **Sécurité** : Tokens révoqués côté serveur

**Routes Ajoutées :**
- ✅ `POST /api/auth/logout` - Déconnexion
- ✅ `POST /api/auth/refresh` - Rafraîchir token

**Fichiers Modifiés :**
- ✅ `app/Services/AuthentikService.php` - Ajout méthodes logout et refresh
- ✅ `app/Http/Controllers/Api/AuthController.php` - Méthodes logout et refreshToken
- ✅ `routes/api.php` - Route refresh ajoutée

---

## 🧪 JSON pour Tests Postman

### 1. Déconnexion Simple

```json
POST http://localhost:8000/api/auth/logout
Authorization: Bearer eyJhbGc...

(Pas de body)
```

### 2. Déconnexion Complète

```json
POST http://localhost:8000/api/auth/logout
Authorization: Bearer eyJhbGc...

{
  "refresh_token": "eyJhbGc..."
}
```

### 3. Rafraîchissement

```json
POST http://localhost:8000/api/auth/refresh

{
  "refresh_token": "eyJhbGc..."
}
```

---

**Votre système de déconnexion est maintenant complet et sécurisé ! 🎉**

**Testez maintenant :**
1. Connectez-vous
2. Copiez le access_token et refresh_token
3. Déconnectez-vous
4. Vérifiez que le token ne fonctionne plus ! 🔒
