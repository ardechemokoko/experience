# 📚 Documentation Swagger - Auto-École API

## 🎯 Accès à la Documentation

Votre documentation Swagger est maintenant disponible et prête à l'emploi !

### 🌐 URL d'Accès

```
http://localhost:8000/api/documentation
```

### 🚀 Démarrage Rapide

1. **Démarrer le serveur Laravel** :
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. **Ouvrir votre navigateur** :
   ```
   http://localhost:8000/api/documentation
   ```

3. **Explorer l'API** :
   - Tous les endpoints sont documentés
   - Vous pouvez tester directement depuis l'interface
   - Les exemples de requêtes/réponses sont fournis

---

## 📋 Endpoints Disponibles

### 🔐 Authentification

#### 1. **📝 Inscription** (`POST /api/auth/register`)
- Crée un nouvel utilisateur dans Authentik
- Retourne l'URL d'authentification
- **Exemple** :
  ```json
  {
    "email": "jean.dupont@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "nom": "Dupont",
    "prenom": "Jean",
    "contact": "0612345678",
    "role": "candidat"
  }
  ```

#### 2. **🚀 Connexion Directe** (`POST /api/auth/login-direct`)
- **Contournement Password Grant Flow**
- Authentification directe via API Authentik
- **Exemple** :
  ```json
  {
    "email": "jean.dupont@example.com",
    "password": "Password123!"
  }
  ```

#### 3. **🔗 Obtenir URL d'Authentification** (`GET /api/auth/auth-url`)
- Génère l'URL pour Authorization Code Flow
- Utilisé pour la connexion via navigateur

#### 4. **🔄 Redirection vers Authentik** (`GET /api/auth/authentik/redirect`)
- Redirige vers Authentik pour OAuth
- Utilisé dans le flux d'autorisation

#### 5. **📞 Callback Authentik** (`GET /api/auth/authentik/callback`)
- Gère le retour après authentification OAuth
- Récupère les tokens d'accès

### 👤 Utilisateurs

#### 6. **👤 Profil Utilisateur** (`GET /api/auth/me`)
- Récupère les informations de l'utilisateur connecté
- **Authentification requise** (Bearer Token)

### 🔄 Tokens

#### 7. **🚪 Déconnexion** (`POST /api/auth/logout`)
- Révoque les tokens d'accès et de rafraîchissement
- **Authentification requise** (Bearer Token)
- **Exemple** :
  ```json
  {
    "refresh_token": "eyJ1c2VyX2lkIjoyOCwidHlwZSI6In..."
  }
  ```

#### 8. **🔄 Rafraîchir Token** (`POST /api/auth/refresh`)
- Renouvelle le token d'accès
- **Exemple** :
  ```json
  {
    "refresh_token": "eyJ1c2VyX2lkIjoyOCwidHlwZSI6In..."
  }
  ```

### ❤️ Santé

#### 9. **❤️ Health Check** (`GET /api/health`)
- Vérifie que l'API est opérationnelle
- Pas d'authentification requise

---

## 🔑 Authentification dans Swagger

### Utiliser les Endpoints Protégés

1. **Connectez-vous** via `/api/auth/login-direct`
2. **Copiez le `access_token`** de la réponse
3. **Cliquez sur "Authorize" 🔒** en haut de la page Swagger
4. **Entrez** : `Bearer YOUR_ACCESS_TOKEN`
5. **Validez** en cliquant sur "Authorize"
6. **Testez les endpoints protégés** (like `/api/auth/me`)

---

## 🎨 Fonctionnalités du Swagger

### ✅ Ce que vous pouvez faire :

1. **📖 Lire la documentation complète**
   - Description de chaque endpoint
   - Paramètres requis/optionnels
   - Types de réponses possibles

2. **🧪 Tester les endpoints directement**
   - Bouton "Try it out"
   - Remplir les champs
   - Cliquer sur "Execute"
   - Voir la réponse en temps réel

3. **📋 Voir les exemples**
   - Exemples de requêtes
   - Exemples de réponses
   - Codes d'erreur possibles

4. **🔍 Filtrer les endpoints**
   - Recherche par tag
   - Recherche par mot-clé
   - Navigation par catégorie

5. **📥 Exporter la spécification**
   - Format JSON disponible
   - Compatible avec Postman
   - Compatible avec Insomnia

---

## 🔧 Personnalisation

### Thème Personnalisé

Le Swagger utilise un thème personnalisé aux couleurs de votre projet :
- 🟢 **Vert** (#50C786) : Couleur principale
- 🟢 **Vert foncé** (#2E7D32) : Couleur secondaire
- 🟠 **Orange** (#FF6B35) : Couleur d'accent

### Régénérer la Documentation

Si vous modifiez les annotations dans le code :

```bash
php artisan l5-swagger:generate
```

### Vider le Cache

Si les changements ne s'affichent pas :

```bash
php artisan config:clear
php artisan cache:clear
php artisan l5-swagger:generate
```

---

## 📝 Ajouter de Nouveaux Endpoints

### Exemple d'Annotation

```php
/**
 * @OA\Post(
 *     path="/api/mon-endpoint",
 *     operationId="monEndpoint",
 *     tags={"Ma Catégorie"},
 *     summary="Description courte",
 *     description="Description détaillée de l'endpoint",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="champ1", type="string", example="valeur1"),
 *             @OA\Property(property="champ2", type="integer", example=123)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     )
 * )
 */
public function monEndpoint(Request $request)
{
    // Votre code ici
}
```

Puis régénérez :
```bash
php artisan l5-swagger:generate
```

---

## 🌐 Accès depuis l'Extérieur

### En Production

Modifiez `APP_URL` dans `.env` :
```env
APP_URL=https://api.votre-domaine.com
```

Swagger sera accessible à :
```
https://api.votre-domaine.com/api/documentation
```

### Configuration CORS

Si nécessaire, configurez CORS dans `config/cors.php` pour permettre l'accès depuis votre frontend.

---

## 📚 Ressources

### Documentation L5-Swagger
- [GitHub](https://github.com/DarkaOnLine/L5-Swagger)
- [OpenAPI Specification](https://swagger.io/specification/)

### Documentation Swagger
- [Swagger UI](https://swagger.io/tools/swagger-ui/)
- [Annotations PHP](https://github.com/zircote/swagger-php)

---

## 🎯 Résumé

✅ **Swagger installé et configuré**  
✅ **Tous les endpoints d'authentification documentés**  
✅ **Thème personnalisé appliqué**  
✅ **Exemples de requêtes fournis**  
✅ **Possibilité de tester directement**  

### 🚀 Accédez maintenant à :
```
http://localhost:8000/api/documentation
```

**🎉 Votre documentation Swagger est prête à l'emploi !**

