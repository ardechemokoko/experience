# 🧪 Guide de Test Complet - API Authentik via Swagger

## 🎯 Objectif

Tester **TOUTES les routes** de votre API qui utilisent Authentik pour vérifier que tout fonctionne parfaitement.

---

## 🌐 Accès au Swagger

1. **Ouvrez votre navigateur**
2. **Allez à** : `http://localhost:8000/api/documentation`
3. **Rafraîchissez** avec `Ctrl + F5` si nécessaire

---

## 📋 Routes à Tester (9 endpoints)

### ✅ **Checklist de Test**

- [ ] 1. POST `/api/auth/register` - Inscription
- [ ] 2. POST `/api/auth/login-direct` - Connexion directe 🚀
- [ ] 3. GET `/api/auth/auth-url` - URL d'authentification
- [ ] 4. GET `/api/auth/authentik/redirect` - Redirection OAuth
- [ ] 5. GET `/api/auth/authentik/callback` - Callback OAuth
- [ ] 6. GET `/api/auth/me` - Profil utilisateur 🔒
- [ ] 7. POST `/api/auth/logout` - Déconnexion 🔒
- [ ] 8. POST `/api/auth/refresh` - Rafraîchir token
- [ ] 9. GET `/api/health` - Health check

---

## 🧪 Tests Détaillés

### Test 1️⃣ : Health Check (Échauffement)

**Objectif** : Vérifier que l'API fonctionne

1. **Cliquez sur** `GET /api/health`
2. **Cliquez sur** "Try it out"
3. **Cliquez sur** "Execute"

**✅ Résultat Attendu** :
```json
{
  "status": "ok",
  "message": "API Auto-École fonctionnelle",
  "timestamp": "2025-10-23T...",
  "version": "1.0.0",
  "environment": "local"
}
```

**🎯 Code attendu** : `200 OK`

---

### Test 2️⃣ : Inscription d'un Nouvel Utilisateur

**Objectif** : Créer un utilisateur dans Authentik et la base locale

1. **Cliquez sur** `POST /api/auth/register`
2. **Cliquez sur** "Try it out"
3. **Modifiez le JSON** :

```json
{
  "email": "test.swagger@example.com",
  "password": "TestSwagger123!",
  "password_confirmation": "TestSwagger123!",
  "nom": "Test",
  "prenom": "Swagger",
  "contact": "0612345678",
  "adresse": "123 Rue du Test",
  "role": "candidat"
}
```

4. **Cliquez sur** "Execute"

**✅ Résultat Attendu** :
```json
{
  "success": true,
  "message": "Inscription réussie. Redirigez l'utilisateur vers Authentik pour se connecter.",
  "user": {
    "id": "uuid...",
    "email": "test.swagger@example.com",
    "role": "candidat",
    "created_at": "2025-10-23T...",
    "personne": {
      "id": "uuid...",
      "nom": "Test",
      "prenom": "Swagger",
      "nom_complet": "Swagger Test",
      "email": "test.swagger@example.com",
      "contact": "0612345678",
      "adresse": "123 Rue du Test"
    }
  },
  "auth_url": "http://5.189.156.115:31015/...",
  "authentik": {
    "user_id": 123,
    "username": "test.swagger@example.com"
  }
}
```

**🎯 Code attendu** : `201 Created`

**📝 Notes** :
- ✅ L'utilisateur est créé dans Authentik
- ✅ L'utilisateur est créé dans la base locale
- ✅ Une personne associée est créée
- ✅ Le rôle est attribué dans Authentik
- ✅ Une URL d'authentification est fournie

---

### Test 3️⃣ : Connexion Directe (MÉTHODE PRINCIPALE) 🚀

**Objectif** : Se connecter avec le contournement Password Grant

1. **Cliquez sur** `POST /api/auth/login-direct`
2. **Cliquez sur** "Try it out"
3. **Entrez vos identifiants** :

```json
{
  "email": "candidat@test.com",
  "password": "Password123!"
}
```

4. **Cliquez sur** "Execute"

**✅ Résultat Attendu** :
```json
{
  "success": true,
  "message": "Connexion réussie !",
  "user": {
    "id": "uuid...",
    "email": "candidat@test.com",
    "role": "candidat",
    "created_at": "2025-10-23T...",
    "personne": {
      "id": "uuid...",
      "nom": "Dupont",
      "prenom": "Jean",
      "nom_complet": "Jean Dupont",
      "email": "candidat@test.com",
      "contact": "0612345678",
      "adresse": "123 Rue de Paris"
    }
  },
  "access_token": "eyJ1c2VyX2lkIjoyOCw...",
  "refresh_token": "eyJ1c2VyX2lkIjoyOCw...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "method": "direct_auth",
  "authentik": {
    "user_id": 28,
    "username": "candidat@test.com"
  }
}
```

**🎯 Code attendu** : `200 OK`

**📝 Notes** :
- ✅ Authentification via API Authentik
- ✅ Vérification du mot de passe
- ✅ Génération de tokens personnalisés
- ✅ Retour des informations complètes

**⚠️ IMPORTANT** : **Copiez le `access_token`** pour les tests suivants !

---

### Test 4️⃣ : Obtenir URL d'Authentification

**Objectif** : Récupérer l'URL pour OAuth Authorization Code Flow

1. **Cliquez sur** `GET /api/auth/auth-url`
2. **Cliquez sur** "Try it out"
3. **Cliquez sur** "Execute"

**✅ Résultat Attendu** :
```json
{
  "success": true,
  "auth_url": "http://5.189.156.115:31015/application/o/authorize/?client_id=...",
  "message": "Redirigez l'utilisateur vers cette URL pour s'authentifier."
}
```

**🎯 Code attendu** : `200 OK`

---

### Test 5️⃣ : Authentification avec Token (Route Protégée) 🔒

**Objectif** : Configurer l'authentification et tester une route protégée

#### 5.1. Configurer l'Authentification

1. **En haut de la page Swagger**, cliquez sur le bouton **"Authorize" 🔒**
2. **Dans le champ**, entrez :
   ```
   Bearer VOTRE_ACCESS_TOKEN_ICI
   ```
   *(Remplacez par le token copié du Test 3)*
3. **Cliquez sur** "Authorize"
4. **Cliquez sur** "Close"

**✅ Vous êtes maintenant authentifié !**

#### 5.2. Tester la Route Protégée

1. **Cliquez sur** `GET /api/auth/me`
2. **Cliquez sur** "Try it out"
3. **Cliquez sur** "Execute"

**✅ Résultat Attendu** :
```json
{
  "success": true,
  "user": {
    "id": "uuid...",
    "email": "candidat@test.com",
    "role": "candidat",
    "created_at": "2025-10-23T...",
    "updated_at": "2025-10-23T...",
    "personne": {
      "id": "uuid...",
      "utilisateur_id": "uuid...",
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "candidat@test.com",
      "contact": "0612345678",
      "adresse": "123 Rue de Paris",
      "created_at": "2025-10-23T...",
      "updated_at": "2025-10-23T..."
    }
  }
}
```

**🎯 Code attendu** : `200 OK`

**📝 Notes** :
- ✅ Le token est validé
- ✅ Les informations complètes sont retournées
- ✅ La relation `personne` est chargée

---

### Test 6️⃣ : Rafraîchir le Token

**Objectif** : Renouveler le token d'accès avec le refresh token

1. **Cliquez sur** `POST /api/auth/refresh`
2. **Cliquez sur** "Try it out"
3. **Entrez le refresh token** du Test 3 :

```json
{
  "refresh_token": "VOTRE_REFRESH_TOKEN_ICI"
}
```

4. **Cliquez sur** "Execute"

**✅ Résultat Attendu** :
```json
{
  "success": true,
  "message": "Token rafraîchi avec succès.",
  "access_token": "nouveau_token...",
  "refresh_token": "nouveau_refresh_token...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**🎯 Code attendu** : `200 OK`

---

### Test 7️⃣ : Déconnexion

**Objectif** : Révoquer les tokens et déconnecter l'utilisateur

1. **Assurez-vous d'être authentifié** (Test 5.1)
2. **Cliquez sur** `POST /api/auth/logout`
3. **Cliquez sur** "Try it out"
4. **Entrez le refresh token** :

```json
{
  "refresh_token": "VOTRE_REFRESH_TOKEN_ICI"
}
```

5. **Cliquez sur** "Execute"

**✅ Résultat Attendu** :
```json
{
  "success": true,
  "message": "Déconnexion réussie. À bientôt !"
}
```

**🎯 Code attendu** : `200 OK`

**📝 Notes** :
- ✅ Access token révoqué dans Authentik
- ✅ Refresh token révoqué dans Authentik
- ✅ L'utilisateur est déconnecté

---

### Test 8️⃣ : Vérifier la Révocation du Token

**Objectif** : Confirmer que le token est bien révoqué

1. **Essayez de nouveau** `GET /api/auth/me`
2. **Sans reconfigurer l'authentification**
3. **Cliquez sur** "Execute"

**✅ Résultat Attendu** :
```json
{
  "success": false,
  "message": "Non authentifié. Veuillez vous connecter."
}
```

**🎯 Code attendu** : `401 Unauthorized`

**📝 Notes** :
- ✅ Le token ne fonctionne plus
- ✅ L'utilisateur doit se reconnecter

---

### Test 9️⃣ : Reconnecter l'Utilisateur

**Objectif** : Vérifier qu'on peut se reconnecter après déconnexion

1. **Refaites le Test 3** (Connexion directe)
2. **Vérifiez** que vous obtenez de nouveaux tokens
3. **Reconfigurez l'authentification** avec le nouveau token
4. **Testez** `GET /api/auth/me` à nouveau

**✅ Résultat Attendu** : Connexion réussie avec de nouveaux tokens

---

## 🎯 Résumé des Tests

### ✅ Ce qui DOIT Fonctionner

| # | Endpoint | Méthode | Authentification | Ce qui est testé |
|---|----------|---------|------------------|------------------|
| 1 | `/api/health` | GET | ❌ Non | API opérationnelle |
| 2 | `/api/auth/register` | POST | ❌ Non | Création dans Authentik + DB |
| 3 | `/api/auth/login-direct` | POST | ❌ Non | Connexion + Tokens |
| 4 | `/api/auth/auth-url` | GET | ❌ Non | URL OAuth |
| 5 | `/api/auth/me` | GET | ✅ Oui | Profil utilisateur |
| 6 | `/api/auth/refresh` | POST | ❌ Non | Renouvellement token |
| 7 | `/api/auth/logout` | POST | ✅ Oui | Révocation tokens |

---

## 🔍 Vérifications Authentik

### Après l'Inscription (Test 2)

1. **Connectez-vous à Authentik** : `http://5.189.156.115:31015`
2. **Admin Interface** → **Directory** → **Users**
3. **Vérifiez** que l'utilisateur `test.swagger@example.com` existe
4. **Cliquez** sur l'utilisateur
5. **Vérifiez** :
   - ✅ Email correct
   - ✅ Nom complet : "Swagger Test"
   - ✅ Statut : Active
   - ✅ Groupe : "Candidats"

### Après la Connexion (Test 3)

1. **Dans Authentik**, allez dans **Events** → **Logs**
2. **Vérifiez** qu'il y a un événement de connexion récent
3. **Vérifiez** l'utilisateur et l'IP

---

## 📊 Tableau de Bord de Test

### Scénario Complet : Cycle de Vie Utilisateur

```
1. 📝 Inscription          → ✅ Utilisateur créé dans Authentik + DB
2. 🚀 Connexion Directe    → ✅ Tokens obtenus
3. 🔒 Authentification     → ✅ Bearer token configuré
4. 👤 Profil Utilisateur   → ✅ Données complètes récupérées
5. 🔄 Rafraîchir Token     → ✅ Nouveaux tokens obtenus
6. 🚪 Déconnexion          → ✅ Tokens révoqués
7. ❌ Test Post-Déco       → ✅ Accès refusé (401)
8. 🔁 Reconnecter          → ✅ Nouvelle connexion OK
```

---

## 🐛 Dépannage

### Erreur 401 "Non authentifié"

**Problème** : Token non reconnu

**Solutions** :
1. Vérifiez que vous avez bien cliqué sur "Authorize"
2. Vérifiez que le token commence par "Bearer "
3. Vérifiez que le token n'a pas expiré (3600s = 1h)
4. Reconnectez-vous pour obtenir un nouveau token

### Erreur 422 "Validation"

**Problème** : Données invalides

**Solutions** :
1. Vérifiez que tous les champs requis sont remplis
2. Vérifiez le format de l'email
3. Vérifiez que le mot de passe a au moins 8 caractères
4. Vérifiez que `password_confirmation` correspond

### Erreur 500 "Erreur serveur"

**Problème** : Erreur côté serveur

**Solutions** :
1. Vérifiez les logs Laravel : `storage/logs/laravel.log`
2. Vérifiez que le serveur tourne : `php artisan serve`
3. Vérifiez la connexion à Authentik
4. Vérifiez le token API Authentik dans `.env`

### L'Utilisateur n'Apparaît Pas dans Authentik

**Problème** : Inscription échoue côté Authentik

**Solutions** :
1. Vérifiez `AUTHENTIK_API_TOKEN` dans `.env`
2. Vérifiez que le token a les permissions `api`
3. Regardez les logs Laravel pour l'erreur exacte
4. Créez un nouveau token API dans Authentik

---

## 📝 Rapport de Test

### Template de Rapport

```
✅ Test effectué le : [DATE]
✅ Swagger accessible : OUI / NON
✅ Tests passés : X/9

Détails :
[ ] 1. Health Check
[ ] 2. Inscription
[ ] 3. Connexion Directe
[ ] 4. URL Auth
[ ] 5. Profil Utilisateur
[ ] 6. Refresh Token
[ ] 7. Déconnexion
[ ] 8. Vérification Révocation
[ ] 9. Reconnecter

Problèmes rencontrés :
- ...

Notes :
- ...
```

---

## 🎉 Si Tous les Tests Passent

### Vous avez vérifié que :

✅ **Authentik fonctionne** comme IAM central  
✅ **L'inscription** crée bien les utilisateurs  
✅ **La connexion directe** contourne le Password Grant  
✅ **Les tokens** sont générés et fonctionnent  
✅ **L'authentification** Bearer est opérationnelle  
✅ **Les informations** complètes sont retournées  
✅ **Le refresh** de token fonctionne  
✅ **La déconnexion** révoque bien les tokens  
✅ **La sécurité** est respectée (401 après déco)  

### Votre API est **100% Opérationnelle !** 🚀

---

## 📚 Prochaines Étapes

1. **Tester avec différents rôles** :
   - Candidat
   - Responsable Auto-École
   - Administrateur

2. **Tester les cas d'erreur** :
   - Mauvais mot de passe
   - Email inexistant
   - Token expiré

3. **Documentation Frontend** :
   - Intégrer l'API dans votre application
   - Gérer le stockage des tokens
   - Implémenter le refresh automatique

4. **Monitoring** :
   - Surveiller les logs
   - Traquer les erreurs
   - Optimiser les performances

---

**🎯 Bon Test ! Suivez ce guide étape par étape et cochez chaque case ! ✅**

