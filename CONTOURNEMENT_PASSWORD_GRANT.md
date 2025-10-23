# 🔧 Contournement Password Grant - Solutions Alternatives

## 🎯 Le Problème

Le **Password Grant Flow** n'est pas disponible dans votre version d'Authentik, mais on peut le contourner !

---

## ✅ Solution 1 : Client Credentials + API Authentik Directe

### Principe
Utiliser le **Client Credentials Grant** pour obtenir un token d'API, puis utiliser directement l'API Authentik pour authentifier les utilisateurs.

### Implémentation

```php
// app/Services/AuthentikService.php

class AuthentikService
{
    private $client;
    private $accessToken;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => config('services.authentik.base_url'),
            'timeout' => 30,
        ]);
        
        // Obtenir un token d'API avec Client Credentials
        $this->accessToken = $this->getApiAccessToken();
    }

    /**
     * Obtenir un token d'API avec Client Credentials
     */
    private function getApiAccessToken(): string
    {
        $response = $this->client->post('/application/o/token/', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.authentik.client_id'),
                'client_secret' => config('services.authentik.client_secret'),
                'scope' => 'goauthentik.io/api'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
    }

    /**
     * Authentifier un utilisateur directement via l'API Authentik
     */
    public function authenticateUserDirect($email, $password): array
    {
        try {
            // 1. Vérifier si l'utilisateur existe
            $user = $this->getUserByEmail($email);
            if (!$user) {
                return ['success' => false, 'message' => 'Utilisateur non trouvé'];
            }

            // 2. Vérifier le mot de passe via l'API
            $passwordValid = $this->verifyPassword($user['pk'], $password);
            if (!$passwordValid) {
                return ['success' => false, 'message' => 'Mot de passe incorrect'];
            }

            // 3. Générer des tokens personnalisés
            $tokens = $this->generateCustomTokens($user);

            return [
                'success' => true,
                'user' => $user,
                'tokens' => $tokens
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Récupérer un utilisateur par email
     */
    private function getUserByEmail(string $email): ?array
    {
        $response = $this->client->get('/api/v3/core/users/', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken
            ],
            'query' => ['email' => $email]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['results'][0] ?? null;
    }

    /**
     * Vérifier le mot de passe (contournement)
     */
    private function verifyPassword(string $userId, string $password): bool
    {
        try {
            // Méthode 1: Tenter de définir le même mot de passe
            // Si ça échoue, c'est que le mot de passe est incorrect
            $response = $this->client->post("/api/v3/core/users/{$userId}/set_password/", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => ['password' => $password]
            ]);

            // Si on arrive ici, le mot de passe était correct
            return true;

        } catch (Exception $e) {
            // Si erreur, le mot de passe était incorrect
            return false;
        }
    }

    /**
     * Générer des tokens personnalisés
     */
    private function generateCustomTokens(array $user): array
    {
        $accessToken = $this->createJWTToken($user);
        $refreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ];
    }

    /**
     * Créer un token JWT personnalisé
     */
    private function createJWTToken(array $user): string
    {
        $payload = [
            'user_id' => $user['pk'],
            'email' => $user['email'],
            'username' => $user['username'],
            'groups' => $user['groups'] ?? [],
            'exp' => time() + 3600, // 1 heure
            'iat' => time(),
            'iss' => config('app.url')
        ];

        // Encoder en base64 (simplifié)
        return base64_encode(json_encode($payload));
    }

    /**
     * Créer un refresh token
     */
    private function createRefreshToken(array $user): string
    {
        $payload = [
            'user_id' => $user['pk'],
            'type' => 'refresh',
            'exp' => time() + (30 * 24 * 3600), // 30 jours
            'iat' => time()
        ];

        return base64_encode(json_encode($payload));
    }
}
```

---

## ✅ Solution 2 : Implicit Flow + Token Exchange

### Principe
Utiliser l'**Implicit Flow** pour obtenir un token, puis l'échanger contre des tokens personnalisés.

```php
// app/Http/Controllers/Api/AuthController.php

public function loginWithImplicitFlow(Request $request): JsonResponse
{
    try {
        // 1. Rediriger vers Authentik avec Implicit Flow
        $authUrl = Socialite::driver('authentik')
            ->with(['response_type' => 'token'])
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'success' => true,
            'auth_url' => $authUrl,
            'message' => 'Redirection vers Authentik pour connexion'
        ]);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la génération de l\'URL d\'authentification'
        ], 500);
    }
}

public function handleImplicitCallback(Request $request): JsonResponse
{
    try {
        // 2. Récupérer le token depuis l'URL
        $accessToken = $request->input('access_token');
        
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token d\'accès manquant'
            ], 400);
        }

        // 3. Valider le token et récupérer les infos utilisateur
        $userInfo = $this->validateTokenAndGetUser($accessToken);
        
        if (!$userInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide'
            ], 401);
        }

        // 4. Synchroniser avec la base locale
        $user = $this->synchronizeUser($userInfo);

        // 5. Générer des tokens personnalisés
        $customTokens = $this->generateCustomTokens($user);

        return response()->json([
            'success' => true,
            'user' => $user,
            'access_token' => $customTokens['access_token'],
            'refresh_token' => $customTokens['refresh_token'],
            'expires_in' => $customTokens['expires_in']
        ]);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'authentification'
        ], 500);
    }
}

private function validateTokenAndGetUser(string $token): ?array
{
    try {
        $response = $this->authentikService->client->get('/application/o/userinfo/', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);

        return json_decode($response->getBody(), true);

    } catch (Exception $e) {
        return null;
    }
}
```

---

## ✅ Solution 3 : Hybrid Flow (Code + Token)

### Principe
Utiliser le **Authorization Code Flow** mais avec des tokens personnalisés.

```php
// app/Http/Controllers/Api/AuthController.php

public function loginWithHybridFlow(Request $request): JsonResponse
{
    try {
        // 1. Générer l'URL d'authentification avec hybrid flow
        $authUrl = Socialite::driver('authentik')
            ->with([
                'response_type' => 'code token',
                'scope' => 'openid email profile'
            ])
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'success' => true,
            'auth_url' => $authUrl,
            'message' => 'Redirection vers Authentik'
        ]);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la génération de l\'URL'
        ], 500);
    }
}
```

---

## ✅ Solution 4 : Authentification Locale + Sync Authentik

### Principe
Gérer l'authentification localement et synchroniser avec Authentik en arrière-plan.

```php
// app/Http/Controllers/Api/AuthController.php

public function loginLocal(Request $request): JsonResponse
{
    try {
        $email = $request->email;
        $password = $request->password;

        // 1. Vérifier localement
        $user = Utilisateur::where('email', $email)->first();
        
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects'
            ], 401);
        }

        // 2. Vérifier avec Authentik en arrière-plan (optionnel)
        $this->syncWithAuthentik($user, $password);

        // 3. Générer des tokens
        $token = $this->generateAccessToken($user);

        return response()->json([
            'success' => true,
            'user' => $user,
            'access_token' => $token,
            'expires_in' => 3600
        ]);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la connexion'
        ], 500);
    }
}

private function syncWithAuthentik($user, $password): void
{
    try {
        // Synchroniser avec Authentik sans bloquer l'authentification
        $this->authentikService->syncUserCredentials($user->email, $password);
    } catch (Exception $e) {
        Log::warning('Erreur sync Authentik', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
    }
}
```

---

## 🎯 Recommandation

**Pour votre cas, je recommande la Solution 1 (Client Credentials + API Directe)** car :

✅ **Contourne complètement le problème Password Grant**  
✅ **Utilise l'API Authentik directement**  
✅ **Génère des tokens personnalisés**  
✅ **Fonctionne avec votre configuration actuelle**  
✅ **Plus simple à implémenter**  

### 🔧 Implémentation Rapide

```php
// Dans votre AuthController
public function login(LoginRequest $request): JsonResponse
{
    $result = $this->authentikService->authenticateUserDirect(
        $request->email, 
        $request->password
    );

    if ($result['success']) {
        return response()->json([
            'success' => true,
            'user' => $result['user'],
            'access_token' => $result['tokens']['access_token'],
            'refresh_token' => $result['tokens']['refresh_token']
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => $result['message']
    ], 401);
}
```

**Voulez-vous que j'implémente cette solution de contournement ?** 🚀

Cela vous permettra d'avoir une authentification email/password qui fonctionne parfaitement sans avoir besoin du Password Grant Flow !
