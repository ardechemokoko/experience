# 🎭 Gestion des Rôles avec les Groupes Authentik

## ✅ Solution Implémentée

Les **rôles sont maintenant gérés via les Groupes Authentik** !

### 🔄 Fonctionnement

```
Inscription avec rôle "candidat"
    ↓
1. Créer utilisateur dans Authentik
    ↓
2. Créer automatiquement le groupe "Candidats" (si n'existe pas)
    ↓
3. Ajouter l'utilisateur au groupe "Candidats"
    ↓
✅ Le rôle est visible dans Authentik !
```

---

## 📋 Mapping des Rôles

| Rôle Laravel | Groupe Authentik | Attribut |
|--------------|------------------|----------|
| `candidat` | **Candidats** | `{"role": "candidat"}` |
| `responsable_auto_ecole` | **Responsables Auto-École** | `{"role": "responsable_auto_ecole"}` |
| `admin` | **Administrateurs** | `{"role": "admin"}` |

---

## 🔧 Configuration Requise

### 1️⃣ Créer un Token API Valide

**L'erreur 403 signifie que votre token est invalide !**

#### Étapes :

1. **Connectez-vous à Authentik** :
   ```
   http://5.189.156.115:31015
   ```

2. **Admin Interface** → **Directory** → **Tokens and App passwords**

3. **Create** :
   ```
   ┌─────────────────────────────────────┐
   │ Identifier: laravel-api-token       │
   │ User: [Votre admin]                 │
   │ Description: Token pour Laravel API │
   │ Expiring: ☐ (décocher)             │
   │ Intent: api                         │
   └─────────────────────────────────────┘
   ```

4. **IMPORTANT** : Copiez le token affiché !
   ```
   ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```

5. **Mettez à jour .env** :
   ```env
   AUTHENTIK_API_TOKEN=ak_votre_nouveau_token_ici
   ```

6. **Vider le cache** :
   ```bash
   php artisan config:clear
   ```

---

## 🧪 Test Complet

### 1. Inscription avec Rôle Candidat

```bash
POST http://5.189.156.115:31015/api/auth/register
Content-Type: application/json

{
  "email": "candidat@test.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Dupont",
  "prenom": "Jean",
  "contact": "0612345678",
  "role": "candidat"
}
```

**Ce qui se passe :**
1. ✅ Utilisateur créé dans Authentik
2. ✅ Groupe "Candidats" créé automatiquement (si n'existe pas)
3. ✅ Utilisateur ajouté au groupe "Candidats"

**Vérification dans Authentik :**
```
Directory → Users → candidat@test.com
→ Groups: Candidats ✅
```

---

### 2. Inscription avec Rôle Responsable

```bash
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "email": "responsable@test.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Martin",
  "prenom": "Sophie",
  "contact": "0698765432",
  "role": "responsable_auto_ecole"
}
```

**Vérification dans Authentik :**
```
Directory → Users → responsable@test.com
→ Groups: Responsables Auto-École ✅
```

---

### 3. Inscription avec Rôle Admin

```bash
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "email": "admin@test.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Durand",
  "prenom": "Pierre",
  "contact": "0601020304",
  "role": "admin"
}
```

**Vérification dans Authentik :**
```
Directory → Users → admin@test.com
→ Groups: Administrateurs ✅
```

---

## 👀 Visualiser les Rôles dans Authentik

### Méthode 1 : Via les Groupes

```
1. Authentik → Directory → Groups
2. Vous verrez :
   - Candidats (X membres)
   - Responsables Auto-École (X membres)
   - Administrateurs (X membres)
```

### Méthode 2 : Via l'Utilisateur

```
1. Authentik → Directory → Users
2. Cliquez sur un utilisateur
3. Onglet "Groups"
4. Vous verrez le groupe auquel il appartient
```

### Méthode 3 : Via les Attributs

```
1. Authentik → Directory → Users
2. Cliquez sur un utilisateur
3. Onglet "Attributes"
4. Vous verrez : { "role": "candidat", "contact": "...", ... }
```

---

## 🔍 Vérifier dans les Logs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Rechercher les créations de groupes
grep "Groupe créé" storage/logs/laravel.log

# Rechercher les ajouts aux groupes
grep "Utilisateur ajouté au groupe" storage/logs/laravel.log
```

**Exemple de logs :**
```
[2025-10-22] local.INFO: Utilisateur créé dans Authentik {"user_id":123,"email":"test@test.com","role":"candidat"}
[2025-10-22] local.INFO: Groupe créé dans Authentik {"group_name":"Candidats","group_id":1}
[2025-10-22] local.INFO: Utilisateur ajouté au groupe {"user_id":123,"group":"Candidats","role":"candidat"}
```

---

## 🎯 Avantages des Groupes

### ✅ Visibilité

- **Avant** : Rôle caché dans les attributs
- **Maintenant** : Rôle visible dans l'interface Authentik

### ✅ Gestion

```
Authentik → Directory → Groups → Candidats
→ Voir tous les membres
→ Ajouter/retirer des membres
→ Définir des permissions
```

### ✅ Permissions (Future)

Vous pourrez définir des permissions spécifiques par groupe :
```
Groupe "Administrateurs"
  → Permission: Accès admin
  → Permission: Gestion utilisateurs

Groupe "Candidats"
  → Permission: Voir dossiers
  → Permission: Modifier profil
```

---

## 🔧 Configuration des Variables .env

**Fichier `.env` complet pour Authentik :**

```env
# Authentik Configuration
AUTHENTIK_BASE_URL=http://5.189.156.115:31015
AUTHENTIK_CLIENT_ID=votre_client_id
AUTHENTIK_CLIENT_SECRET=votre_client_secret
AUTHENTIK_REDIRECT_URI=http://localhost:8000/api/auth/authentik/callback
AUTHENTIK_API_TOKEN=ak_votre_token_api_ici
```

**Après modification :**
```bash
php artisan config:clear
```

---

## 🚨 Résolution d'Erreurs

### Erreur : "Token invalid/expired" (403)

**Solution :**
1. Créer un nouveau token API dans Authentik
2. Mettre à jour `AUTHENTIK_API_TOKEN` dans `.env`
3. `php artisan config:clear`

---

### Erreur : "Group not found"

**Solution :**
Les groupes sont créés automatiquement ! Si l'erreur persiste :
1. Vérifiez les logs : `storage/logs/laravel.log`
2. Vérifiez le token API
3. Créez manuellement les groupes dans Authentik

---

### Utilisateur créé mais pas de groupe

**Solution :**
1. Vérifiez les logs pour voir l'erreur
2. Le token API doit avoir les permissions pour :
   - Créer des groupes
   - Ajouter des utilisateurs aux groupes

---

## 📊 Flux Complet

```
POST /api/auth/register
{ email, password, role: "candidat" }
    ↓
Laravel API (AuthController)
    ↓
AuthentikService::createUser()
    ├─> 1. POST /api/v3/core/users/
    │   → Crée l'utilisateur
    │
    ├─> 2. POST /api/v3/core/users/{id}/set_password/
    │   → Définit le mot de passe
    │
    └─> 3. AuthentikService::addUserToRoleGroup()
        │
        ├─> 3a. GET /api/v3/core/groups/?name=Candidats
        │   → Cherche le groupe
        │
        ├─> 3b. POST /api/v3/core/groups/ (si n'existe pas)
        │   → Crée le groupe
        │
        └─> 3c. POST /api/v3/core/groups/{id}/add_user/
            → Ajoute l'utilisateur au groupe

✅ Résultat :
   - Utilisateur dans Authentik
   - Utilisateur dans groupe "Candidats"
   - Rôle visible dans Authentik
```

---

## ✅ Checklist

- [ ] Token API créé dans Authentik
- [ ] Token API configuré dans `.env`
- [ ] `php artisan config:clear` exécuté
- [ ] Test inscription → Succès
- [ ] Utilisateur visible dans Authentik
- [ ] Groupe visible dans Authentik
- [ ] Utilisateur membre du groupe

---

## 🎉 Résultat Final

**Inscription d'un utilisateur :**

1. **Dans votre DB Laravel :**
   ```sql
   utilisateurs
   ├─ email: candidat@test.com
   └─ role: candidat
   ```

2. **Dans Authentik :**
   ```
   Directory → Users → candidat@test.com
   ├─ Email: candidat@test.com
   ├─ Name: Jean Dupont
   ├─ Groups: [Candidats]
   └─ Attributes: {"role": "candidat", "contact": "...", ...}
   ```

3. **Groupe Authentik :**
   ```
   Directory → Groups → Candidats
   └─ Members: [candidat@test.com, ...]
   ```

**Le rôle est maintenant visible et gérable dans Authentik ! 🎊**

---

**Date :** 22 Octobre 2025  
**Status :** ✅ Groupes implémentés  
**Prêt à tester !** 🚀

