# 🔧 Créer un Nouveau Provider avec Password Grant

## 🎯 Solution : Nouveau Provider OAuth

Si vous ne trouvez pas "Resource Owner Password Credentials" dans votre version, créons un nouveau Provider.

---

## 📋 Étapes Détaillées

### 1. Supprimer l'Ancien Provider (Optionnel)

```
Authentik → Admin Interface → Applications → Providers
→ Trouvez "permis" → Delete
```

### 2. Créer un Nouveau Provider

```
Applications → Providers → Create
```

**Configuration :**

```
┌─────────────────────────────────────────────────────┐
│ Name: Auto-Ecole-OAuth-Provider                     │
│                                                     │
│ Type: OAuth2/OpenID Provider                        │
│                                                     │
│ Client Type: Confidential                           │
│                                                     │
│ Authorization Flow:                                 │
│   ☑ Authorization Code                              │
│   ☑ Implicit Flow                                   │
│   ☑ Resource Owner Password Credentials            │ ← Cherchez cette option
│                                                     │
│ Redirect URIs:                                      │
│   http://localhost:8000/api/auth/authentik/callback│
│                                                     │
│ Signing Key: (sélectionner une clé)                │
│                                                     │
│ Scopes:                                             │
│   ☑ openid                                          │
│   ☑ email                                           │
│   ☑ profile                                         │
│   ☑ offline_access                                  │
│   ☑ goauthentik.io/api                              │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### 3. Si "Resource Owner Password Credentials" n'apparaît pas

**Dans les versions récentes d'Authentik, cette option peut être :**

- **Dans "Grant Types"** au lieu de "Authorization Flow"
- **Dans "Advanced Settings"**
- **Dans une section séparée "OAuth2 Settings"**

### 4. Alternative : Utiliser Authorization Code Flow

Si le Password Grant n'est pas disponible, on peut utiliser le **Authorization Code Flow** :

**Avantages :**
- ✅ Plus sécurisé
- ✅ Fonctionne avec toutes les versions d'Authentik
- ✅ Supporte les refresh tokens

**Inconvénients :**
- ❌ Nécessite une redirection vers Authentik
- ❌ Plus complexe pour l'API

---

## 🔄 Modification du Code Laravel

Si vous utilisez Authorization Code Flow, modifiez `AuthController.php` :

```php
public function login(LoginRequest $request): JsonResponse
{
    // Rediriger vers Authentik pour l'authentification
    return redirect()->route('authentik.redirect');
}

public function handleAuthentikCallback(Request $request): JsonResponse
{
    // Gérer le callback d'Authentik
    $user = Socialite::driver('authentik')->user();
    
    // Synchroniser avec la base locale
    // ...
    
    return response()->json([
        'success' => true,
        'access_token' => $this->generateAccessToken($user),
        'user' => $user
    ]);
}
```

---

## 🧪 Test du Nouveau Provider

### 1. Récupérer les Credentials

Après création du Provider :
- **Client ID** : Copiez-le
- **Client Secret** : Cliquez sur "Show" et copiez-le

### 2. Mettre à Jour .env

```env
AUTHENTIK_CLIENT_ID=votre_nouveau_client_id
AUTHENTIK_CLIENT_SECRET=votre_nouveau_client_secret
```

### 3. Vider le Cache

```bash
php artisan config:clear
```

### 4. Test

```bash
php test_simple.php
```

---

## 🔍 Vérification de la Version Authentik

Pour connaître votre version d'Authentik :

1. **Connectez-vous à Authentik**
2. **En bas de page** → Version info
3. **Ou** → **Admin Interface** → **System** → **Version**

**Versions récentes (2024+) :**
- L'option peut être dans "Grant Types" ou "OAuth2 Settings"

**Versions plus anciennes :**
- L'option est directement visible dans "Authorization Flow"

---

## 💡 Solution Alternative : Utiliser l'Interface Web

Si le Password Grant pose problème, vous pouvez :

1. **Créer une page de connexion** qui redirige vers Authentik
2. **Utiliser le callback** pour récupérer les tokens
3. **Stocker les tokens** dans le frontend

**Exemple Frontend :**

```javascript
// Redirection vers Authentik
const authUrl = '/api/auth/authentik/redirect';
window.location.href = authUrl;

// Après callback, récupérer les tokens
const urlParams = new URLSearchParams(window.location.search);
const code = urlParams.get('code');
```

---

## 🎯 Résumé

**Problème :** Password Grant non disponible dans votre version  
**Solution 1 :** Chercher dans "Grant Types" ou "Advanced Settings"  
**Solution 2 :** Créer un nouveau Provider  
**Solution 3 :** Utiliser Authorization Code Flow  

**Prochaine étape :** Créez un nouveau Provider et cherchez "Resource Owner Password Credentials" ou "Password Grant" dans les options avancées ! 🚀
