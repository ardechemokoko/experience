# 🚀 Frontend - Authorization Code Flow

## 📋 Comment utiliser l'API d'authentification

### 1. Inscription d'un utilisateur

```javascript
// POST /api/auth/register
const registerUser = async (userData) => {
  const response = await fetch('http://localhost:8000/api/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      email: userData.email,
      password: userData.password,
      password_confirmation: userData.password,
      nom: userData.nom,
      prenom: userData.prenom,
      contact: userData.contact,
      role: userData.role || 'candidat'
    })
  });

  const result = await response.json();
  
  if (result.success) {
    // Rediriger l'utilisateur vers Authentik pour se connecter
    window.location.href = result.auth_url;
  }
  
  return result;
};
```

### 2. Connexion (Authorization Code Flow)

```javascript
// GET /api/auth/auth-url
const getAuthUrl = async () => {
  const response = await fetch('http://localhost:8000/api/auth/auth-url');
  const result = await response.json();
  
  if (result.success) {
    // Rediriger l'utilisateur vers Authentik
    window.location.href = result.auth_url;
  }
  
  return result;
};

// L'utilisateur sera redirigé vers le callback automatiquement
```

### 3. Gestion du Callback

```javascript
// Le callback est automatiquement géré par Laravel
// L'utilisateur sera redirigé vers :
// http://localhost:8000/api/auth/authentik/callback

// Après connexion, récupérer les tokens depuis la réponse
const handleCallback = () => {
  // Les tokens sont automatiquement retournés par le callback
  // Stockez-les dans localStorage ou sessionStorage
};
```

### 4. Utilisation des Tokens

```javascript
// Utiliser le token pour les requêtes authentifiées
const makeAuthenticatedRequest = async (endpoint, data) => {
  const token = localStorage.getItem('access_token');
  
  const response = await fetch(`http://localhost:8000/api${endpoint}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(data)
  });
  
  return response.json();
};
```

### 5. Déconnexion

```javascript
// POST /api/auth/logout
const logout = async () => {
  const token = localStorage.getItem('access_token');
  
  const response = await fetch('http://localhost:8000/api/auth/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const result = await response.json();
  
  if (result.success) {
    // Supprimer les tokens du stockage local
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    
    // Rediriger vers la page de connexion
    window.location.href = '/login';
  }
  
  return result;
};
```

## 🔄 Flux Complet

### Étape 1 : Inscription
1. L'utilisateur remplit le formulaire d'inscription
2. Envoi de la requête POST `/api/auth/register`
3. L'API crée l'utilisateur dans Authentik et la base locale
4. Retour de l'URL d'authentification
5. Redirection automatique vers Authentik

### Étape 2 : Connexion
1. L'utilisateur se connecte sur Authentik
2. Authentik redirige vers le callback avec un code
3. Le callback échange le code contre des tokens
4. Retour des tokens à l'utilisateur
5. Stockage des tokens côté frontend

### Étape 3 : Utilisation
1. Les requêtes authentifiées utilisent le token Bearer
2. Le token est automatiquement renouvelé si nécessaire
3. Déconnexion révoque les tokens côté Authentik

## 📱 Exemple React

```jsx
import React, { useState, useEffect } from 'react';

const AuthComponent = () => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [user, setUser] = useState(null);

  useEffect(() => {
    // Vérifier si l'utilisateur est connecté
    const token = localStorage.getItem('access_token');
    if (token) {
      setIsAuthenticated(true);
      // Récupérer les infos utilisateur
      fetchUserInfo(token);
    }
  }, []);

  const registerUser = async (userData) => {
    try {
      const response = await fetch('http://localhost:8000/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userData)
      });

      const result = await response.json();
      
      if (result.success) {
        // Rediriger vers Authentik
        window.location.href = result.auth_url;
      }
    } catch (error) {
      console.error('Erreur inscription:', error);
    }
  };

  const loginUser = async () => {
    try {
      const response = await fetch('http://localhost:8000/api/auth/auth-url');
      const result = await response.json();
      
      if (result.success) {
        // Rediriger vers Authentik
        window.location.href = result.auth_url;
      }
    } catch (error) {
      console.error('Erreur connexion:', error);
    }
  };

  const logoutUser = async () => {
    try {
      const token = localStorage.getItem('access_token');
      
      await fetch('http://localhost:8000/api/auth/logout', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      localStorage.removeItem('access_token');
      localStorage.removeItem('refresh_token');
      setIsAuthenticated(false);
      setUser(null);
    } catch (error) {
      console.error('Erreur déconnexion:', error);
    }
  };

  if (isAuthenticated) {
    return (
      <div>
        <h1>Bienvenue, {user?.email} !</h1>
        <button onClick={logoutUser}>Se déconnecter</button>
      </div>
    );
  }

  return (
    <div>
      <h1>Auto-École</h1>
      <button onClick={loginUser}>Se connecter</button>
      <button onClick={() => registerUser({
        email: 'test@example.com',
        password: 'Password123!',
        nom: 'Test',
        prenom: 'User',
        contact: '0600000000',
        role: 'candidat'
      })}>S'inscrire</button>
    </div>
  );
};

export default AuthComponent;
```

## 🎯 Résumé

✅ **Inscription** : Crée l'utilisateur et retourne l'URL d'auth  
✅ **Connexion** : Redirection vers Authentik  
✅ **Callback** : Gestion automatique des tokens  
✅ **Authentification** : Utilisation des tokens Bearer  
✅ **Déconnexion** : Révocation des tokens  

**L'Authorization Code Flow est maintenant opérationnel !** 🚀
