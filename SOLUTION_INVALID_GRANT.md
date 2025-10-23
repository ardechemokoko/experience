# 🔧 Solution : Erreur "invalid_grant"

## ❌ Erreur Rencontrée

```json
{
  "success": false,
  "message": "Identifiants incorrects. Veuillez vérifier votre email et mot de passe."
}
```

**Erreur Authentik :**
```
"error": "invalid_grant"
```

---

## 🔍 Causes Possibles

### 1. Mot de Passe Incorrect ❌

L'utilisateur existe dans Authentik mais le mot de passe ne correspond pas.

### 2. Utilisateur Non Actif ❌

L'utilisateur existe mais n'est pas activé dans Authentik.

### 3. Utilisateur Créé Manuellement ❌

L'utilisateur a été créé manuellement dans Authentik sans mot de passe défini.

---

## ✅ Solutions

### Solution 1 : Créer l'Utilisateur via l'API (Recommandé)

Utilisez la route **`/api/auth/register`** pour créer l'utilisateur :

```bash
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "email": "mokoko3@gmail.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "nom": "Mokoko",
  "prenom": "Test",
  "contact": "0600000000",
  "role": "candidat"
}
```

**Ce que ça fait :**
1. ✅ Crée l'utilisateur dans Authentik
2. ✅ Définit le mot de passe
3. ✅ Ajoute au groupe "Candidats"
4. ✅ Synchronise dans votre DB
5. ✅ Retourne un token pour connexion immédiate

**Puis testez la connexion :**
```bash
POST http://localhost:8000/api/auth/login

{
  "email": "mokoko3@gmail.com",
  "password": "Password123!"
}
```

---

### Solution 2 : Réinitialiser le Mot de Passe dans Authentik

Si l'utilisateur existe déjà dans Authentik :

#### Méthode A : Via l'Interface Authentik

```
1. Authentik → Directory → Users
2. Chercher : mokoko3@gmail.com
3. Cliquer sur l'utilisateur
4. Onglet "Password"
5. Cliquer "Set password"
6. Entrer : Password123!
7. Sauvegarder
8. Réessayer la connexion
```

#### Méthode B : Réinitialiser et Recréer

```
1. Supprimer l'utilisateur dans Authentik
2. Utiliser POST /api/auth/register pour le recréer
3. Tout sera correctement configuré
```

---

### Solution 3 : Vérifier que l'Utilisateur est Actif

```
1. Authentik → Directory → Users
2. Chercher : mokoko3@gmail.com
3. Vérifier que "Active" est coché ☑
4. Si non coché, activer l'utilisateur
5. Réessayer
```

---

## 🧪 Test Complet : Créer et Connecter

### Étape 1 : Inscription (Crée dans Authentik)

**Postman :**
```
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "email": "test.nouveau@gmail.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "nom": "Nouveau",
  "prenom": "Test",
  "contact": "0612345678",
  "role": "candidat"
}
```

**Résultat Attendu (201) :**
```json
{
  "success": true,
  "message": "Inscription réussie. Bienvenue !",
  "user": { ... },
  "access_token": "eyJhbGc...",
  "refresh_token": "eyJhbGc...",
  "expires_in": 3600
}
```

---

### Étape 2 : Vérifier dans Authentik

```
Authentik → Directory → Users
→ Chercher : test.nouveau@gmail.com
→ Devrait exister ✅
→ Active: Oui ✅
→ Groups: Candidats ✅
```

---

### Étape 3 : Se Connecter

**Postman :**
```
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "test.nouveau@gmail.com",
  "password": "SecurePass123!"
}
```

**Résultat Attendu (200) :**
```json
{
  "success": true,
  "message": "Connexion réussie. Bienvenue !",
  "access_token": "eyJhbGc...",
  "refresh_token": "eyJhbGc..."
}
```

**✅ Ça devrait fonctionner !**

---

## 🔧 Vérifier la Configuration Password Grant

### Dans Authentik

```
1. Admin Interface
2. Applications → Providers
3. Cliquez sur votre Provider OAuth
4. Section "Authorization flow"
5. Vérifiez que cette case est COCHÉE :
   ☑ Resource Owner Password Credentials
6. Si pas cochée → Cochez-la et Sauvegardez
7. Réessayez
```

---

## 📊 Comparaison Utilisateur

### Utilisateur Créé Manuellement (Problématique)

```
Authentik → Directory → Users → Create

❌ Problème:
   - Mot de passe peut ne pas être défini correctement
   - Peut manquer des attributs
   - N'est pas dans les groupes automatiquement
```

### Utilisateur Créé via API (Recommandé)

```
POST /api/auth/register

✅ Avantages:
   - Mot de passe correctement hashé
   - Attributs complets (nom, prénom, contact, etc.)
   - Automatiquement ajouté au groupe du rôle
   - Synchronisé dans DB Laravel
```

---

## 🎯 Recommandations

### Pour Tester Maintenant

1. **Créez un NOUVEL utilisateur** via `/api/auth/register`
2. **N'utilisez PAS** les utilisateurs créés manuellement
3. **Testez la connexion** avec le nouveau compte

### Exemple de Test

```bash
# 1. Inscription
POST /api/auth/register
{
  "email": "test.final@gmail.com",
  "password": "FinalTest123!",
  "password_confirmation": "FinalTest123!",
  "nom": "Final",
  "prenom": "Test",
  "contact": "0600000000"
}

# 2. Connexion (immédiatement après)
POST /api/auth/login
{
  "email": "test.final@gmail.com",
  "password": "FinalTest123!"
}
```

**Cela devrait fonctionner ! ✅**

---

## 🔍 Debug Avancé

### Si le Problème Persiste

Exécutez le script de test avec le bon mot de passe :

```bash
php test_login_direct.php mokoko3@gmail.com MotDePasseRéel
```

Cela vous dira **exactement** quelle est l'erreur.

---

## 📚 Logs Laravel

Vérifiez les logs pour voir les détails :

```bash
tail -20 storage/logs/laravel.log
```

Cherchez :
```
[ERROR] Erreur rafraîchissement token
```

Cela vous donnera plus d'informations sur l'erreur Authentik.

---

## ✅ Checklist de Résolution

- [ ] Token API configuré et valide (voir `php verify_authentik_token.php`)
- [ ] Password Grant activé dans Provider Authentik
- [ ] Créer utilisateur via `/api/auth/register` (pas manuellement)
- [ ] Vérifier que l'utilisateur est actif dans Authentik
- [ ] Tester connexion avec le bon mot de passe
- [ ] Vérifier dans les logs Laravel (`storage/logs/laravel.log`)

---

## 💡 Astuce : Tester Directement sur Authentik

**Pour vérifier si les identifiants sont corrects :**

1. Allez sur : `http://5.189.156.115:31015`
2. Essayez de vous connecter avec :
   - Username: `mokoko3@gmail.com`
   - Password: Le mot de passe que vous testez

**Si la connexion fonctionne sur Authentik :**
→ Le problème est dans la configuration OAuth

**Si la connexion échoue sur Authentik :**
→ Le mot de passe est incorrect ou l'utilisateur n'existe pas

---

## 🎯 Solution Rapide

**La solution la plus simple :**

1. **Créez un NOUVEAU compte** via l'API :
```bash
POST /api/auth/register
{
  "email": "compte.test@gmail.com",
  "password": "Test123456!",
  "password_confirmation": "Test123456!",
  "nom": "Test",
  "prenom": "Compte",
  "contact": "0600000000"
}
```

2. **Connectez-vous** immédiatement :
```bash
POST /api/auth/login
{
  "email": "compte.test@gmail.com",
  "password": "Test123456!"
}
```

**✅ Ça devrait fonctionner !**

---

**Besoin d'aide ? Donnez-moi le résultat de :**
```bash
php test_login_direct.php votre-email@gmail.com VotreVraiMotDePasse
```

Je pourrai vous dire exactement quel est le problème ! 🔍

