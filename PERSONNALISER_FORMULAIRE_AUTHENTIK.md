# 🎨 Personnaliser le Formulaire Authentik par Provider

## 🎯 Objectif

Personnaliser l'interface de connexion d'Authentik pour chaque provider (par exemple, différents formulaires pour candidats, responsables d'auto-école, administrateurs).

---

## 📋 Méthodes de Personnalisation

### 1. 🎨 Personnalisation par Flows (Recommandé)

#### Principe
Créer des **Flows** personnalisés pour chaque type d'utilisateur et les associer à des Providers spécifiques.

#### Étapes

**Étape 1 : Créer des Flows personnalisés**

```
Authentik → Admin Interface → Flows → Create
```

**Flow pour Candidats :**
```
Name: Auto-Ecole-Candidat-Login
Title: Connexion Candidat
Slug: auto-ecole-candidat-login

Designer:
┌─────────────────────────────────────┐
│ 🔐 Connexion Candidat Auto-École   │
│                                     │
│ 📧 Email: [________________]        │
│ 🔒 Mot de passe: [____________]     │
│                                     │
│ [Se connecter] [S'inscrire]         │
│                                     │
│ 🚗 Votre permis de conduire        │
│    vous attend !                    │
└─────────────────────────────────────┘
```

**Flow pour Responsables Auto-École :**
```
Name: Auto-Ecole-Responsable-Login
Title: Connexion Responsable
Slug: auto-ecole-responsable-login

Designer:
┌─────────────────────────────────────┐
│ 🏢 Espace Responsable Auto-École   │
│                                     │
│ 📧 Email: [________________]        │
│ 🔒 Mot de passe: [____________]     │
│                                     │
│ [Se connecter]                      │
│                                     │
│ 📊 Gérer vos candidats             │
│ 📈 Suivre les formations           │
└─────────────────────────────────────┘
```

**Flow pour Administrateurs :**
```
Name: Auto-Ecole-Admin-Login
Title: Connexion Administrateur
Slug: auto-ecole-admin-login

Designer:
┌─────────────────────────────────────┐
│ ⚙️ Administration Auto-École       │
│                                     │
│ 📧 Email: [________________]        │
│ 🔒 Mot de passe: [____________]     │
│                                     │
│ [Se connecter]                      │
│                                     │
│ 🛠️ Gestion du système              │
│ 📊 Statistiques globales           │
└─────────────────────────────────────┘
```

#### Étape 2 : Associer les Flows aux Providers

**Pour chaque Provider :**
```
Applications → Providers → [Votre Provider] → Edit

Authentication flow: Sélectionner le Flow correspondant
```

---

### 2. 🎯 Personnalisation par Application

#### Principe
Créer des **Applications** distinctes avec des Flows différents.

#### Configuration

**Application Candidats :**
```
Applications → Applications → Create

Name: Auto-Ecole-Candidats
Slug: auto-ecole-candidats
Provider: [Provider OAuth pour candidats]
Authentication flow: Auto-Ecole-Candidat-Login

Branding:
- Logo: logo-auto-ecole-candidat.png
- Favicon: favicon-candidat.ico
- Primary color: #50C786 (vert)
- Background: image-auto-ecole.jpg
```

**Application Responsables :**
```
Applications → Applications → Create

Name: Auto-Ecole-Responsables
Slug: auto-ecole-responsables
Provider: [Provider OAuth pour responsables]
Authentication flow: Auto-Ecole-Responsable-Login

Branding:
- Logo: logo-auto-ecole-responsable.png
- Favicon: favicon-responsable.ico
- Primary color: #2563EB (bleu)
- Background: image-bureau.jpg
```

**Application Admin :**
```
Applications → Applications → Create

Name: Auto-Ecole-Admin
Slug: auto-ecole-admin
Provider: [Provider OAuth pour admin]
Authentication flow: Auto-Ecole-Admin-Login

Branding:
- Logo: logo-auto-ecole-admin.png
- Favicon: favicon-admin.ico
- Primary color: #DC2626 (rouge)
- Background: image-admin.jpg
```

---

### 3. 🎨 Personnalisation CSS/JS Avancée

#### Principe
Utiliser des **Custom CSS** et **JavaScript** pour personnaliser l'apparence.

#### Configuration

**Dans chaque Flow :**
```
Flows → [Votre Flow] → Edit → Designer

Custom CSS:
```css
/* Personnalisation pour candidats */
.pf-c-login {
    background: linear-gradient(135deg, #50C786, #2E8B57);
}

.pf-c-login__main-header {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 20px;
}

.pf-c-form__group-label {
    color: #ffffff;
    font-weight: bold;
}

.pf-c-button.pf-m-primary {
    background: #2E8B57;
    border-color: #2E8B57;
}

.pf-c-button.pf-m-primary:hover {
    background: #228B22;
}
```

**Custom JavaScript:**
```javascript
// Animation pour candidats
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.pf-c-form');
    if (form) {
        form.style.animation = 'slideIn 0.5s ease-in-out';
    }
    
    // Ajouter un message personnalisé
    const header = document.querySelector('.pf-c-login__main-header h1');
    if (header) {
        header.innerHTML = '🚗 Votre permis vous attend !';
    }
});
```

---

### 4. 🔧 Configuration dans votre API Laravel

#### Modifier AuthController pour gérer différents Providers

```php
// app/Http/Controllers/Api/AuthController.php

public function getAuthUrl(Request $request): JsonResponse
{
    try {
        $userType = $request->query('type', 'candidat'); // candidat, responsable, admin
        
        // Sélectionner le Provider selon le type d'utilisateur
        $provider = $this->getProviderByUserType($userType);
        
        $authUrl = Socialite::driver($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'success' => true,
            'auth_url' => $authUrl,
            'user_type' => $userType,
            'message' => "Redirection vers l'espace {$userType}."
        ]);
    } catch (Exception $e) {
        // Gestion d'erreur...
    }
}

private function getProviderByUserType(string $userType): string
{
    return match($userType) {
        'candidat' => 'authentik-candidat',
        'responsable' => 'authentik-responsable', 
        'admin' => 'authentik-admin',
        default => 'authentik'
    };
}
```

#### Configuration des Providers dans config/services.php

```php
// config/services.php

return [
    // ... autres services

    'authentik-candidat' => [
        'client_id' => env('AUTHENTIK_CANDIDAT_CLIENT_ID'),
        'client_secret' => env('AUTHENTIK_CANDIDAT_CLIENT_SECRET'),
        'redirect' => env('AUTHENTIK_CANDIDAT_REDIRECT_URI'),
    ],

    'authentik-responsable' => [
        'client_id' => env('AUTHENTIK_RESPONSABLE_CLIENT_ID'),
        'client_secret' => env('AUTHENTIK_RESPONSABLE_CLIENT_SECRET'),
        'redirect' => env('AUTHENTIK_RESPONSABLE_REDIRECT_URI'),
    ],

    'authentik-admin' => [
        'client_id' => env('AUTHENTIK_ADMIN_CLIENT_ID'),
        'client_secret' => env('AUTHENTIK_ADMIN_CLIENT_SECRET'),
        'redirect' => env('AUTHENTIK_ADMIN_REDIRECT_URI'),
    ],
];
```

---

### 5. 🎯 Utilisation depuis le Frontend

#### Différentes URLs selon le type d'utilisateur

```javascript
// Frontend - Sélection du type de connexion

const loginCandidat = async () => {
    const response = await fetch('http://localhost:8000/api/auth/auth-url?type=candidat');
    const result = await response.json();
    
    if (result.success) {
        window.location.href = result.auth_url;
    }
};

const loginResponsable = async () => {
    const response = await fetch('http://localhost:8000/api/auth/auth-url?type=responsable');
    const result = await response.json();
    
    if (result.success) {
        window.location.href = result.auth_url;
    }
};

const loginAdmin = async () => {
    const response = await fetch('http://localhost:8000/api/auth/auth-url?type=admin');
    const result = await response.json();
    
    if (result.success) {
        window.location.href = result.auth_url;
    }
};
```

#### Interface de sélection

```html
<!DOCTYPE html>
<html>
<head>
    <title>Auto-École - Connexion</title>
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .login-option {
            margin: 20px 0;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .login-option:hover {
            border-color: #50C786;
            background-color: #f0fdf4;
        }
        
        .candidat { border-left: 4px solid #50C786; }
        .responsable { border-left: 4px solid #2563EB; }
        .admin { border-left: 4px solid #DC2626; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🚗 Auto-École</h1>
        <p>Choisissez votre espace de connexion :</p>
        
        <div class="login-option candidat" onclick="loginCandidat()">
            <h3>👤 Candidat</h3>
            <p>Accédez à votre espace candidat pour suivre votre formation</p>
        </div>
        
        <div class="login-option responsable" onclick="loginResponsable()">
            <h3>🏢 Responsable Auto-École</h3>
            <p>Gérez vos candidats et formations</p>
        </div>
        
        <div class="login-option admin" onclick="loginAdmin()">
            <h3>⚙️ Administrateur</h3>
            <p>Administration du système</p>
        </div>
    </div>
    
    <script>
        // Scripts de connexion...
    </script>
</body>
</html>
```

---

## 🎯 Recommandation

**Pour votre cas, je recommande la Méthode 1 (Flows personnalisés)** car :

✅ **Simple à implémenter**  
✅ **Pas besoin de plusieurs Providers**  
✅ **Personnalisation complète de l'interface**  
✅ **Facile à maintenir**  

### Étapes à suivre :

1. **Créer 3 Flows** dans Authentik (candidat, responsable, admin)
2. **Personnaliser l'interface** de chaque Flow
3. **Modifier votre API** pour gérer les différents types
4. **Créer l'interface de sélection** côté frontend

**Voulez-vous que je vous guide pour créer le premier Flow personnalisé ?** 🚀
