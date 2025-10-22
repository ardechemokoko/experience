# 🔑 Guide : Créer un Token API Authentik

## 🚨 Problème Actuel

```json
{
  "error": "Token invalid/expired"
}
```

**Cause :** Le token API dans votre `.env` n'est pas valide.

---

## ✅ Solution en 5 Minutes

### Étape 1 : Se Connecter à Authentik

Ouvrez votre navigateur :
```
http://5.189.156.115:31015
```

Connectez-vous avec :
- **Username** : `akadmin` (votre utilisateur admin par défaut)
- **Password** : Votre mot de passe admin

---

### Étape 2 : Accéder à l'Interface Admin

Une fois connecté :

1. **Cliquez sur votre nom** en haut à droite
2. Sélectionnez **"Admin Interface"** (interface d'administration)

Vous êtes maintenant dans le panneau d'administration.

---

### Étape 3 : Créer le Token API

1. **Menu de gauche** → **Directory** → **Tokens and App passwords**

2. **Cliquez sur le bouton "Create"** (en haut à droite)

3. **Remplissez le formulaire :**

```
┌─────────────────────────────────────────────────┐
│                                                 │
│ Identifier: laravel-api-token                   │
│ (Nom pour identifier ce token)                 │
│                                                 │
│ User: akadmin                                   │
│ (Sélectionnez votre utilisateur admin)         │
│                                                 │
│ Description: Token API pour Laravel             │
│ (Optionnel mais recommandé)                    │
│                                                 │
│ ☐ Expiring                                     │
│ (DÉCOCHEZ cette case pour que le token         │
│  n'expire jamais)                              │
│                                                 │
│ Intent: api ▼                                  │
│ (Sélectionnez "api" dans la liste)            │
│                                                 │
└─────────────────────────────────────────────────┘
```

4. **Cliquez sur "Create"**

---

### Étape 4 : COPIER LE TOKEN ! ⚠️

**IMPORTANT** : Une fenêtre s'affiche avec votre token :

```
┌─────────────────────────────────────────────────┐
│  Token created successfully!                    │
│                                                 │
│  ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx          │
│                                                 │
│  ⚠️  COPIEZ CE TOKEN MAINTENANT !              │
│  Vous ne pourrez plus le revoir après          │
│  avoir fermé cette fenêtre.                    │
│                                                 │
│  [Copy to clipboard]  [Close]                  │
└─────────────────────────────────────────────────┘
```

**COPIEZ le token complet** (commence par `ak_`)

---

### Étape 5 : Mettre à Jour le .env

Ouvrez votre fichier `.env` dans votre éditeur de code et **ajoutez/modifiez** ces lignes :

```env
# Authentik Configuration
AUTHENTIK_BASE_URL=http://5.189.156.115:31015
AUTHENTIK_API_TOKEN=ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Si vous n'avez pas encore configuré l'OAuth, ajoutez aussi :
AUTHENTIK_CLIENT_ID=votre_client_id
AUTHENTIK_CLIENT_SECRET=votre_client_secret
AUTHENTIK_REDIRECT_URI=http://localhost:8000/api/auth/authentik/callback
```

**Remplacez** `ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx` par le token que vous venez de copier !

---

### Étape 6 : Vider le Cache Laravel

Dans votre terminal PowerShell :

```bash
php artisan config:clear
```

**Résultat attendu :**
```
✅ INFO  Configuration cache cleared successfully.
```

---

## 🧪 Test : Vérifier que le Token Fonctionne

Dans votre terminal :

```bash
php artisan tinker
```

Puis testez :

```php
$service = app(\App\Services\AuthentikService::class);

// Test 1: Chercher un utilisateur
$user = $service->findUserByEmail('akadmin');
print_r($user);

// Test 2: Lister les groupes
$groups = $service->getGroupByName('Candidats');
print_r($groups);

// Si ça fonctionne sans erreur 403, votre token est bon ! ✅
```

---

## 📊 Résolution du Problème des Groupes

### Problème Original

L'endpoint `/api/v3/core/groups/{id}/add_user/` n'existe pas ou ne fonctionne pas comme prévu.

### ✅ Solution Implémentée

J'ai modifié `AuthentikService::addUserToRoleGroup()` pour utiliser **PATCH** sur le groupe :

**Avant (ne marche pas) :**
```php
POST /api/v3/core/groups/{id}/add_user/
{ "pk": userId }
```

**Après (correct) :**
```php
PATCH /api/v3/core/groups/{id}/
{ "users": [user1, user2, user3, ...] }
```

---

## 🧪 Test Complet Après Configuration

### 1. Inscription d'un Nouveau Utilisateur

```json
POST http://localhost:8000/api/auth/register

{
  "email": "nouveau@test.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Test",
  "prenom": "Nouveau",
  "contact": "0600000000",
  "role": "candidat"
}
```

### 2. Vérification dans Authentik

**A. Vérifier l'utilisateur :**
```
Authentik → Directory → Users
→ Chercher : nouveau@test.com
→ Cliquez dessus
→ Onglet "Groups"
→ Vous devriez voir : "Candidats" ✅
```

**B. Vérifier le groupe :**
```
Authentik → Directory → Groups
→ Cliquez sur "Candidats"
→ Onglet "Users"
→ Vous devriez voir : nouveau@test.com ✅
```

---

## 🔍 Vérifier les Logs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log
```

**Logs attendus après inscription :**
```
[INFO] Utilisateur créé dans Authentik {"user_id":123,"email":"nouveau@test.com","role":"candidat"}
[INFO] Groupe créé dans Authentik {"group_name":"Candidats","group_id":1}
[INFO] Utilisateur ajouté au groupe {"user_id":123,"group":"Candidats","role":"candidat","total_users":1}
```

**Si vous voyez une erreur :**
```
[ERROR] Erreur ajout utilisateur au groupe {"error":"...","response":"..."}
```
→ Vérifiez que le token API a les bonnes permissions.

---

## 🔐 Permissions du Token

Votre token API doit avoir les permissions pour :

1. ✅ Créer des utilisateurs
2. ✅ Modifier des utilisateurs (set password)
3. ✅ Créer des groupes
4. ✅ Modifier des groupes (ajouter membres)
5. ✅ Lire des utilisateurs et groupes

**Pour vérifier :**
```
Authentik → Admin → Directory → Tokens
→ Cliquez sur votre token
→ Vérifiez l'utilisateur associé (doit être admin)
```

---

## 📋 Checklist de Résolution

- [ ] Token API créé dans Authentik (Intent: api)
- [ ] Token copié et ajouté dans `.env` (`AUTHENTIK_API_TOKEN=ak_...`)
- [ ] Cache vidé (`php artisan config:clear`)
- [ ] Test avec Tinker réussi (pas d'erreur 403)
- [ ] Inscription test réussie
- [ ] Utilisateur visible dans Authentik → Users
- [ ] Utilisateur visible dans Authentik → Groups → Candidats

---

## 🚨 Si Ça Ne Fonctionne Toujours Pas

### Vérification 1 : Token Correct

```bash
# Dans .env
AUTHENTIK_API_TOKEN=ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
                   ^
                   Doit commencer par "ak_"
```

### Vérification 2 : Permissions du Token

Le token doit être créé avec un **utilisateur administrateur** (akadmin).

### Vérification 3 : Configuration Authentik

```env
AUTHENTIK_BASE_URL=http://5.189.156.115:31015
                   ^
                   Sans slash à la fin !
```

### Vérification 4 : Tester Directement l'API

```bash
curl -H "Authorization: Bearer ak_votre_token" \
     http://5.189.156.115:31015/api/v3/core/users/
```

**Si ça retourne des utilisateurs → Token OK ✅**  
**Si ça retourne 403 → Token invalide ❌**

---

## 💡 Alternative : Créer les Groupes Manuellement

Si l'API a des soucis, créez les groupes manuellement dans Authentik :

### Créer le Groupe "Candidats"

```
1. Authentik → Directory → Groups
2. Create
3. Remplir :
   - Name: Candidats
   - Attributes: {"role": "candidat"}
4. Save
```

### Créer les Autres Groupes

Répétez pour :
- **Responsables Auto-École** avec `{"role": "responsable_auto_ecole"}`
- **Administrateurs** avec `{"role": "admin"}`

Puis retestez l'inscription !

---

## 🎯 Résumé

**Problème :** Token API invalide + Endpoint incorrect  
**Solutions appliquées :**
1. ✅ Correction de l'endpoint pour ajouter aux groupes (PATCH au lieu de POST)
2. ✅ Guide pour créer un token API valide

**Prochaine étape :**
1. Créer le token API dans Authentik
2. L'ajouter dans `.env`
3. Vider le cache
4. Tester !

---

**Une fois le token configuré, les utilisateurs apparaîtront dans les groupes ! 🎉**

