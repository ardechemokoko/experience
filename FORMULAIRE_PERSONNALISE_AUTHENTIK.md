# 🎨 Formulaire Complètement Personnalisé Authentik

## 🎯 Objectif

Créer un formulaire de connexion **100% personnalisé** avec vos propres champs, design et validation.

---

## 🚀 Solution : Custom Flow avec Formulaire Personnalisé

### 1. 🎨 Créer un Flow Personnalisé

#### Dans Authentik Admin Interface

```
Flows → Create

Name: Auto-Ecole-Form-Personnalise
Title: Connexion Auto-École
Slug: auto-ecole-form-personnalise
```

#### Designer du Flow

```
┌─────────────────────────────────────────────────────┐
│ 🚗 AUTO-ÉCOLE - CONNEXION PERSONNALISÉE            │
│                                                     │
│ ┌─────────────────────────────────────────────────┐ │
│ │ 🔐 Espace Personnel                            │ │
│ │                                                 │ │
│ │ Type de compte:                                │ │
│ │ ○ Candidat   ○ Responsable   ○ Administrateur  │ │
│ │                                                 │ │
│ │ 📧 Email: [_____________________________]      │ │
│ │ 🔒 Mot de passe: [_____________________]       │ │
│ │                                                 │ │
│ │ ☑ Se souvenir de moi                          │ │
│ │                                                 │ │
│ │ [Se connecter]    [Mot de passe oublié?]      │ │
│ │                                                 │ │
│ │ Pas encore de compte? [S'inscrire ici]        │ │
│ └─────────────────────────────────────────────────┘ │
│                                                     │
│ 📞 Besoin d'aide? Contactez-nous au 01 23 45 67 89 │
└─────────────────────────────────────────────────────┘
```

### 2. 🔧 Configuration du Flow

#### Étapes du Flow

```
1. Prompt Stage (Sélection du type de compte)
   ├─ Field Name: account_type
   ├─ Field Label: Type de compte
   ├─ Placeholder: Choisissez votre type de compte
   └─ Required: true

2. Prompt Stage (Email)
   ├─ Field Name: email
   ├─ Field Label: Adresse email
   ├─ Placeholder: votre.email@example.com
   ├─ Type: Email
   └─ Required: true

3. Prompt Stage (Mot de passe)
   ├─ Field Name: password
   ├─ Field Label: Mot de passe
   ├─ Type: Password
   └─ Required: true

4. Prompt Stage (Se souvenir)
   ├─ Field Name: remember_me
   ├─ Field Label: Se souvenir de moi
   ├─ Type: Checkbox
   └─ Required: false

5. User Login Stage
   ├─ Session Duration: 30 days (si remember_me)
   └─ Session Duration: 1 day (sinon)
```

### 3. 🎨 CSS Personnalisé Complet

```css
/* Auto-École Formulaire Personnalisé */
.pf-c-login {
    background: linear-gradient(135deg, #50C786 0%, #2E8B57 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.pf-c-login__main {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.pf-c-login__main-header {
    background: linear-gradient(135deg, #50C786, #2E8B57);
    color: white;
    padding: 30px;
    border-radius: 20px 20px 0 0;
    text-align: center;
}

.pf-c-login__main-header h1 {
    font-size: 2.5rem;
    margin: 0;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.pf-c-login__main-body {
    padding: 40px;
}

/* Formulaire personnalisé */
.pf-c-form {
    max-width: 400px;
    margin: 0 auto;
}

.pf-c-form__group {
    margin-bottom: 25px;
}

.pf-c-form__group-label {
    font-weight: 600;
    color: #2D3748;
    margin-bottom: 8px;
    font-size: 1.1rem;
}

/* Champs personnalisés */
.pf-c-form-control {
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    padding: 15px 20px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #F7FAFC;
}

.pf-c-form-control:focus {
    border-color: #50C786;
    box-shadow: 0 0 0 3px rgba(80, 199, 134, 0.1);
    background: white;
}

/* Type de compte - Radio buttons personnalisés */
.account-type-group {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
    margin-top: 10px;
}

.account-type-option {
    position: relative;
    cursor: pointer;
}

.account-type-option input[type="radio"] {
    display: none;
}

.account-type-label {
    display: block;
    padding: 15px;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    text-align: center;
    background: #F7FAFC;
    transition: all 0.3s ease;
    font-weight: 500;
}

.account-type-option input[type="radio"]:checked + .account-type-label {
    border-color: #50C786;
    background: #F0FDF4;
    color: #2E8B57;
    font-weight: 600;
}

/* Boutons personnalisés */
.pf-c-button {
    border-radius: 12px;
    padding: 15px 30px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    text-transform: none;
}

.pf-c-button.pf-m-primary {
    background: linear-gradient(135deg, #50C786, #2E8B57);
    border: none;
    box-shadow: 0 4px 15px rgba(80, 199, 134, 0.3);
}

.pf-c-button.pf-m-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(80, 199, 134, 0.4);
}

.pf-c-button.pf-m-secondary {
    background: transparent;
    border: 2px solid #50C786;
    color: #50C786;
}

.pf-c-button.pf-m-secondary:hover {
    background: #50C786;
    color: white;
}

/* Checkbox personnalisé */
.remember-me-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
}

.remember-me-checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid #50C786;
    border-radius: 4px;
    cursor: pointer;
}

/* Liens personnalisés */
.pf-c-login__main-body a {
    color: #50C786;
    text-decoration: none;
    font-weight: 500;
}

.pf-c-login__main-body a:hover {
    color: #2E8B57;
    text-decoration: underline;
}

/* Footer personnalisé */
.login-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #E2E8F0;
    color: #718096;
    font-size: 0.9rem;
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.pf-c-form {
    animation: slideIn 0.6s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .account-type-group {
        grid-template-columns: 1fr;
    }
    
    .pf-c-login__main-body {
        padding: 20px;
    }
    
    .pf-c-login__main-header h1 {
        font-size: 2rem;
    }
}
```

### 4. 🎭 JavaScript Personnalisé

```javascript
// Auto-École Formulaire Personnalisé
document.addEventListener('DOMContentLoaded', function() {
    
    // Animation d'entrée
    const form = document.querySelector('.pf-c-form');
    if (form) {
        form.style.opacity = '0';
        form.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            form.style.transition = 'all 0.6s ease-out';
            form.style.opacity = '1';
            form.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // Gestion du type de compte
    const accountTypeOptions = document.querySelectorAll('.account-type-option input[type="radio"]');
    accountTypeOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Mettre à jour l'interface selon le type sélectionné
            updateInterfaceForAccountType(this.value);
        });
    });
    
    // Validation en temps réel
    const emailField = document.querySelector('input[name="email"]');
    if (emailField) {
        emailField.addEventListener('input', validateEmail);
    }
    
    const passwordField = document.querySelector('input[name="password"]');
    if (passwordField) {
        passwordField.addEventListener('input', validatePassword);
    }
    
    // Soumission du formulaire
    const loginForm = document.querySelector('form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleFormSubmission);
    }
    
    // Bouton "Mot de passe oublié"
    const forgotPasswordBtn = document.querySelector('.forgot-password');
    if (forgotPasswordBtn) {
        forgotPasswordBtn.addEventListener('click', handleForgotPassword);
    }
});

function updateInterfaceForAccountType(accountType) {
    const header = document.querySelector('.pf-c-login__main-header h1');
    const body = document.querySelector('.pf-c-login__main-body');
    
    switch(accountType) {
        case 'candidat':
            header.innerHTML = '🚗 Espace Candidat';
            body.style.borderLeft = '4px solid #50C786';
            break;
        case 'responsable':
            header.innerHTML = '🏢 Espace Responsable';
            body.style.borderLeft = '4px solid #2563EB';
            break;
        case 'admin':
            header.innerHTML = '⚙️ Espace Administrateur';
            body.style.borderLeft = '4px solid #DC2626';
            break;
    }
}

function validateEmail() {
    const email = this.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        this.style.borderColor = '#E53E3E';
        showFieldError(this, 'Format d\'email invalide');
    } else {
        this.style.borderColor = '#50C786';
        hideFieldError(this);
    }
}

function validatePassword() {
    const password = this.value;
    
    if (password && password.length < 6) {
        this.style.borderColor = '#E53E3E';
        showFieldError(this, 'Le mot de passe doit contenir au moins 6 caractères');
    } else {
        this.style.borderColor = '#50C786';
        hideFieldError(this);
    }
}

function showFieldError(field, message) {
    hideFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#E53E3E';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function hideFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

function handleFormSubmission(event) {
    event.preventDefault();
    
    const accountType = document.querySelector('input[name="account_type"]:checked')?.value;
    const email = document.querySelector('input[name="email"]')?.value;
    const password = document.querySelector('input[name="password"]')?.value;
    const rememberMe = document.querySelector('input[name="remember_me"]')?.checked;
    
    // Validation
    if (!accountType || !email || !password) {
        showNotification('Veuillez remplir tous les champs obligatoires', 'error');
        return;
    }
    
    // Animation de chargement
    const submitBtn = document.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Connexion...';
    submitBtn.disabled = true;
    
    // Simuler l'envoi (remplacer par votre logique)
    setTimeout(() => {
        submitBtn.textContent submitting...';
        submitBtn.disabled = false;
        
        // Redirection vers l'API Laravel
        window.location.href = `/api/auth/auth-url?type=${accountType}`;
    }, 1500);
}

function handleForgotPassword() {
    const email = document.querySelector('input[name="email"]')?.value;
    
    if (!email) {
        showNotification('Veuillez saisir votre email d\'abord', 'warning');
        return;
    }
    
    // Logique de mot de passe oublié
    showNotification('Un email de réinitialisation a été envoyé', 'success');
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideInRight 0.3s ease-out;
        max-width: 300px;
    `;
    
    switch(type) {
        case 'success':
            notification.style.background = '#50C786';
            break;
        case 'error':
            notification.style.background = '#E53E3E';
            break;
        case 'warning':
            notification.style.background = '#F6AD55';
            break;
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 4000);
}
```

### 5. 🎯 Configuration dans Authentik

#### Prompt Stages à créer :

```
1. Account Type Selection
   ├─ Name: account_type
   ├─ Label: Type de compte
   ├─ Type: Radio
   ├─ Choices: candidat|Responsable|admin
   └─ Required: true

2. Email Field
   ├─ Name: email
   ├─ Label: Adresse email
   ├─ Type: Email
   ├─ Placeholder: votre.email@example.com
   └─ Required: true

3. Password Field
   ├─ Name: password
   ├─ Label: Mot de passe
   ├─ Type: Password
   └─ Required: true

4. Remember Me
   ├─ Name: remember_me
   ├─ Label: Se souvenir de moi
   ├─ Type: Checkbox
   └─ Required: false
```

### 6. 🔧 Intégration avec votre API Laravel

```php
// app/Http/Controllers/Api/AuthController.php

public function handleAuthentikCallback(Request $request): JsonResponse
{
    try {
        $authentikUser = Socialite::driver('authentik')->stateless()->user();
        
        // Récupérer les données du formulaire personnalisé
        $accountType = $request->session()->get('account_type');
        $rememberMe = $request->session()->get('remember_me');
        
        // Logique selon le type de compte...
        
        return response()->json([
            'success' => true,
            'user' => $user,
            'account_type' => $accountType,
            'remember_me' => $rememberMe
        ]);
        
    } catch (Exception $e) {
        // Gestion d'erreur...
    }
}
```

---

## 🎯 Résultat Final

Vous aurez un formulaire **complètement personnalisé** avec :

✅ **Design unique** pour votre auto-école  
✅ **Sélection du type de compte** (candidat/responsable/admin)  
✅ **Validation en temps réel**  
✅ **Animations fluides**  
✅ **Responsive design**  
✅ **Intégration avec votre API Laravel**  

**Voulez-vous que je vous guide pour créer ce formulaire personnalisé étape par étape ?** 🚀
