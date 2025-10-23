# 🔧 Correction du Password Grant Flow Authentik

## ❌ Problème Actuel

```json
{
  "success": false,
  "message": "Identifiants incorrects. Veuillez vérifier votre email et mot de passe."
}
```

**Erreur Authentik sous-jacente :**
```
"error": "invalid_grant"
```

---

## 🎯 Cause Principale

Le **Password Grant Flow** n'est **PAS correctement configuré** dans votre Provider OAuth Authentik.

---

## ✅ Solution Complète en 6 Étapes

### Étape 1 : Accéder à Authentik

```
URL: http://5.189.156.115:31015
Username: akadmin (votre admin)
Password: Votre mot de passe
```

---

### Étape 2 : Aller dans Admin Interface

1. Une fois connecté, **cliquez sur votre nom** en haut à droite
2. Sélectionnez **"Admin Interface"**

---

### Étape 3 : Éditer/Créer le Provider OAuth

1. **Menu gauche** → **Applications** → **Providers**

2. **Cherchez votre provider** ou **créez-en un nouveau**

3. **Cliquez sur "Edit"** (ou "Create")

---

### Étape 4 : Configuration du Provider ⭐ IMPORTANT

**Formulaire de configuration :**

```
┌─────────────────────────────────────────────────────┐
│ Name: Auto-Ecole-API-Provider                       │
│                                                     │
│ Type: OAuth2/OpenID Provider                        │
│                                                     │
│ ============= SECTION IMPORTANTE =============      │
│                                                     │
│ Authorization flow:                                 │
│   ☑ Authorization Code              ← Cocher       │
│   ☑ Implicit Flow                   ← Optionnel    │
│   ☑ Resource Owner Password         ← ⭐ OBLIGATOIRE
│       Credentials                                   │
│                                                     │
│ ============================================        │
│                                                     │
│ Client type: Confidential                           │
│                                                     │
│ Client ID: (généré automatiquement)                 │
│ Client Secret: (généré automatiquement)             │
│                                                     │
│ Redirect URIs:                                      │
│   http://localhost:8000/api/auth/authentik/callback│
│                                                     │
│ Signing Key: (sélectionner une clé)                │
│                                                     │
│ ============= Advanced Settings =============       │
│                                                     │
│ Access code validity: minutes=1                     │
│ Access token validity: hours=1                      │
│ Refresh token validity: days=30                     │
│                                                     │
│ Scopes: openid email profile                       │
│                                                     │
└─────────────────────────────────────────────────────┘
```

**🎯 LA CLÉ : Cocher "Resource Owner Password Credentials" !**

---

### Étape 5 : Récupérer les Credentials

Après avoir sauvegardé le Provider :

1. **Cliquez sur le Provider** que vous venez de créer/modifier
2. **Notez :**
   - **Client ID** : `JpMm7W7oeisa2EWDsfxyX0xNoF9SEYlOnKDfGxu2` (exemple)
   - **Client Secret** : Cliquez sur "Show" pour voir

3. **Copiez ces valeurs**

---

### Étape 6 : Mettre à Jour .env

Éditez votre fichier `.env` :

```env
# Authentik OAuth Configuration
AUTHENTIK_BASE_URL=http://5.189.156.115:31015
AUTHENTIK_CLIENT_ID=JpMm7W7oeisa2EWDsfxyX0xNoF9SEYlOnKDfGxu2
AUTHENTIK_CLIENT_SECRET=votre_client_secret_complet_ici
AUTHENTIK_REDIRECT_URI=http://localhost:8000/api/auth/authentik/callback
AUTHENTIK_API_TOKEN=NYKgxb4g6i...votre_token_api
```

**Puis :**

```bash
php artisan config:clear
```

---

## 🧪 Test Immédiat

### Test 1 : Vérifier la Configuration

```bash
php test_login_direct.php mokoko3@gmail.com VotreVraiMotDePasse
```

**Si ça affiche "unsupported_grant_type" :**
→ Le Password Grant n'est PAS coché

**Si ça affiche "invalid_client" :**
→ Client ID ou Secret incorrect

**Si ça affiche "invalid_grant" :**
→ Mot de passe incorrect ou utilisateur inactif

**Si ça affiche "✅ LA CONNEXION FONCTIONNE !" :**
→ Tout est OK ! 🎉

---

### Test 2 : Inscription Nouveau Compte

**Postman :**
```json
POST http://localhost:8000/api/auth/register

{
  "email": "test.final@gmail.com",
  "password": "TestFinal123!",
  "password_confirmation": "TestFinal123!",
  "nom": "Final",
  "prenom": "Test",
  "contact": "0600000000",
  "role": "candidat"
}
```

**Devrait retourner (201) :**
```json
{
  "success": true,
  "access_token": "eyJhbGc...",
  "refresh_token": "eyJhbGc..."
}
```

---

### Test 3 : Connexion

**Postman :**
```json
POST http://localhost:8000/api/auth/login

{
  "email": "test.final@gmail.com",
  "password": "TestFinal123!"
}
```

**Devrait retourner (200) :**
```json
{
  "success": true,
  "access_token": "eyJhbGc...",
  "refresh_token": "eyJhbGc..."
}
```

---

## 🔍 Diagnostic Visuel

### Dans Authentik → Applications → Providers

Vérifiez votre Provider :

```
Provider Details
├─ Name: Auto-Ecole-API-Provider
├─ Type: OAuth2/OpenID Provider
├─ Client Type: Confidential
│
└─ Authorization flow:
   ☑ Authorization Code                      ← OK
   ☑ Resource Owner Password Credentials     ← ⭐ DOIT ÊTRE COCHÉ !
```

**Si "Resource Owner Password Credentials" n'est PAS coché :**
→ C'est le problème ! Cochez-le et sauvegardez.

---

## 🚨 Erreurs Courantes

### Erreur 1 : Password Grant Non Activé

**Symptôme :**
```json
{"error": "unsupported_grant_type"}
```

**Solution :**
- Éditez le Provider
- Cochez "Resource Owner Password Credentials"
- Sauvegardez

---

### Erreur 2 : Client ID/Secret Incorrects

**Symptôme :**
```json
{"error": "invalid_client"}
```

**Solution :**
- Vérifiez AUTHENTIK_CLIENT_ID dans .env
- Vérifiez AUTHENTIK_CLIENT_SECRET dans .env
- Comparez avec Authentik → Providers → Votre Provider

---

### Erreur 3 : Mot de Passe Incorrect

**Symptôme :**
```json
{"error": "invalid_grant"}
```

**Solution :**
- L'utilisateur existe mais le mot de passe ne correspond pas
- Réinitialisez le mot de passe dans Authentik
- Ou créez un nouveau compte via `/register`

---

## 📸 Capture d'Écran de la Config (Guide Visuel)

### Où Trouver "Resource Owner Password Credentials"

```
Authentik Admin Interface
  │
  └─ Applications
      │
      └─ Providers
          │
          └─ [Votre Provider]
              │
              └─ Edit
                  │
                  ├─ Name: Auto-Ecole-API-Provider
                  ├─ Type: OAuth2/OpenID Provider
                  │
                  ├─ Protocol settings
                  │   │
                  │   └─ Authorization flow
                  │       ├─ ☑ Authorization Code
                  │       ├─ ☐ Implicit Flow
                  │       ├─ ☑ Resource Owner Password Credentials  ⭐
                  │       └─ ☐ Device Code
                  │
                  ├─ Client Settings
                  │   ├─ Client type: Confidential
                  │   ├─ Client ID: xxxxx
                  │   └─ Client Secret: xxxxx
                  │
                  └─ [Save]
```

---

## 🎯 Checklist de Vérification

### Dans Authentik

- [ ] Provider OAuth créé
- [ ] "Resource Owner Password Credentials" **COCHÉ** ⭐
- [ ] Client Type = "Confidential"
- [ ] Client ID copié dans .env
- [ ] Client Secret copié dans .env
- [ ] Token API créé et configuré

### Dans Laravel

- [ ] .env mis à jour avec toutes les variables
- [ ] `php artisan config:clear` exécuté
- [ ] Test `php verify_authentik_token.php` → Token valide ✅

### Test de Connexion

- [ ] Créer nouveau compte via `/register`
- [ ] Connexion via `/login` → Devrait fonctionner ✅

---

## 🔄 Si le Problème Persiste

### Option 1 : Recréer le Provider

1. **Supprimez** l'ancien provider
2. **Créez-en un nouveau** avec Password Grant activé
3. **Récupérez** les nouveaux Client ID et Secret
4. **Mettez à jour** .env
5. **Testez**

### Option 2 : Vérifier les Logs

```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Cherchez :
[ERROR] Erreur rafraîchissement token
```

Cela vous donnera la **vraie erreur** d'Authentik.

---

## 📝 Exemple de Configuration .env Complète

```env
# Application
APP_NAME="Auto École API"
APP_ENV=local
APP_KEY=base64:xxx...
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Session
SESSION_DRIVER=cookie

# Authentik OAuth/OpenID Connect
AUTHENTIK_BASE_URL=http://5.189.156.115:31015
AUTHENTIK_CLIENT_ID=JpMm7W7oeisa2EWDsfxyX0xNoF9SEYlOnKDfGxu2
AUTHENTIK_CLIENT_SECRET=pbkdf2_sha256$xxxxx...votre_secret
AUTHENTIK_REDIRECT_URI=http://localhost:8000/api/auth/authentik/callback
AUTHENTIK_API_TOKEN=NYKgxb4g6i...votre_token
```

---

## ✅ Solution Garantie

**Pour être SÛR que ça fonctionne :**

1. **Supprimez** les anciens utilisateurs de test (mokoko1, mokoko2, mokoko3)

2. **Créez UN utilisateur** via l'API :
```bash
POST /api/auth/register avec le JSON complet (avec role)
```

3. **Vérifiez dans Authentik** que l'utilisateur est créé

4. **Connectez-vous** avec le MÊME email et mot de passe

**Si ça ne fonctionne toujours pas :**
→ Le Password Grant n'est PAS activé dans le Provider

---

**Prochaine étape : Vérifiez que "Resource Owner Password Credentials" est COCHÉ dans votre Provider ! ⭐**

