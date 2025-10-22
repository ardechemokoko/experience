# 🎨 Guide d'Intégration Frontend - API Auto-École

## 📡 Configuration de Base

### URL de l'API
```javascript
const API_BASE_URL = 'http://localhost:8000/api';
```

---

## 🔐 Authentification avec Authentik (OAuth)

### 1. Service d'Authentification (React/Vue/Angular)

```javascript
// services/auth.service.js

class AuthService {
  constructor() {
    this.API_URL = 'http://localhost:8000/api';
    this.TOKEN_KEY = 'auth_token';
    this.USER_KEY = 'user_data';
  }

  /**
   * Obtenir l'URL d'authentification Authentik
   */
  async getAuthentikAuthUrl() {
    try {
      const response = await fetch(`${this.API_URL}/auth/authentik/redirect`);
      const data = await response.json();
      
      if (data.success) {
        return data.auth_url;
      }
      throw new Error(data.message);
    } catch (error) {
      console.error('Erreur lors de la récupération de l\'URL d\'auth:', error);
      throw error;
    }
  }

  /**
   * Rediriger vers Authentik pour l'authentification
   */
  async loginWithAuthentik() {
    const authUrl = await this.getAuthentikAuthUrl();
    window.location.href = authUrl;
  }

  /**
   * Gérer le callback après authentification Authentik
   * À appeler sur la page de callback
   */
  async handleAuthentikCallback() {
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get('code');
    const state = urlParams.get('state');

    if (!code) {
      throw new Error('Code d\'autorisation manquant');
    }

    try {
      const response = await fetch(
        `${this.API_URL}/auth/authentik/callback?code=${code}&state=${state}`
      );
      const data = await response.json();

      if (data.success) {
        this.saveToken(data.access_token);
        this.saveUser(data.user);
        return data.user;
      }
      throw new Error(data.message);
    } catch (error) {
      console.error('Erreur lors du callback Authentik:', error);
      throw error;
    }
  }

  /**
   * Inscription locale
   */
  async register(userData) {
    try {
      const response = await fetch(`${this.API_URL}/auth/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData),
      });

      const data = await response.json();

      if (data.success) {
        this.saveToken(data.access_token);
        this.saveUser(data.user);
        return data.user;
      }
      throw new Error(data.message);
    } catch (error) {
      console.error('Erreur lors de l\'inscription:', error);
      throw error;
    }
  }

  /**
   * Connexion locale
   */
  async login(email, password) {
    try {
      const response = await fetch(`${this.API_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();

      if (data.success) {
        this.saveToken(data.access_token);
        this.saveUser(data.user);
        return data.user;
      }
      throw new Error(data.message);
    } catch (error) {
      console.error('Erreur lors de la connexion:', error);
      throw error;
    }
  }

  /**
   * Obtenir les informations de l'utilisateur connecté
   */
  async getCurrentUser() {
    const token = this.getToken();
    
    if (!token) {
      throw new Error('Non authentifié');
    }

    try {
      const response = await fetch(`${this.API_URL}/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });

      const data = await response.json();

      if (data.success) {
        this.saveUser(data.user);
        return data.user;
      }
      throw new Error(data.message);
    } catch (error) {
      console.error('Erreur lors de la récupération de l\'utilisateur:', error);
      throw error;
    }
  }

  /**
   * Déconnexion
   */
  async logout() {
    const token = this.getToken();
    
    try {
      await fetch(`${this.API_URL}/auth/logout`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error);
    } finally {
      this.removeToken();
      this.removeUser();
    }
  }

  /**
   * Sauvegarder le token
   */
  saveToken(token) {
    localStorage.setItem(this.TOKEN_KEY, token);
  }

  /**
   * Récupérer le token
   */
  getToken() {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  /**
   * Supprimer le token
   */
  removeToken() {
    localStorage.removeItem(this.TOKEN_KEY);
  }

  /**
   * Sauvegarder les données utilisateur
   */
  saveUser(user) {
    localStorage.setItem(this.USER_KEY, JSON.stringify(user));
  }

  /**
   * Récupérer les données utilisateur
   */
  getUser() {
    const user = localStorage.getItem(this.USER_KEY);
    return user ? JSON.parse(user) : null;
  }

  /**
   * Supprimer les données utilisateur
   */
  removeUser() {
    localStorage.removeItem(this.USER_KEY);
  }

  /**
   * Vérifier si l'utilisateur est connecté
   */
  isAuthenticated() {
    return !!this.getToken();
  }
}

export default new AuthService();
```

---

## 🚀 Utilisation dans React

### Composant de Connexion

```jsx
// components/Login.jsx
import React, { useState } from 'react';
import AuthService from '../services/auth.service';

function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLocalLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await AuthService.login(email, password);
      window.location.href = '/dashboard'; // Rediriger après connexion
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleAuthentikLogin = async () => {
    try {
      await AuthService.loginWithAuthentik();
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <div className="login-container">
      <h2>Connexion</h2>

      {error && <div className="alert alert-error">{error}</div>}

      {/* Connexion avec Authentik */}
      <button 
        onClick={handleAuthentikLogin}
        className="btn btn-primary btn-block"
      >
        Se connecter avec Authentik (SSO)
      </button>

      <div className="divider">OU</div>

      {/* Connexion locale */}
      <form onSubmit={handleLocalLogin}>
        <div className="form-group">
          <label>Email</label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            className="form-control"
          />
        </div>

        <div className="form-group">
          <label>Mot de passe</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            className="form-control"
          />
        </div>

        <button 
          type="submit" 
          disabled={loading}
          className="btn btn-secondary btn-block"
        >
          {loading ? 'Connexion...' : 'Se connecter'}
        </button>
      </form>

      <p className="text-center mt-3">
        Pas encore de compte ? <a href="/register">S'inscrire</a>
      </p>
    </div>
  );
}

export default Login;
```

### Composant d'Inscription

```jsx
// components/Register.jsx
import React, { useState } from 'react';
import AuthService from '../services/auth.service';

function Register() {
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    password_confirmation: '',
    nom: '',
    prenom: '',
    contact: '',
    adresse: '',
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await AuthService.register(formData);
      window.location.href = '/dashboard';
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="register-container">
      <h2>Inscription</h2>

      {error && <div className="alert alert-error">{error}</div>}

      <form onSubmit={handleSubmit}>
        <div className="form-row">
          <div className="form-group col-md-6">
            <label>Nom</label>
            <input
              type="text"
              name="nom"
              value={formData.nom}
              onChange={handleChange}
              required
              className="form-control"
            />
          </div>

          <div className="form-group col-md-6">
            <label>Prénom</label>
            <input
              type="text"
              name="prenom"
              value={formData.prenom}
              onChange={handleChange}
              required
              className="form-control"
            />
          </div>
        </div>

        <div className="form-group">
          <label>Email</label>
          <input
            type="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            required
            className="form-control"
          />
        </div>

        <div className="form-group">
          <label>Contact</label>
          <input
            type="tel"
            name="contact"
            value={formData.contact}
            onChange={handleChange}
            required
            className="form-control"
          />
        </div>

        <div className="form-group">
          <label>Adresse</label>
          <textarea
            name="adresse"
            value={formData.adresse}
            onChange={handleChange}
            className="form-control"
          />
        </div>

        <div className="form-row">
          <div className="form-group col-md-6">
            <label>Mot de passe</label>
            <input
              type="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              required
              minLength="8"
              className="form-control"
            />
          </div>

          <div className="form-group col-md-6">
            <label>Confirmer le mot de passe</label>
            <input
              type="password"
              name="password_confirmation"
              value={formData.password_confirmation}
              onChange={handleChange}
              required
              minLength="8"
              className="form-control"
            />
          </div>
        </div>

        <button 
          type="submit" 
          disabled={loading}
          className="btn btn-primary btn-block"
        >
          {loading ? 'Inscription...' : 'S\'inscrire'}
        </button>
      </form>

      <p className="text-center mt-3">
        Déjà un compte ? <a href="/login">Se connecter</a>
      </p>
    </div>
  );
}

export default Register;
```

### Page de Callback Authentik

```jsx
// pages/AuthentikCallback.jsx
import React, { useEffect, useState } from 'react';
import AuthService from '../services/auth.service';

function AuthentikCallback() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const handleCallback = async () => {
      try {
        await AuthService.handleAuthentikCallback();
        // Rediriger vers le dashboard après authentification
        window.location.href = '/dashboard';
      } catch (err) {
        setError(err.message);
        setLoading(false);
      }
    };

    handleCallback();
  }, []);

  if (loading) {
    return (
      <div className="callback-loading">
        <div className="spinner"></div>
        <p>Authentification en cours...</p>
      </div>
    );
  }

  return (
    <div className="callback-error">
      <h2>Erreur d'authentification</h2>
      <p>{error}</p>
      <a href="/login" className="btn btn-primary">
        Retour à la connexion
      </a>
    </div>
  );
}

export default AuthentikCallback;
```

### Guard de Route Protégée

```jsx
// components/ProtectedRoute.jsx
import React from 'react';
import { Navigate } from 'react-router-dom';
import AuthService from '../services/auth.service';

function ProtectedRoute({ children }) {
  const isAuthenticated = AuthService.isAuthenticated();

  if (!isAuthenticated) {
    return <Navigate to="/login" />;
  }

  return children;
}

export default ProtectedRoute;
```

### Configuration des Routes

```jsx
// App.jsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Login from './components/Login';
import Register from './components/Register';
import AuthentikCallback from './pages/AuthentikCallback';
import Dashboard from './pages/Dashboard';
import ProtectedRoute from './components/ProtectedRoute';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/auth/authentik/callback" element={<AuthentikCallback />} />
        
        <Route 
          path="/dashboard" 
          element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          } 
        />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
```

---

## 🔧 Configuration CORS

Pour permettre les requêtes depuis votre frontend, ajoutez dans `.env` :

```env
FRONTEND_URL=http://localhost:3000
```

Et configurez CORS dans `config/cors.php` (si nécessaire).

---

## 📱 Utilisation dans Vue.js

```javascript
// store/auth.js (Vuex)
import AuthService from '@/services/auth.service';

export default {
  state: {
    user: AuthService.getUser(),
    isAuthenticated: AuthService.isAuthenticated(),
  },
  
  mutations: {
    SET_USER(state, user) {
      state.user = user;
      state.isAuthenticated = true;
    },
    
    LOGOUT(state) {
      state.user = null;
      state.isAuthenticated = false;
    },
  },
  
  actions: {
    async login({ commit }, credentials) {
      const user = await AuthService.login(credentials.email, credentials.password);
      commit('SET_USER', user);
    },
    
    async loginWithAuthentik({ commit }) {
      await AuthService.loginWithAuthentik();
    },
    
    async logout({ commit }) {
      await AuthService.logout();
      commit('LOGOUT');
    },
  },
};
```

---

## 🎯 Intercepteur Axios (optionnel)

```javascript
// services/axios.service.js
import axios from 'axios';
import AuthService from './auth.service';

const axiosInstance = axios.create({
  baseURL: 'http://localhost:8000/api',
});

// Ajouter le token à chaque requête
axiosInstance.interceptors.request.use(
  (config) => {
    const token = AuthService.getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Gérer les erreurs d'authentification
axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      AuthService.logout();
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default axiosInstance;
```

---

## 📝 Notes Importantes

1. **Sécurité** : Ne stockez jamais d'informations sensibles dans `localStorage` en production
2. **HTTPS** : Utilisez toujours HTTPS en production
3. **Tokens** : Implémentez une stratégie de rafraîchissement des tokens
4. **CORS** : Configurez correctement les origines autorisées

---

**Bon développement ! 🚀**

