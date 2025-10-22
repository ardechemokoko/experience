# 🚀 Démarrage Rapide - Authentik IAM

## ✅ Ce Qui a Changé

**Avant :** Mots de passe stockés dans votre DB  
**Maintenant :** Authentik est votre IAM unique

```
Inscription → Crée utilisateur dans Authentik → Token Authentik
Connexion → Authentifie via Authentik → Token Authentik
```

---

## ⚙️ Configuration en 3 Étapes

### 1️⃣ Créer un Token API dans Authentik

```
Authentik Admin → Tokens and App passwords → Create

- Identifier: laravel-api-token
- User: Admin
- Intent: api
→ Copier le token : ak-xxxxxxxx...
```

### 2️⃣ Créer Provider OAuth avec Password Grant

```
Authentik Admin → Applications → Providers → Create

- Name: Auto École API
- Type: OAuth2/OpenID
- Authorization flow: ✅ Resource Owner Password Credentials
- Client type: Confidential
→ Noter Client ID et Client Secret
```

### 3️⃣ Configurer .env

```env
AUTHENTIK_BASE_URL=https://your-authentik.com
AUTHENTIK_CLIENT_ID=votre_client_id
AUTHENTIK_CLIENT_SECRET=votre_client_secret
AUTHENTIK_REDIRECT_URI=http://localhost:8000/api/auth/authentik/callback
AUTHENTIK_API_TOKEN=ak-xxxxxxxxxxxxxxxxxxxxxxx
```

---

## 🧪 Test Rapide

### Inscription

```bash
POST http://localhost:8000/api/auth/register

{
  "email": "test@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Dupont",
  "prenom": "Jean",
  "contact": "0612345678"
}
```

**✅ Résultat :**
- Utilisateur créé dans Authentik
- Utilisateur dans votre DB
- Token Authentik retourné

**Vérification :**
```
Authentik → Directory → Users → test@example.com ✅
```

---

### Connexion

```bash
POST http://localhost:8000/api/auth/login

{
  "email": "test@example.com",
  "password": "Password123!"
}
```

**✅ Résultat :**
- Authentifié via Authentik
- Token Authentik retourné

---

## 📊 Nouveau Format de Réponse

```json
{
  "success": true,
  "message": "Connexion réussie. Bienvenue !",
  "user": {
    "id": "uuid",
    "email": "test@example.com",
    "role": "candidat"
  },
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**Nouveautés :**
- ✅ `refresh_token` pour renouveler l'accès
- ✅ `expires_in` durée de validité du token
- ✅ Token OAuth standard (JWT)

---

## 🔑 Utilisation du Token

**Même usage qu'avant dans le frontend :**

```javascript
fetch('http://localhost:8000/api/auth/me', {
  headers: {
    'Authorization': `Bearer ${access_token}`
  }
})
```

---

## 🎯 Architecture

```
┌────────────┐
│  Frontend  │
└────────────┘
      │
      │ register/login
      ↓
┌────────────┐
│ Laravel API│────────────┐
└────────────┘            │
      │                   │ Crée utilisateur
      │ Authentifie       │ Vérifie credentials
      ↓                   ↓
┌────────────────────────────┐
│        AUTHENTIK           │
│  (Source unique d'auth)    │
│                            │
│  - Stocke mots de passe    │
│  - Génère tokens OAuth     │
│  - Gère authentification   │
└────────────────────────────┘
```

---

## ✅ Avantages

1. **Sécurité** : Mots de passe seulement dans Authentik
2. **Standard** : Tokens OAuth2 JWT
3. **Centralisé** : Un IAM pour plusieurs apps
4. **Audit** : Logs centralisés
5. **SSO** : Possible dans le futur

---

## 📝 Checklist

- [ ] Token API créé dans Authentik
- [ ] Provider OAuth avec Password Grant
- [ ] Variables `.env` configurées
- [ ] `php artisan config:clear` exécuté
- [ ] Test inscription OK → Utilisateur dans Authentik
- [ ] Test connexion OK → Token reçu

---

## 🔧 Commandes Utiles

```bash
# Vider le cache
php artisan config:clear

# Tester la connexion Authentik
php artisan tinker
>>> app(\App\Services\AuthentikService::class)->userExists('test@test.com')

# Voir les logs
tail -f storage/logs/laravel.log
```

---

## 📚 Documentation Complète

- `AUTHENTIK_IAM_SETUP.md` - Configuration détaillée
- `VALIDATION_EXAMPLES.md` - Exemples de tests
- `AUTHENTIK_CONFIG.md` - Config OAuth

---

**Status :** ✅ Authentik comme IAM unique  
**Prêt à tester !** 🚀

