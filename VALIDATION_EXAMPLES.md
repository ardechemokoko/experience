# 📝 Exemples de Validation - Messages en Français

## ✅ Améliorations Apportées

### 1. **Classes Request Personnalisées**
- `RegisterRequest` - Validation pour l'inscription
- `LoginRequest` - Validation pour la connexion

### 2. **Messages d'Erreur en Français**
Tous les messages de validation sont maintenant en français avec des messages clairs et explicites.

### 3. **Gestion des Erreurs Améliorée**
- Transactions de base de données (DB::beginTransaction/commit/rollback)
- Logging des erreurs et des événements importants
- Messages d'erreur conditionnels selon l'environnement (debug mode)
- Type hints stricts sur toutes les méthodes

---

## 🧪 Exemples de Tests avec Validation

### 1. Inscription - Champs Manquants

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com"
  }'
```

**Réponse (422 Unprocessable Entity) :**
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "password": [
      "Le mot de passe est obligatoire."
    ],
    "nom": [
      "Le nom est obligatoire."
    ],
    "prenom": [
      "Le prénom est obligatoire."
    ],
    "contact": [
      "Le numéro de contact est obligatoire."
    ]
  }
}
```

---

### 2. Inscription - Email Invalide

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "email-invalide",
    "password": "Pass123",
    "password_confirmation": "Pass123",
    "nom": "Dupont",
    "prenom": "Jean",
    "contact": "0612345678"
  }'
```

**Réponse (422) :**
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "email": [
      "L'adresse email doit être valide."
    ]
  }
}
```

---

### 3. Inscription - Mot de Passe Trop Court

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "123",
    "password_confirmation": "123",
    "nom": "Dupont",
    "prenom": "Jean",
    "contact": "0612345678"
  }'
```

**Réponse (422) :**
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "password": [
      "Le mot de passe doit contenir au moins 8 caractères."
    ]
  }
}
```

---

### 4. Inscription - Confirmation Mot de Passe Incorrecte

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123",
    "password_confirmation": "DifferentPassword",
    "nom": "Dupont",
    "prenom": "Jean",
    "contact": "0612345678"
  }'
```

**Réponse (422) :**
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "password": [
      "La confirmation du mot de passe ne correspond pas."
    ]
  }
}
```

---

### 5. Inscription - Email Déjà Utilisé

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "existing@example.com",
    "password": "Password123",
    "password_confirmation": "Password123",
    "nom": "Dupont",
    "prenom": "Jean",
    "contact": "0612345678"
  }'
```

**Réponse (422) :**
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "email": [
      "Cette adresse email est déjà utilisée."
    ]
  }
}
```

---

### 6. Inscription - Rôle Invalide

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123",
    "password_confirmation": "Password123",
    "nom": "Dupont",
    "prenom": "Jean",
    "contact": "0612345678",
    "role": "super_admin"
  }'
```

**Réponse (422) :**
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "role": [
      "Le rôle sélectionné n'est pas valide. Valeurs autorisées : admin, responsable_auto_ecole, candidat."
    ]
  }
}
```

---

### 7. Inscription - Réussie

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "nouveau@example.com",
    "password": "Password123",
    "password_confirmation": "Password123",
    "nom": "Dupont",
    "prenom": "Jean",
    "contact": "0612345678",
    "adresse": "123 Rue de Paris, Lyon"
  }'
```

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "Inscription réussie. Bienvenue !",
  "user": {
    "id": "9d45e8f0-7890-4abc-defg-123456789abc",
    "email": "nouveau@example.com",
    "role": "candidat"
  },
  "access_token": "eyJ1c2VyX2lkIjoiOWQ0NWU4Zj...",
  "token_type": "Bearer"
}
```

---

### 8. Connexion - Email Manquant

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "password": "Password123"
  }'
```

**Réponse (422) :**
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "email": [
      "L'adresse email est obligatoire."
    ]
  }
}
```

---

### 9. Connexion - Identifiants Incorrects

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "WrongPassword"
  }'
```

**Réponse (401 Unauthorized) :**
```json
{
  "success": false,
  "message": "Identifiants incorrects. Veuillez vérifier votre email et mot de passe."
}
```

---

### 10. Connexion - Réussie

**Requête :**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123"
  }'
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Connexion réussie. Bienvenue !",
  "user": {
    "id": "9d45e8f0-7890-4abc-defg-123456789abc",
    "email": "test@example.com",
    "role": "candidat"
  },
  "access_token": "eyJ1c2VyX2lkIjoiOWQ0NWU4Zj...",
  "token_type": "Bearer"
}
```

---

## 📊 Structure des Réponses

### Réponse de Succès
```json
{
  "success": true,
  "message": "Message de succès",
  "data": {}
}
```

### Réponse d'Erreur de Validation (422)
```json
{
  "success": false,
  "message": "Erreur de validation des données.",
  "errors": {
    "champ1": ["Message d'erreur 1"],
    "champ2": ["Message d'erreur 2"]
  }
}
```

### Réponse d'Erreur Serveur (500)
```json
{
  "success": false,
  "message": "Message d'erreur général",
  "error": "Détails techniques (en mode debug uniquement)"
}
```

---

## 🔍 Règles de Validation Appliquées

### Inscription (`RegisterRequest`)

| Champ | Règles | Message d'Erreur |
|-------|--------|------------------|
| `email` | required, email, unique, max:255 | Messages personnalisés en français |
| `password` | required, string, min:8, confirmed | Messages personnalisés en français |
| `nom` | required, string, max:255 | Messages personnalisés en français |
| `prenom` | required, string, max:255 | Messages personnalisés en français |
| `contact` | required, string, max:20 | Messages personnalisés en français |
| `adresse` | nullable, string, max:500 | Messages personnalisés en français |
| `role` | sometimes, string, in:admin,responsable_auto_ecole,candidat | Messages personnalisés en français |

### Connexion (`LoginRequest`)

| Champ | Règles | Message d'Erreur |
|-------|--------|------------------|
| `email` | required, email, max:255 | Messages personnalisés en français |
| `password` | required, string, min:8 | Messages personnalisés en français |

---

## 🛡️ Sécurité et Bonnes Pratiques

### ✅ Implémentées

1. **Transactions de Base de Données**
   ```php
   DB::beginTransaction();
   try {
       // Code...
       DB::commit();
   } catch (Exception $e) {
       DB::rollBack();
       throw $e;
   }
   ```

2. **Logging des Événements**
   ```php
   Log::info('Connexion réussie', ['user_id' => $user->id]);
   Log::warning('Tentative de connexion échouée', ['email' => $email]);
   Log::error('Erreur critique', ['error' => $e->getMessage()]);
   ```

3. **Messages Conditionnels selon l'Environnement**
   ```php
   'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.'
   ```

4. **Type Hints Stricts**
   ```php
   public function login(LoginRequest $request): JsonResponse
   ```

5. **Validation Automatique**
   - Les Form Requests valident automatiquement
   - Réponse JSON formatée automatiquement en cas d'erreur
   - Code 422 pour les erreurs de validation

---

## 🧪 Tester avec Postman

### Collection Postman

Créez une collection avec ces requêtes :

1. **POST** - Inscription
   - URL: `http://localhost:8000/api/auth/register`
   - Body: JSON

2. **POST** - Connexion
   - URL: `http://localhost:8000/api/auth/login`
   - Body: JSON

3. **GET** - Profil
   - URL: `http://localhost:8000/api/auth/me`
   - Headers: `Authorization: Bearer {token}`

4. **POST** - Déconnexion
   - URL: `http://localhost:8000/api/auth/logout`
   - Headers: `Authorization: Bearer {token}`

---

## 📝 Notes Importantes

1. **Validation Automatique** : Les Form Requests gèrent automatiquement la validation et retournent les erreurs au format JSON.

2. **Messages Personnalisés** : Chaque règle de validation a un message personnalisé en français.

3. **Sécurité** : Les mots de passe sont hashés avec `bcrypt` via `Hash::make()`.

4. **Logging** : Tous les événements importants sont loggés dans `storage/logs/laravel.log`.

5. **Transactions** : Les opérations de création utilisent des transactions pour garantir la cohérence des données.

---

**Date de création :** 22 octobre 2025  
**Version Laravel :** 12.35.0  
**Validation :** Messages en français ✅

