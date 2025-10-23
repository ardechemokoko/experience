# 📮 JSON Complets pour Postman - Avec Rôles

## ✅ Tous les JSON Prêts à l'Emploi

---

## 1️⃣ INSCRIPTION - Candidat

**Endpoint :** `POST http://localhost:8000/api/auth/register`  
**Headers :** `Content-Type: application/json`

```json
{
  "email": "candidat@test.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Dupont",
  "prenom": "Jean",
  "contact": "0612345678",
  "adresse": "123 Avenue des Champs-Élysées, 75008 Paris",
  "role": "candidat"
}
```

**Réponse Attendue (201) :**
```json
{
  "success": true,
  "message": "Inscription réussie. Bienvenue !",
  "user": {
    "id": "uuid-xxx",
    "email": "candidat@test.com",
    "role": "candidat"
  },
  "access_token": "eyJhbGc...",
  "refresh_token": "eyJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

---

## 2️⃣ INSCRIPTION - Responsable Auto-École

```json
{
  "email": "responsable@autoecole.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "nom": "Martin",
  "prenom": "Sophie",
  "contact": "0698765432",
  "adresse": "15 Rue de la Formation, 69001 Lyon",
  "role": "responsable_auto_ecole"
}
```

**✅ Utilisateur ajouté au groupe :** "Responsables Auto-École"

---

## 3️⃣ INSCRIPTION - Administrateur

```json
{
  "email": "admin@systeme.com",
  "password": "AdminPass123!",
  "password_confirmation": "AdminPass123!",
  "nom": "Durand",
  "prenom": "Pierre",
  "contact": "0601020304",
  "adresse": "1 Place de la République, 75011 Paris",
  "role": "admin"
}
```

**✅ Utilisateur ajouté au groupe :** "Administrateurs"

---

## 4️⃣ CONNEXION

**Endpoint :** `POST http://localhost:8000/api/auth/login`  
**Headers :** `Content-Type: application/json`

```json
{
  "email": "candidat@test.com",
  "password": "Password123!"
}
```

**Réponse Attendue (200) :**
```json
{
  "success": true,
  "message": "Connexion réussie. Bienvenue !",
  "user": {
    "id": "uuid-xxx",
    "email": "candidat@test.com",
    "role": "candidat"
  },
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**💾 Sauvegarder les tokens pour les tests suivants !**

---

## 5️⃣ PROFIL UTILISATEUR

**Endpoint :** `GET http://localhost:8000/api/auth/me`  
**Headers :** 
```
Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Réponse Attendue (200) :**
```json
{
  "success": true,
  "user": {
    "id": "uuid-xxx",
    "email": "candidat@test.com",
    "role": "candidat",
    "personne": {
      "id": "uuid-yyy",
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "candidat@test.com",
      "contact": "0612345678",
      "adresse": "123 Avenue des Champs-Élysées, 75008 Paris"
    },
    "created_at": "2025-10-22T23:00:00.000000Z",
    "updated_at": "2025-10-22T23:00:00.000000Z"
  }
}
```

---

## 6️⃣ DÉCONNEXION

**Endpoint :** `POST http://localhost:8000/api/auth/logout`  
**Headers :** 
```
Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json
```

**Body :**
```json
{
  "refresh_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Réponse Attendue (200) :**
```json
{
  "success": true,
  "message": "Déconnexion réussie. À bientôt !"
}
```

---

## 7️⃣ RAFRAÎCHIR TOKEN

**Endpoint :** `POST http://localhost:8000/api/auth/refresh`  
**Headers :** `Content-Type: application/json`

```json
{
  "refresh_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Réponse Attendue (200) :**
```json
{
  "success": true,
  "message": "Token rafraîchi avec succès.",
  "access_token": "eyJhbGc...",
  "refresh_token": "eyJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

---

## 🎯 Workflow Complet de Test

### Étape 1 : Inscription

```json
POST http://localhost:8000/api/auth/register

{
  "email": "test.workflow@gmail.com",
  "password": "Workflow123!",
  "password_confirmation": "Workflow123!",
  "nom": "Workflow",
  "prenom": "Test",
  "contact": "0600000000",
  "role": "candidat"
}
```

**💾 COPIER les tokens retournés !**

---

### Étape 2 : Vérifier dans Authentik

```
http://5.189.156.115:31015

Directory → Users → test.workflow@gmail.com
→ Active: Oui ✅
→ Groups: Candidats ✅
```

---

### Étape 3 : Connexion

```json
POST http://localhost:8000/api/auth/login

{
  "email": "test.workflow@gmail.com",
  "password": "Workflow123!"
}
```

**✅ Devrait retourner les tokens !**

---

### Étape 4 : Profil

```
GET http://localhost:8000/api/auth/me
Authorization: Bearer {access_token_de_l_étape_3}
```

---

### Étape 5 : Déconnexion

```json
POST http://localhost:8000/api/auth/logout
Authorization: Bearer {access_token_de_l_étape_3}

{
  "refresh_token": "{refresh_token_de_l_étape_3}"
}
```

---

### Étape 6 : Vérifier Révocation

```
GET http://localhost:8000/api/auth/me
Authorization: Bearer {même_access_token}
```

**Résultat attendu :** ❌ 401 Unauthorized (token révoqué)

---

## 📋 Tous les Rôles Disponibles

| Rôle | Valeur JSON | Groupe Authentik |
|------|-------------|------------------|
| Candidat | `"role": "candidat"` | Candidats |
| Responsable | `"role": "responsable_auto_ecole"` | Responsables Auto-École |
| Admin | `"role": "admin"` | Administrateurs |

---

## 🧪 Collection Postman Complète

### Variables d'Environnement

```
base_url = http://localhost:8000/api
access_token = (sera rempli après login)
refresh_token = (sera rempli après login)
```

### Requêtes

**1. Register Candidat**
```
POST {{base_url}}/auth/register
Body: JSON ci-dessus avec role: "candidat"
```

**2. Register Responsable**
```
POST {{base_url}}/auth/register
Body: JSON ci-dessus avec role: "responsable_auto_ecole"
```

**3. Register Admin**
```
POST {{base_url}}/auth/register
Body: JSON ci-dessus avec role: "admin"
```

**4. Login**
```
POST {{base_url}}/auth/login
Tests: Set {{access_token}} = response.access_token
```

**5. Me**
```
GET {{base_url}}/auth/me
Headers: Authorization: Bearer {{access_token}}
```

**6. Logout**
```
POST {{base_url}}/auth/logout
Headers: Authorization: Bearer {{access_token}}
Body: { "refresh_token": "{{refresh_token}}" }
```

**7. Refresh**
```
POST {{base_url}}/auth/refresh
Body: { "refresh_token": "{{refresh_token}}" }
```

---

## ⚠️ Erreurs de Validation

### Email Manquant

```json
{
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Test",
  "prenom": "User",
  "contact": "0600000000",
  "role": "candidat"
}
```

**Erreur (422) :**
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "email": ["L'adresse email est obligatoire."]
  }
}
```

---

### Rôle Invalide

```json
{
  "email": "test@test.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Test",
  "prenom": "User",
  "contact": "0600000000",
  "role": "super_admin"
}
```

**Erreur (422) :**
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "role": ["Le rôle sélectionné n'est pas valide. Valeurs autorisées : admin, responsable_auto_ecole, candidat."]
  }
}
```

---

### Rôle Omis (Par Défaut = candidat)

```json
{
  "email": "test@test.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Test",
  "prenom": "User",
  "contact": "0600000000"
}
```

**✅ Inscription réussie avec role = "candidat" par défaut**

---

## 🎯 JSON Recommandés pour Vos Tests

### Test 1 : Candidat (Rôle par défaut)

```json
{
  "email": "candidat.test@gmail.com",
  "password": "Candidat123!",
  "password_confirmation": "Candidat123!",
  "nom": "Candidat",
  "prenom": "Test",
  "contact": "0600000001",
  "adresse": "1 Rue du Candidat",
  "role": "candidat"
}
```

### Test 2 : Responsable Auto-École

```json
{
  "email": "responsable.test@gmail.com",
  "password": "Responsable123!",
  "password_confirmation": "Responsable123!",
  "nom": "Responsable",
  "prenom": "Test",
  "contact": "0600000002",
  "adresse": "2 Rue du Responsable",
  "role": "responsable_auto_ecole"
}
```

### Test 3 : Administrateur

```json
{
  "email": "admin.test@gmail.com",
  "password": "Admin123!",
  "password_confirmation": "Admin123!",
  "nom": "Admin",
  "prenom": "Test",
  "contact": "0600000003",
  "adresse": "3 Rue de l'Admin",
  "role": "admin"
}
```

---

## 🔄 Connexion pour Chaque Rôle

### Connexion Candidat

```json
{
  "email": "candidat.test@gmail.com",
  "password": "Candidat123!"
}
```

### Connexion Responsable

```json
{
  "email": "responsable.test@gmail.com",
  "password": "Responsable123!"
}
```

### Connexion Admin

```json
{
  "email": "admin.test@gmail.com",
  "password": "Admin123!"
}
```

---

## ✅ Vérification des Groupes dans Authentik

Après chaque inscription, vérifiez :

```
Authentik → Directory → Groups

Candidats
└─ candidat.test@gmail.com ✅

Responsables Auto-École
└─ responsable.test@gmail.com ✅

Administrateurs
└─ admin.test@gmail.com ✅
```

---

## 📊 Champs Obligatoires vs Optionnels

| Champ | Type | Obligatoire | Valeur par défaut |
|-------|------|-------------|-------------------|
| `email` | string | ✅ Oui | - |
| `password` | string (min 8) | ✅ Oui | - |
| `password_confirmation` | string | ✅ Oui | - |
| `nom` | string | ✅ Oui | - |
| `prenom` | string | ✅ Oui | - |
| `contact` | string | ✅ Oui | - |
| `adresse` | string | ❌ Non | null |
| `role` | enum | ❌ Non | "candidat" |

---

## 🎯 JSON Minimal (Sans Rôle)

Si vous omettez le rôle, il sera automatiquement **"candidat"** :

```json
{
  "email": "simple@test.com",
  "password": "Simple123!",
  "password_confirmation": "Simple123!",
  "nom": "Simple",
  "prenom": "User",
  "contact": "0600000000"
}
```

**✅ Résultat :** Utilisateur créé avec `role: "candidat"`

---

## 📝 Récapitulatif des Rôles

### Candidat
```json
"role": "candidat"
```
- **Groupe Authentik :** Candidats
- **Usage :** Candidats au permis de conduire
- **Permissions :** Voir ses dossiers, modifier son profil

### Responsable Auto-École
```json
"role": "responsable_auto_ecole"
```
- **Groupe Authentik :** Responsables Auto-École
- **Usage :** Gérants d'auto-école
- **Permissions :** Gérer formations, dossiers, candidats

### Administrateur
```json
"role": "admin"
```
- **Groupe Authentik :** Administrateurs
- **Usage :** Administration système
- **Permissions :** Toutes permissions

---

## 🧪 Test Rapide Complet

**Copiez-collez ce JSON dans Postman :**

### Inscription

```json
{
  "email": "test.complet@gmail.com",
  "password": "TestComplet123!",
  "password_confirmation": "TestComplet123!",
  "nom": "Complet",
  "prenom": "Test",
  "contact": "0612345678",
  "adresse": "123 Rue du Test",
  "role": "candidat"
}
```

### Connexion (Utilisez le MÊME mot de passe)

```json
{
  "email": "test.complet@gmail.com",
  "password": "TestComplet123!"
}
```

**✅ Devrait fonctionner !**

---

## 🔍 Vérification

Après inscription, vérifiez dans Authentik :

```
http://5.189.156.115:31015

Directory → Users → test.complet@gmail.com
├─ Email: test.complet@gmail.com ✅
├─ Name: Test Complet ✅
├─ Active: Oui ✅
├─ Groups: [Candidats] ✅
└─ Attributes:
   ├─ role: candidat ✅
   ├─ contact: 0612345678 ✅
   ├─ adresse: 123 Rue du Test ✅
   ├─ nom: Complet ✅
   └─ prenom: Test ✅
```

---

## 🎊 Résumé

**Tous les JSON incluent maintenant le champ `role` !**

- ✅ `role: "candidat"` → Groupe "Candidats"
- ✅ `role: "responsable_auto_ecole"` → Groupe "Responsables Auto-École"
- ✅ `role: "admin"` → Groupe "Administrateurs"
- ✅ Rôle par défaut si omis : "candidat"

**Testez maintenant avec Postman ! 🚀**

