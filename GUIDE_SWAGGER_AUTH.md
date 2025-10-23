# 🔐 Guide d'Authentification Swagger - API Auto-École

## 🎯 Comment Utiliser Swagger avec Authentification

---

## 📋 Étapes pour Tester les Routes Protégées

### 1. 🔑 Obtenir un Token d'Authentification

#### Via Swagger UI

1. **Ouvrir Swagger** : `http://localhost:8000/api/documentation`

2. **Aller à la section Authentification** :
   - Chercher `🔐 Authentification`
   - Développer `POST /api/auth/login-direct`

3. **Se connecter** :
   ```json
   {
     "email": "candidat@test.com",
     "password": "Password123!"
   }
   ```

4. **Copier le token** de la réponse :
   ```json
   {
     "success": true,
     "access_token": "eyJ1c2VyX2lkIjoyLCJlbWFpbCI6ImNhbmRpZGF0QHRlc3QuY29tIiwicm9sZSI6ImNhbmRpZGF0IiwiaWF0IjoxNzM3NzQ4NzQ4LCJleHAiOjE3Mzc3NTIzNDh9.eyJzaWduYXR1cmUiOiJhYmNkZWYxMjM0NTY3ODkwYWJjZGVmMTIzNDU2Nzg5MGFiY2RlZjEyMzQ1Njc4OTAifQ"
   }
   ```

---

### 2. 🔒 Configurer l'Authentification dans Swagger

#### Méthode 1 : Via le Bouton "Authorize"

1. **Cliquer sur le bouton "Authorize"** (🔒) en haut à droite de Swagger

2. **Dans le champ "Value"** :
   ```
   Bearer eyJ1c2VyX2lkIjoyLCJlbWFpbCI6ImNhbmRpZGF0QHRlc3QuY29tIiwicm9sZSI6ImNhbmRpZGF0IiwiaWF0IjoxNzM3NzQ4NzQ4LCJleHAiOjE3Mzc3NTIzNDh9.eyJzaWduYXR1cmUiOiJhYmNkZWYxMjM0NTY3ODkwYWJjZGVmMTIzNDU2Nzg5MGFiY2RlZjEyMzQ1Njc4OTAifQ
   ```

3. **Cliquer sur "Authorize"**

4. **Cliquer sur "Close"**

#### Méthode 2 : Via l'Interface Swagger

1. **Développer une route protégée** (ex: `POST /api/candidats`)

2. **Cliquer sur "Try it out"**

3. **Dans la section "Authorization"** :
   - Sélectionner `BearerAuth`
   - Coller le token complet

---

### 3. ✅ Tester les Routes Protégées

#### Exemple : Compléter le Profil Candidat

1. **Aller à** : `POST /api/candidats/complete-profile`

2. **Cliquer sur "Try it out"**

3. **Remplir le formulaire** :
   ```json
   {
     "date_naissance": "1995-05-15",
     "lieu_naissance": "Dakar",
     "nip": "1234567890123",
     "type_piece": "CNI",
     "numero_piece": "1234567890",
     "nationalite": "Sénégalaise",
     "genre": "M"
   }
   ```

4. **Cliquer sur "Execute"**

5. **Vérifier la réponse** :
   ```json
   {
     "success": true,
     "message": "Profil candidat complété avec succès !",
     "data": {...}
   }
   ```

---

## 🔍 Routes par Type d'Authentification

### 🌐 Routes Publiques (Pas de Token)

Ces routes sont **accessibles sans authentification** :

```http
GET /api/candidats
GET /api/candidats/{id}
GET /api/auto-ecoles
GET /api/auto-ecoles/{id}
GET /api/auto-ecoles/{id}/formations
GET /api/formations
GET /api/formations/{id}
GET /api/formations/{id}/documents-requis
GET /api/dossiers
GET /api/dossiers/{id}
GET /api/documents
GET /api/documents/{id}
GET /api/referentiels
GET /api/referentiels/{id}
GET /api/health
```

### 🔒 Routes Protégées (Token Requis)

Ces routes nécessitent un **token d'authentification** :

#### Flux Candidat
```http
POST /api/candidats/complete-profile
POST /api/candidats/inscription-formation
GET  /api/candidats/mes-dossiers
POST /api/dossiers/{id}/upload-document
```

#### Flux Auto-École
```http
GET  /api/auto-ecoles/mes-dossiers
POST /api/dossiers/{id}/valider
POST /api/documents/{id}/valider
```

#### CRUD Complet
```http
POST   /api/candidats
PUT    /api/candidats/{id}
DELETE /api/candidats/{id}

POST   /api/auto-ecoles
PUT    /api/auto-ecoles/{id}
DELETE /api/auto-ecoles/{id}

POST   /api/formations
PUT    /api/formations/{id}
DELETE /api/formations/{id}

POST   /api/dossiers
PUT    /api/dossiers/{id}
DELETE /api/dossiers/{id}

POST   /api/documents
PUT    /api/documents/{id}
DELETE /api/documents/{id}

POST   /api/referentiels
PUT    /api/referentiels/{id}
DELETE /api/referentiels/{id}
```

---

## 🚨 Messages d'Erreur d'Authentification

### 401 Unauthorized

#### Token Manquant
```json
{
  "success": false,
  "message": "Token d'authentification manquant. Veuillez vous connecter."
}
```

#### Token Invalide
```json
{
  "success": false,
  "message": "Token d'authentification invalide."
}
```

#### Token Expiré
```json
{
  "success": false,
  "message": "Token d'authentification expiré. Veuillez vous reconnecter."
}
```

#### Utilisateur Non Trouvé
```json
{
  "success": false,
  "message": "Utilisateur non trouvé."
}
```

---

## 🛠️ Configuration Swagger

### Sécurité Bearer Token

Dans `config/l5-swagger.php` :

```php
'securityDefinitions' => [
    'securitySchemes' => [
        'bearer_token' => [
            'type' => 'http',
            'description' => 'Token d\'authentification JWT obtenu via /api/auth/login-direct',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
    ],
],
```

### Annotation dans les Contrôleurs

```php
/**
 * @OA\Post(
 *     path="/api/candidats",
 *     security={{"BearerAuth":{}}},
 *     @OA\Response(response=401, description="❌ Non authentifié"),
 * )
 */
```

---

## 📱 Test avec Postman/Insomnia

### Configuration

1. **Méthode** : POST
2. **URL** : `http://localhost:8000/api/candidats/complete-profile`
3. **Headers** :
   ```
   Content-Type: application/json
   Authorization: Bearer eyJ1c2VyX2lkIjoy...
   ```
4. **Body** :
   ```json
   {
     "date_naissance": "1995-05-15",
     "lieu_naissance": "Dakar",
     "nip": "1234567890123",
     "type_piece": "CNI",
     "numero_piece": "1234567890",
     "nationalite": "Sénégalaise",
     "genre": "M"
   }
   ```

---

## 🔄 Cycle de Vie du Token

### 1. Connexion
```http
POST /api/auth/login-direct
{
  "email": "candidat@test.com",
  "password": "Password123!"
}
```

### 2. Utilisation
```http
Authorization: Bearer {token}
```

### 3. Expiration
- **Durée** : 1 heure
- **Action** : Se reconnecter pour obtenir un nouveau token

### 4. Déconnexion
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

---

## 🎯 Bonnes Pratiques

### ✅ À Faire

1. **Toujours tester d'abord** les routes publiques
2. **Obtenir un token** avant de tester les routes protégées
3. **Vérifier l'expiration** du token
4. **Utiliser le bouton "Authorize"** dans Swagger
5. **Tester avec différents utilisateurs** (candidat, responsable, admin)

### ❌ À Éviter

1. **Ne pas oublier** le préfixe "Bearer " avant le token
2. **Ne pas utiliser** des tokens expirés
3. **Ne pas tester** les routes protégées sans token
4. **Ne pas partager** les tokens en production

---

## 🧪 Tests Recommandés

### Test 1 : Route Publique
```bash
curl -X GET http://localhost:8000/api/auto-ecoles
# Doit retourner 200 OK
```

### Test 2 : Route Protégée Sans Token
```bash
curl -X POST http://localhost:8000/api/candidats/complete-profile \
  -H "Content-Type: application/json" \
  -d '{"date_naissance": "1995-05-15"}'
# Doit retourner 401 Unauthorized
```

### Test 3 : Route Protégée Avec Token
```bash
curl -X POST http://localhost:8000/api/candidats/complete-profile \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJ1c2VyX2lkIjoy..." \
  -d '{"date_naissance": "1995-05-15", ...}'
# Doit retourner 201 Created
```

---

## 📚 Ressources

- **Swagger UI** : `http://localhost:8000/api/documentation`
- **API JSON** : `http://localhost:8000/api-docs.json`
- **Configuration** : `config/l5-swagger.php`
- **Middleware** : `app/Http/Middleware/AuthentikTokenMiddleware.php`

---

**🔐 Votre API est maintenant sécurisée et documentée dans Swagger ! 🎯**

