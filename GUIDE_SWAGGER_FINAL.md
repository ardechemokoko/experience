# 🔐 Guide d'Utilisation Swagger avec Authentification

## ✅ Configuration Terminée !

Votre API est maintenant **sécurisée** et **documentée** dans Swagger avec authentification Bearer Token.

---

## 🎯 Comment Utiliser Swagger avec Authentification

### 1. 📖 Accéder à Swagger

Ouvrez votre navigateur et allez à :
```
http://localhost:8000/api/documentation
```

### 2. 🔑 Obtenir un Token d'Authentification

#### Étape 1 : Se connecter
1. **Cherchez la section** `🔐 Authentification`
2. **Développez** `POST /api/auth/login-direct`
3. **Cliquez sur** "Try it out"
4. **Remplissez** le formulaire :
   ```json
   {
     "email": "candidat@test.com",
     "password": "Password123!"
   }
   ```
5. **Cliquez sur** "Execute"

#### Étape 2 : Copier le token
Dans la réponse, copiez le `access_token` :
```json
{
  "success": true,
  "access_token": "eyJ1c2VyX2lkIjoyOCwiZW1haWwiOiJjYW5kaWRhdEB0ZXN0LmNvbSIsInJvbGUiOiJjYW5kaWRhdCIsImlhdCI6MTczNzc0OTI0MCwiZXhwIjoxNzM3NzUyODQwfQ.eyJzaWduYXR1cmUiOiJhYmNkZWYxMjM0NTY3ODkwYWJjZGVmMTIzNDU2Nzg5MGFiY2RlZjEyMzQ1Njc4OTAifQ"
}
```

### 3. 🔒 Configurer l'Authentification dans Swagger

#### Méthode 1 : Bouton "Authorize"
1. **Cliquez sur** le bouton "Authorize" (🔒) en haut à droite
2. **Dans le champ "Value"** :
   ```
   Bearer eyJ1c2VyX2lkIjoyOCwiZW1haWwiOiJjYW5kaWRhdEB0ZXN0LmNvbSIsInJvbGUiOiJjYW5kaWRhdCIsImlhdCI6MTczNzc0OTI0MCwiZXhwIjoxNzM3NzUyODQwfQ.eyJzaWduYXR1cmUiOiJhYmNkZWYxMjM0NTY3ODkwYWJjZGVmMTIzNDU2Nzg5MGFiY2RlZjEyMzQ1Njc4OTAifQ
   ```
3. **Cliquez sur** "Authorize"
4. **Cliquez sur** "Close"

#### Méthode 2 : Par Route
1. **Développez** une route protégée (ex: `POST /api/candidats`)
2. **Cliquez sur** "Try it out"
3. **Dans la section "Authorization"** :
   - Sélectionnez `BearerAuth`
   - Collez le token complet

### 4. ✅ Tester les Routes Protégées

#### Exemple : Compléter le Profil Candidat
1. **Allez à** `POST /api/candidats/complete-profile`
2. **Cliquez sur** "Try it out"
3. **Remplissez** le formulaire :
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
4. **Cliquez sur** "Execute"
5. **Vérifiez** la réponse :
   ```json
   {
     "success": true,
     "message": "Profil candidat complété avec succès !",
     "data": {...}
   }
   ```

---

## 🔍 Identification des Routes Protégées

### 🔒 Routes avec Cadenas (Authentification Requise)

Ces routes affichent un **cadenas 🔒** et nécessitent un token :

#### Flux Candidat
- `POST /api/candidats/complete-profile` 🔒
- `POST /api/candidats/inscription-formation` 🔒
- `GET /api/candidats/mes-dossiers` 🔒
- `POST /api/dossiers/{id}/upload-document` 🔒

#### Flux Auto-École
- `GET /api/auto-ecoles/mes-dossiers` 🔒
- `POST /api/dossiers/{id}/valider` 🔒
- `POST /api/documents/{id}/valider` 🔒

#### CRUD Complet
- `POST /api/candidats` 🔒
- `PUT /api/candidats/{id}` 🔒
- `DELETE /api/candidats/{id}` 🔒
- `POST /api/auto-ecoles` 🔒
- `PUT /api/auto-ecoles/{id}` 🔒
- `DELETE /api/auto-ecoles/{id}` 🔒
- `POST /api/formations` 🔒
- `PUT /api/formations/{id}` 🔒
- `DELETE /api/formations/{id}` 🔒
- `POST /api/dossiers` 🔒
- `PUT /api/dossiers/{id}` 🔒
- `DELETE /api/dossiers/{id}` 🔒
- `POST /api/documents` 🔒
- `PUT /api/documents/{id}` 🔒
- `DELETE /api/documents/{id}` 🔒
- `POST /api/referentiels` 🔒
- `PUT /api/referentiels/{id}` 🔒
- `DELETE /api/referentiels/{id}` 🔒

### 🌐 Routes Publiques (Pas d'Authentification)

Ces routes sont **accessibles sans token** :

- `GET /api/candidats`
- `GET /api/candidats/{id}`
- `GET /api/auto-ecoles`
- `GET /api/auto-ecoles/{id}`
- `GET /api/auto-ecoles/{id}/formations`
- `GET /api/formations`
- `GET /api/formations/{id}`
- `GET /api/formations/{id}/documents-requis`
- `GET /api/dossiers`
- `GET /api/dossiers/{id}`
- `GET /api/documents`
- `GET /api/documents/{id}`
- `GET /api/referentiels`
- `GET /api/referentiels/{id}`
- `GET /api/health`

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
  "message": "Token invalide."
}
```

#### Token Expiré
```json
{
  "success": false,
  "message": "Token expiré. Veuillez vous reconnecter.",
  "expired_at": "2025-01-23 10:30:00"
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

## 🛠️ Configuration Technique

### Sécurité Bearer Token dans Swagger

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

### Middleware d'Authentification

Le middleware `auth.token` :
1. ✅ Vérifie la présence du token
2. ✅ Décode le token (JWT ou base64)
3. ✅ Vérifie l'expiration
4. ✅ Récupère l'utilisateur par email
5. ✅ Attache l'utilisateur à la requête

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
3. **Utiliser le bouton "Authorize"** dans Swagger
4. **Vérifier l'expiration** du token
5. **Tester avec différents utilisateurs** (candidat, responsable, admin)

### ❌ À Éviter

1. **Ne pas oublier** le préfixe "Bearer " avant le token
2. **Ne pas utiliser** des tokens expirés
3. **Ne pas tester** les routes protégées sans token
4. **Ne pas partager** les tokens en production

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

## 📚 Ressources

- **Swagger UI** : `http://localhost:8000/api/documentation`
- **API JSON** : `http://localhost:8000/api-docs.json`
- **Configuration** : `config/l5-swagger.php`
- **Middleware** : `app/Http/Middleware/AuthentikTokenMiddleware.php`
- **Helper** : `app/Http/Helpers/AuthHelper.php`

---

## 🎉 Résumé

✅ **Sécurité implémentée** : Toutes les routes d'écriture nécessitent un token  
✅ **Swagger configuré** : Bouton "Authorize" et icônes de cadenas  
✅ **Documentation complète** : Réponses 401 documentées  
✅ **Middleware fonctionnel** : Validation des tokens JWT  
✅ **Tests réussis** : Routes publiques et protégées testées  

**🔐 Votre API est maintenant sécurisée et documentée dans Swagger ! 🎯**
