# 🔐 Configuration Authentik comme IAM Unique

## ✅ Architecture Implémentée

Authentik est maintenant votre **Identity and Access Management (IAM) unique** :

```
┌─────────────────────────────────────────────────────────┐
│                    VOTRE API LARAVEL                    │
│                                                         │
│  Inscription (/register)                                │
│    1. Crée utilisateur dans Authentik                   │
│    2. Synchronise dans DB Laravel                       │
│    3. Retourne token Authentik                          │
│                                                         │
│  Connexion (/login)                                     │
│    1. Authentification via Authentik                    │
│    2. Synchronise/récupère utilisateur de la DB        │
│    3. Retourne token Authentik                          │
│                                                         │
│  ✅ Mots de passe stockés UNIQUEMENT dans Authentik    │
│  ✅ Token d'accès généré par Authentik                 │
│  ✅ DB Laravel = données métier seulement               │
│                                                         │
└─────────────────────────────────────────────────────────┘
                         │
                         │ API REST
                         ↓
              ┌──────────────────┐
              │    AUTHENTIK     │
              │  (IAM Central)   │
              │                  │
              │  - Utilisateurs  │
              │  - Mots de passe │
              │  - Tokens OAuth  │
              │  - Authentification │
              └──────────────────┘
```

---

## 📋 Configuration Requise dans Authentik

### 1️⃣ Créer un Token API

Le token API permet à votre application de gérer les utilisateurs dans Authentik.

**Étapes :**

1. Connectez-vous à votre instance Authentik en tant qu'admin
2. Allez dans **Admin Interface** → **Tokens and App passwords**
3. Cliquez sur **Create**
4. Configurez :
   - **Identifier** : `laravel-api-token`
   - **User** : Votre compte admin
   - **Description** : `Token pour Laravel API - Gestion utilisateurs`
   - **Expires** : Jamais (ou date lointaine)
   - **Intent** : `api`
5. Cliquez sur **Create**
6. **Copiez le token affiché** (vous ne pourrez plus le revoir !)

**Exemple de token :**
```
ak-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

### 2️⃣ Créer une Application OAuth (Provider)

Pour permettre la connexion avec mot de passe (Resource Owner Password Flow).

**Étapes :**

1. Allez dans **Applications** → **Providers**
2. Cliquez sur **Create** → **OAuth2/OpenID Provider**
3. Configurez :
   - **Name** : `Auto École API`
   - **Authorization flow** : Cochez **Resource Owner Password Credentials**
   - **Client type** : `Confidential`
   - **Client ID** : Généré automatiquement (notez-le)
   - **Client Secret** : Généré automatiquement (notez-le)
   - **Redirect URIs** : `http://localhost:8000/api/auth/authentik/callback`
   - **Signing Key** : Sélectionnez une clé

4. **Advanced protocol settings** :
   - **Access token validity** : `hours=1` (ou votre préférence)
   - **Refresh token validity** : `days=30`

5. Cliquez sur **Create**

---

### 3️⃣ Créer l'Application

1. Allez dans **Applications** → **Applications**
2. Cliquez sur **Create**
3. Configurez :
   - **Name** : `Auto École Application`
   - **Slug** : `auto-ecole-app`
   - **Provider** : Sélectionnez le provider créé ci-dessus
   - **Launch URL** : `http://localhost:8000`

4. Cliquez sur **Create**

---

## ⚙️ Configuration Laravel (.env)

Ajoutez ces lignes dans votre fichier `.env` :

```env
# Authentik Configuration
AUTHENTIK_BASE_URL=https://your-authentik-instance.com
AUTHENTIK_CLIENT_ID=votre_client_id_ici
AUTHENTIK_CLIENT_SECRET=votre_client_secret_ici
AUTHENTIK_REDIRECT_URI=http://localhost:8000/api/auth/authentik/callback
AUTHENTIK_API_TOKEN=ak-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 📝 Où trouver ces valeurs ?

| Variable | Où la trouver |
|----------|---------------|
| `AUTHENTIK_BASE_URL` | URL de votre instance Authentik (ex: `https://auth.votredomaine.com`) |
| `AUTHENTIK_CLIENT_ID` | Applications → Providers → Votre provider → Client ID |
| `AUTHENTIK_CLIENT_SECRET` | Applications → Providers → Votre provider → Client Secret |
| `AUTHENTIK_REDIRECT_URI` | L'URL de callback de votre API |
| `AUTHENTIK_API_TOKEN` | Tokens and App passwords → Votre token créé |

---

## 🧪 Tester la Configuration

### 1. Vérifier la connexion à Authentik

```bash
php artisan tinker
```

```php
$service = app(\App\Services\AuthentikService::class);

// Test 1: Vérifier si un utilisateur existe
$exists = $service->userExists('test@example.com');
// false si l'utilisateur n'existe pas

// Test 2: Créer un utilisateur test
$user = $service->createUser([
    'email' => 'test@example.com',
    'password' => 'Password123!',
    'nom' => 'Test',
    'prenom' => 'User',
    'contact' => '0600000000',
    'role' => 'candidat',
]);

// Test 3: Authentifier l'utilisateur
$auth = $service->authenticateUser('test@example.com', 'Password123!');
print_r($auth['tokens']);
```

---

### 2. Tester l'Inscription via API

**Postman / curl :**

```bash
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "email": "nouveau@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Dupont",
  "prenom": "Jean",
  "contact": "0612345678",
  "adresse": "123 Rue de Paris"
}
```

**Résultat attendu :**
- ✅ Utilisateur créé dans Authentik
- ✅ Utilisateur créé dans votre DB
- ✅ Token Authentik retourné

**Vérification dans Authentik :**
1. Directory → Users
2. Vous devriez voir `nouveau@example.com` !

---

### 3. Tester la Connexion via API

```bash
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "nouveau@example.com",
  "password": "Password123!"
}
```

**Résultat attendu :**
- ✅ Authentification via Authentik
- ✅ Token Authentik retourné
- ✅ Utilisateur synchronisé dans DB si nécessaire

---

## 🔄 Flux Complet

### Inscription

```
Frontend
   │
   │ POST /register
   │ { email, password, nom, prenom, ... }
   │
   ↓
API Laravel
   │
   ├─1─> Vérifier email unique dans Authentik
   │
   ├─2─> Créer utilisateur dans Authentik
   │     (stockage du mot de passe)
   │
   ├─3─> Créer utilisateur dans DB Laravel
   │     (pas de mot de passe stocké)
   │
   ├─4─> Authentifier via Authentik
   │
   ↓
Authentik
   │
   │ Génère access_token & refresh_token
   │
   ↓
Frontend
   │
   │ Reçoit tokens Authentik
   │ { access_token, refresh_token, expires_in }
```

### Connexion

```
Frontend
   │
   │ POST /login
   │ { email, password }
   │
   ↓
API Laravel
   │
   ├─1─> Authentifier via Authentik
   │
   ↓
Authentik
   │
   │ Vérifie email + password
   │ Génère tokens
   │
   ↓
API Laravel
   │
   ├─2─> Synchroniser utilisateur dans DB
   │
   ├─3─> Retourner tokens
   │
   ↓
Frontend
   │
   │ Reçoit tokens Authentik
   │ { access_token, refresh_token }
```

---

## 🔐 Sécurité

### ✅ Avantages de cette Architecture

1. **Mot de passe unique** : Stocké UNIQUEMENT dans Authentik (chiffré)
2. **Token OAuth standard** : JWT validés par Authentik
3. **Révocation centralisée** : Révoquer l'accès depuis Authentik
4. **SSO possible** : Même compte pour plusieurs applications
5. **Audit centralisé** : Tous les logs d'auth dans Authentik

### 🔒 Données Stockées

| Donnée | Authentik | DB Laravel |
|--------|-----------|------------|
| Email | ✅ | ✅ |
| Mot de passe | ✅ Hashé | ❌ (random) |
| Nom/Prénom | ✅ | ✅ |
| Rôle | ✅ attributes | ✅ role |
| Token accès | ✅ Génère | ❌ |
| Données métier | ❌ | ✅ (dossiers, etc.) |

---

## 🚨 Dépannage

### Erreur : "Invalid client credentials"

**Cause** : Client ID ou Secret incorrect

**Solution** :
1. Vérifiez `AUTHENTIK_CLIENT_ID` dans `.env`
2. Vérifiez `AUTHENTIK_CLIENT_SECRET` dans `.env`
3. Comparez avec Authentik : Applications → Providers

---

### Erreur : "Unauthorized"

**Cause** : Token API invalide

**Solution** :
1. Vérifiez `AUTHENTIK_API_TOKEN` dans `.env`
2. Régénérez un token dans Authentik si nécessaire
3. Vérifiez que le token n'est pas expiré

---

### Erreur : "Connection refused"

**Cause** : URL Authentik incorrecte

**Solution** :
1. Vérifiez `AUTHENTIK_BASE_URL` dans `.env`
2. Testez l'URL dans un navigateur
3. Vérifiez que Authentik est accessible

---

### Utilisateur non trouvé après inscription

**Cause** : Délai de synchronisation

**Solution** :
1. Vérifiez les logs : `storage/logs/laravel.log`
2. Vérifiez dans Authentik : Directory → Users
3. Testez avec `AuthentikService::findUserByEmail()`

---

## 📊 Endpoints API Disponibles

| Endpoint | Méthode | Description | Authentification |
|----------|---------|-------------|------------------|
| `/api/auth/register` | POST | Inscription (crée dans Authentik) | Non |
| `/api/auth/login` | POST | Connexion (via Authentik) | Non |
| `/api/auth/logout` | POST | Déconnexion | Oui |
| `/api/auth/me` | GET | Profil utilisateur | Oui |
| `/api/auth/authentik/redirect` | GET | URL OAuth Authentik | Non |
| `/api/auth/authentik/callback` | GET | Callback OAuth | Non |

---

## 🎯 Token Authentik vs Token Local

### Avant (Token Local)

```json
{
  "access_token": "eyJ1c2VyX2lk...", // Token Laravel
  "token_type": "Bearer"
}
```

### Maintenant (Token Authentik)

```json
{
  "access_token": "eyJhbGciOiJSUzI1N...", // Token OAuth Authentik
  "refresh_token": "eyJhbGciOiJSUzI1N...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**Utilisation identique dans le frontend** :
```javascript
Authorization: Bearer eyJhbGciOiJSUzI1N...
```

---

## 📚 Documentation Complémentaire

- [Authentik API Docs](https://goauthentik.io/developer-docs/api/)
- [OAuth2 Password Grant](https://goauthentik.io/docs/providers/oauth2/)
- [Authentik Tokens](https://goauthentik.io/docs/user-interface/user/tokens)

---

## ✅ Checklist de Configuration

- [ ] Token API Authentik créé
- [ ] Provider OAuth créé avec Password Grant
- [ ] Application Authentik créée
- [ ] Variables `.env` configurées
- [ ] Test création utilisateur réussi
- [ ] Test authentification réussi
- [ ] Utilisateur visible dans Authentik Directory

---

**Date :** 22 Octobre 2025  
**Version Laravel :** 12.35.0  
**Authentik comme IAM :** ✅ Configuré

**Votre système utilise maintenant Authentik comme source unique d'authentification ! 🎉**

