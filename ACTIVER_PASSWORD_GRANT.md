# ⚡ Guide Visuel : Activer Password Grant Flow

## 🎯 Objectif

Activer le **Resource Owner Password Credentials** dans Authentik pour permettre la connexion avec email/password.

---

## 📋 Étapes Détaillées

### 🔹 Étape 1 : Connexion à Authentik

```
1. Ouvrez votre navigateur
2. Allez sur : http://5.189.156.115:31015
3. Connectez-vous avec akadmin
```

---

### 🔹 Étape 2 : Admin Interface

```
┌────────────────────────────────┐
│  Cliquez sur votre nom         │
│  (en haut à droite)            │
│         │                      │
│         ↓                      │
│  📋 Admin Interface            │
└────────────────────────────────┘
```

---

### 🔹 Étape 3 : Naviguer vers Providers

```
Menu de gauche :

📁 Applications
   ├─ Applications
   └─ 📌 Providers  ← CLIQUEZ ICI
```

---

### 🔹 Étape 4 : Votre Provider OAuth

Vous devriez voir une liste de Providers.

**Option A : Provider Existe Déjà**
```
Liste des Providers:
├─ Auto-Ecole-API-Provider  ← CLIQUEZ sur Edit (✏️)
├─ Autre Provider...
└─ ...
```

**Option B : Aucun Provider**
```
Cliquez sur le bouton "Create" en haut à droite
```

---

### 🔹 Étape 5 : Configuration du Provider ⭐

**Formulaire à remplir :**

#### Section 1 : Informations de Base

```
Name: Auto-Ecole-API-Provider
Type: OAuth2/OpenID Provider
```

#### Section 2 : Protocol Settings

```
┌─────────────────────────────────────────┐
│ Authorization flow:                     │
│                                         │
│ ☑ Authorization Code                   │
│                                         │
│ ☑ Resource Owner Password Credentials  │ ⭐⭐⭐
│   (COCHEZ CETTE CASE !)                │
│                                         │
│ ☐ Implicit Flow (optionnel)            │
│                                         │
│ ☐ Device Code (optionnel)               │
└─────────────────────────────────────────┘
```

**⚠️ TRÈS IMPORTANT :**
La case **"Resource Owner Password Credentials"** DOIT être **COCHÉE** ! ☑

#### Section 3 : Client Settings

```
Client type: Confidential

Client ID: (affiché automatiquement)
→ COPIEZ-LE : JpMm7W7oeisa2EWDsfxyX0xNoF9SEYlOnKDfGxu2

Client Secret: (cliquez sur "Show" pour voir)
→ COPIEZ-LE : pbkdf2_sha256$xxxxx...
```

#### Section 4 : Redirect URIs

```
┌─────────────────────────────────────────┐
│ Redirect URIs:                          │
│                                         │
│ http://localhost:8000/api/auth/authentik/callback
│                                         │
│ (Ajoutez cette URL)                    │
└─────────────────────────────────────────┘
```

#### Section 5 : Signing Key

```
Signing Key: (sélectionnez une clé dans la liste)

Si aucune clé n'existe :
1. Applications → Certificates
2. Create → Generate
3. Name: authentik-signing-key
4. Save
5. Retournez dans le Provider et sélectionnez cette clé
```

#### Section 6 : Advanced Protocol Settings

```
┌─────────────────────────────────────────┐
│ Access code validity:                   │
│   minutes = 1                           │
│                                         │
│ Access token validity:                  │
│   hours = 1                             │
│                                         │
│ Refresh token validity:                 │
│   days = 30                             │
│                                         │
│ Scopes:                                 │
│   openid email profile                  │
└─────────────────────────────────────────┘
```

---

### 🔹 Étape 6 : Sauvegarder

**Cliquez sur le bouton "Save" en bas de la page**

---

### 🔹 Étape 7 : Créer/Lier une Application (Optionnel mais Recommandé)

```
1. Applications → Applications
2. Create
3. Remplir :
   - Name: Auto-Ecole-App
   - Slug: auto-ecole-app
   - Provider: Sélectionnez le Provider créé ci-dessus
4. Save
```

---

### 🔹 Étape 8 : Mettre à Jour .env

**Fichier `.env` :**

```env
AUTHENTIK_BASE_URL=http://5.189.156.115:31015
AUTHENTIK_CLIENT_ID=JpMm7W7oeisa2EWDsfxyX0xNoF9SEYlOnKDfGxu2
AUTHENTIK_CLIENT_SECRET=pbkdf2_sha256$720000$xxx...
AUTHENTIK_REDIRECT_URI=http://localhost:8000/api/auth/authentik/callback
AUTHENTIK_API_TOKEN=NYKgxb4g6i...
```

**Vider le cache :**
```bash
php artisan config:clear
```

---

## 🧪 Test Final

### Test A : Script de Vérification

```bash
php test_login_direct.php mokoko3@gmail.com VotreVraiMotDePasse
```

**Résultat attendu :**
```
✅ LA CONNEXION FONCTIONNE !
   Access Token: eyJhbGc...
   Expires In: 3600s
```

---

### Test B : Via l'API

**Inscription :**
```json
POST http://localhost:8000/api/auth/register

{
  "email": "verification@test.com",
  "password": "Verify123!",
  "password_confirmation": "Verify123!",
  "nom": "Verification",
  "prenom": "Test",
  "contact": "0600000000",
  "role": "candidat"
}
```

**Connexion :**
```json
POST http://localhost:8000/api/auth/login

{
  "email": "verification@test.com",
  "password": "Verify123!"
}
```

**✅ Devrait retourner les tokens !**

---

## 🔍 Screenshot des Paramètres Corrects

### Provider OAuth - Vue d'Ensemble

```
┌────────────────────────────────────────────────┐
│ Auto-Ecole-API-Provider                        │
├────────────────────────────────────────────────┤
│                                                │
│ Type: OAuth2/OpenID Provider                   │
│                                                │
│ 📋 Protocol Settings                           │
│                                                │
│ Authorization flow:                            │
│   ☑ Authorization Code                        │
│   ☑ Resource Owner Password Credentials  ⭐   │
│                                                │
│ Client Settings:                               │
│   Client type: Confidential                    │
│   Client ID: JpMm7W7oeisa2EWDsfxyX0x...       │
│   Client Secret: ****************             │
│                                                │
│ Redirect URIs:                                 │
│   http://localhost:8000/api/auth/.../callback │
│                                                │
│                          [Save]                │
└────────────────────────────────────────────────┘
```

---

## 📝 Vérification Post-Configuration

### Checklist

- [ ] Provider OAuth créé/édité
- [ ] ☑ "Resource Owner Password Credentials" **COCHÉ**
- [ ] Client ID copié dans .env
- [ ] Client Secret copié dans .env
- [ ] Redirect URI ajouté
- [ ] Signing Key sélectionné
- [ ] Configuration sauvegardée
- [ ] Cache Laravel vidé (`php artisan config:clear`)
- [ ] Test script → ✅ Connexion fonctionne
- [ ] Test Postman `/login` → ✅ Tokens reçus

---

## 🚨 Si Ça Ne Fonctionne Toujours Pas

### Debug Niveau 1

```bash
php test_login_direct.php votre-email@test.com VotreMotDePasse
```

Analysez l'erreur affichée.

### Debug Niveau 2

```bash
# Vérifier la config
php artisan config:show services.authentik

# Devrait afficher :
client_id => "JpMm7W7oe..."
client_secret => "pbkdf2..."
base_url => "http://5.189.156.115:31015"
```

### Debug Niveau 3

Testez **directement** sur Authentik :
```
1. Allez sur http://5.189.156.115:31015
2. Essayez de vous connecter avec l'email et mot de passe
3. Si ça fonctionne → Le problème est la config OAuth
4. Si ça échoue → Le mot de passe est incorrect
```

---

## 💡 Solution Alternative (Si Password Grant Pose Problème)

Si vous n'arrivez vraiment pas à activer le Password Grant, vous pouvez utiliser **uniquement le flux OAuth** :

```javascript
// Frontend
const response = await fetch('/api/auth/authentik/redirect');
const { auth_url } = await response.json();

// Rediriger l'utilisateur
window.location.href = auth_url;

// Après login, callback automatique avec tokens
```

Mais **Password Grant est recommandé** pour une meilleure UX !

---

## 🎯 Résumé

**Problème :** Password Grant Flow non activé  
**Solution :** Cocher "Resource Owner Password Credentials" dans le Provider  
**Vérification :** `php test_login_direct.php`  
**Test :** Créer compte via `/register` puis `/login`  

**Cette case doit être cochée : ☑ Resource Owner Password Credentials** ⭐

---

**Suivez ce guide étape par étape et ça devrait fonctionner ! 🚀**

