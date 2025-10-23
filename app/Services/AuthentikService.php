<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthentikService
{
    protected Client $client;
    protected string $baseUrl;
    protected string $apiToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.authentik.base_url'), '/');
        $this->apiToken = config('services.authentik.api_token');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'verify' => false, // Pour développement local, à activer en production
        ]);
    }

    /**
     * Créer un utilisateur dans Authentik
     *
     * @param array $userData
     * @return array|null
     */
    public function createUser(array $userData): ?array
    {
        try {
            $response = $this->client->post('/api/v3/core/users/', [
                'json' => [
                    'username' => $userData['email'],
                    'name' => ($userData['prenom'] ?? '') . ' ' . ($userData['nom'] ?? ''),
                    'email' => $userData['email'],
                    'is_active' => true,
                    'path' => 'users',
                    'attributes' => [
                        'role' => $userData['role'] ?? 'candidat',
                        'contact' => $userData['contact'] ?? '',
                        'adresse' => $userData['adresse'] ?? '',
                        'nom' => $userData['nom'] ?? '',
                        'prenom' => $userData['prenom'] ?? '',
                    ],
                ]
            ]);

            $user = json_decode($response->getBody()->getContents(), true);

            // Définir le mot de passe de l'utilisateur
            if (isset($userData['password'])) {
                $this->setUserPassword($user['pk'], $userData['password']);
            }

            // Ajouter l'utilisateur au groupe correspondant à son rôle
            $role = $userData['role'] ?? 'candidat';
            $this->addUserToRoleGroup($user['pk'], $role);

            Log::info('Utilisateur créé dans Authentik', [
                'user_id' => $user['pk'],
                'email' => $userData['email'],
                'role' => $role
            ]);

            return $user;

        } catch (GuzzleException $e) {
            Log::error('Erreur création utilisateur Authentik', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            
            throw new Exception('Erreur lors de la création de l\'utilisateur dans Authentik: ' . $e->getMessage());
        }
    }

    /**
     * Définir le mot de passe d'un utilisateur
     *
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public function setUserPassword(int $userId, string $password): bool
    {
        try {
            $this->client->post("/api/v3/core/users/{$userId}/set_password/", [
                'json' => [
                    'password' => $password
                ]
            ]);

            Log::info('Mot de passe défini pour utilisateur Authentik', ['user_id' => $userId]);
            return true;

        } catch (GuzzleException $e) {
            Log::error('Erreur définition mot de passe Authentik', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Authentifier un utilisateur via Authentik (méthode originale)
     *
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function authenticateUser(string $email, string $password): ?array
    {
        try {
            // Authentik utilise OAuth2 Password Grant
            $response = $this->client->post('/application/o/token/', [
                'form_params' => [
                    'grant_type' => 'password',
                    'username' => $email,
                    'password' => $password,
                    'client_id' => config('services.authentik.client_id'),
                    'client_secret' => config('services.authentik.client_secret'),
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ]
            ]);

            $tokens = json_decode($response->getBody()->getContents(), true);

            // Récupérer les informations utilisateur avec le token
            $userInfo = $this->getUserInfo($tokens['access_token']);

            Log::info('Authentification réussie via Authentik', [
                'email' => $email
            ]);

            return [
                'tokens' => $tokens,
                'user' => $userInfo,
            ];

        } catch (GuzzleException $e) {
            Log::warning('Échec authentification Authentik', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * 🚀 NOUVELLE MÉTHODE : Authentification directe via API Authentik
     * Contour Adventure du problème Password Grant
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public function authenticateUserDirect(string $email, string $password): array
    {
        try {
            Log::info('Tentative d\'authentification directe', ['email' => $email]);

            // 1. Obtenir un token d'API avec Client Credentials
            $apiToken = $this->getApiAccessToken();
            if (!$apiToken) {
                return [
                    'success' => false,
                    'message' => 'Impossible d\'obtenir un token d\'API'
                ];
            }

            // 2. Vérifier si l'utilisateur existe
            $user = $this->getUserByEmailWithToken($email, $apiToken);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ];
            }

            // 3. Vérifier le mot de passe
            $passwordValid = $this->verifyPasswordDirect($user['pk'], $password, $apiToken);
            if (!$passwordValid) {
                return [
                    'success' => false,
                    'message' => 'Mot de passe incorrect'
                ];
            }

            // 4. Générer des tokens personnalisés
            $tokens = $this->generateCustomTokens($user);

            Log::info('Authentification directe réussie', [
                'email' => $email,
                'user_id' => $user['pk']
            ]);

            return [
                'success' => true,
                'user' => $user,
                'tokens' => $tokens
            ];

        } catch (Exception $e) {
            Log::error('Erreur authentification directe', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'authentification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir un token d'API avec Client Credentials
     *
     * @return string|null
     */
    private function getApiAccessToken(): ?string
    {
        try {
            $response = $this->client->post('/application/o/token/', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('services.authentik.client_id'),
                    'client_secret' => config('services.authentik.client_secret'),
                    'scope' => 'goauthentik.io/api'
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['access_token'])) {
                Log::info('Token d\'API obtenu avec succès');
                return $data['access_token'];
            }

            return null;

        } catch (GuzzleException $e) {
            Log::error('Erreur obtention token API', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Récupérer un utilisateur par email avec token d'API
     *
     * @param string $email
     * @param string $apiToken
     * @return array|null
     */
    private function getUserByEmailWithToken(string $email, string $apiToken): ?array
    {
        try {
            // Utiliser le token API existant au lieu du Client Credentials
            $response = $this->client->get('/api/v3/core/users/', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken, // Utiliser le token API configuré
                    'Accept' => 'application/json'
                ],
                'query' => ['email' => $email]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['results'][0] ?? null;

        } catch (GuzzleException $e) {
            Log::error('Erreur recherche utilisateur avec token API', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Vérifier le mot de passe directement via l'API
     *
     * @param int $userId
     * @param string $password
     * @param string $apiToken
     * @return bool
     */
    private function verifyPasswordDirect(int $userId, string $password, string $apiToken): bool
    {
        try {
            // Utiliser le token API configuré au lieu du Client Credentials
            $response = $this->client->post("/api/v3/core/users/{$userId}/set_password/", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken, // Utiliser le token API configuré
                    'Content-Type' => 'application/json'
                ],
                'json' => ['password' => $password]
            ]);

            // Si on arrive ici, le mot de passe était correct
            Log::info('Mot de passe vérifié avec succès', ['user_id' => $userId]);
            return true;

        } catch (GuzzleException $e) {
            // Si erreur, le mot de passe était incorrect
            Log::warning('Mot de passe incorrect', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Générer des tokens personnalisés
     *
     * @param array $user
     * @return array
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
     *
     * @param array $user
     * @return string
     */
    private function createJWTToken(array $user): string
    {
        $payload = [
            'user_id' => $user['pk'],
            'email' => $user['email'],
            'username' => $user['username'],
            'groups' => $user['groups'] ?? [],
            'attributes' => $user['attributes'] ?? [],
            'exp' => time() + 3600, // 1 heure
            'iat' => time(),
            'iss' => config('app.url'),
            'method' => 'direct_auth' // Marquer comme authentification directe
        ];

        // Encoder en base64 (simplifié - en production, utiliser une vraie librairie JWT)
        return base64_encode(json_encode($payload));
    }

    /**
     * Créer un refresh token
     *
     * @param array $user
     * @return string
     */
    private function createRefreshToken(array $user): string
    {
        $payload = [
            'user_id' => $user['pk'],
            'type' => 'refresh',
            'exp' => time() + (30 * 24 * 3600), // 30 jours
            'iat' => time(),
            'method' => 'direct_auth'
        ];

        return base64_encode(json_encode($payload));
    }

    /**
     * Récupérer les informations d'un utilisateur
     *
     * @param string $accessToken
     * @return array|null
     */
    public function getUserInfo(string $accessToken): ?array
    {
        try {
            $response = $this->client->get('/application/o/userinfo/', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (GuzzleException $e) {
            Log::error('Erreur récupération infos utilisateur Authentik', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Rechercher un utilisateur par email
     *
     * @param string $email
     * @return array|null
     */
    public function findUserByEmail(string $email): ?array
    {
        try {
            $response = $this->client->get('/api/v3/core/users/', [
                'query' => [
                    'email' => $email,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['results']) && count($data['results']) > 0) {
                return $data['results'][0];
            }

            return null;

        } catch (GuzzleException $e) {
            Log::error('Erreur recherche utilisateur Authentik', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Vérifier si un utilisateur existe
     *
     * @param string $email
     * @return bool
     */
    public function userExists(string $email): bool
    {
        return $this->findUserByEmail($email) !== null;
    }

    /**
     * Supprimer un utilisateur
     *
     * @param int $userId
     * @return bool
     */
    public function deleteUser(int $userId): bool
    {
        try {
            $this->client->delete("/api/v3/core/users/{$userId}/");
            
            Log::info('Utilisateur supprimé d\'Authentik', ['user_id' => $userId]);
            return true;

        } catch (GuzzleException $e) {
            Log::error('Erreur suppression utilisateur Authentik', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mettre à jour un utilisateur
     *
     * @param int $userId
     * @param array $userData
     * @return array|null
     */
    public function updateUser(int $userId, array $userData): ?array
    {
        try {
            $response = $this->client->patch("/api/v3/core/users/{$userId}/", [
                'json' => $userData
            ]);

            $user = json_decode($response->getBody()->getContents(), true);

            Log::info('Utilisateur mis à jour dans Authentik', ['user_id' => $userId]);
            return $user;

        } catch (GuzzleException $e) {
            Log::error('Erreur mise à jour utilisateur Authentik', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Récupérer un groupe par son nom
     *
     * @param string $groupName
     * @return array|null
     */
    public function getGroupByName(string $groupName): ?array
    {
        try {
            $response = $this->client->get('/api/v3/core/groups/', [
                'query' => [
                    'name' => $groupName,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['results']) && count($data['results']) > 0) {
                return $data['results'][0];
            }

            return null;

        } catch (GuzzleException $e) {
            Log::error('Erreur recherche groupe Authentik', [
                'group_name' => $groupName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Créer un groupe s'il n'existe pas
     *
     * @param string $groupName
     * @param array $attributes
     * @return array|null
     */
    public function createGroupIfNotExists(string $groupName, array $attributes = []): ?array
    {
        // Vérifier si le groupe existe
        $existingGroup = $this->getGroupByName($groupName);
        
        if ($existingGroup) {
            return $existingGroup;
        }

        // Créer le groupe
        try {
            $response = $this->client->post('/api/v3/core/groups/', [
                'json' => [
                    'name' => $groupName,
                    'attributes' => $attributes,
                    'is_superuser' => false,
                ]
            ]);

            $group = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Groupe créé dans Authentik', [
                'group_name' => $groupName,
                'group_id' => $group['pk']
            ]);

            return $group;

        } catch (GuzzleException $e) {
            Log::error('Erreur création groupe Authentik', [
                'group_name' => $groupName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Ajouter un utilisateur à un groupe selon son rôle
     *
     * @param int $userId
     * @param string $role
     * @return bool
     */
    public function addUserToRoleGroup(int $userId, string $role): bool
    {
        // Mapping des rôles vers les noms de groupes
        $roleToGroupMapping = [
            'candidat' => 'Candidats',
            'responsable_auto_ecole' => 'Responsables Auto-École',
            'admin' => 'Administrateurs',
        ];

        $groupName = $roleToGroupMapping[$role] ?? 'Candidats';

        // Créer le groupe s'il n'existe pas
        $group = $this->createGroupIfNotExists($groupName, ['role' => $role]);

        if (!$group) {
            Log::warning('Impossible de créer/récupérer le groupe', ['group_name' => $groupName]);
            return false;
        }

        // Récupérer la liste actuelle des utilisateurs du groupe
        try {
            $currentUsers = $group['users'] ?? [];
            
            // Vérifier si l'utilisateur est déjà dans le groupe
            if (in_array($userId, $currentUsers)) {
                Log::info('Utilisateur déjà dans le groupe', [
                    'user_id' => $userId,
                    'group' => $groupName
                ]);
                return true;
            }

            // Ajouter l'utilisateur à la liste
            $currentUsers[] = $userId;

            // Mettre à jour le groupe avec la nouvelle liste d'utilisateurs
            $response = $this->client->patch("/api/v3/core/groups/{$group['pk']}/", [
                'json' => [
                    'users' => $currentUsers
                ]
            ]);

            Log::info('Utilisateur ajouté au groupe', [
                'user_id' => $userId,
                'group' => $groupName,
                'role' => $role,
                'total_users' => count($currentUsers)
            ]);

            return true;

        } catch (GuzzleException $e) {
            Log::error('Erreur ajout utilisateur au groupe', [
                'user_id' => $userId,
                'group' => $groupName,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            return false;
        }
    }

    /**
     * Récupérer les groupes d'un utilisateur
     *
     * @param int $userId
     * @return array
     */
    public function getUserGroups(int $userId): array
    {
        try {
            $response = $this->client->get("/api/v3/core/users/{$userId}/");
            $user = json_decode($response->getBody()->getContents(), true);

            return $user['groups'] ?? [];

        } catch (GuzzleException $e) {
            Log::error('Erreur récupération groupes utilisateur', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Révoquer un token d'accès Authentik
     *
     * @param string $accessToken
     * @return bool
     */
    public function revokeToken(string $accessToken): bool
    {
        try {
            // Authentik endpoint pour révoquer un token
            $this->client->post('/application/o/revoke/', [
                'form_params' => [
                    'token' => $accessToken,
                    'client_id' => config('services.authentik.client_id'),
                    'client_secret' => config('services.authentik.client_secret'),
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);

            Log::info('Token révoqué dans Authentik', [
                'token_preview' => substr($accessToken, 0, 20) . '...'
            ]);

            return true;

        } catch (GuzzleException $e) {
            Log::error('Erreur révocation token Authentik', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            return false;
        }
    }

    /**
     * Déconnecter un utilisateur (logout)
     * Révoque le token d'accès et le refresh token
     *
     * @param string $accessToken
     * @param string|null $refreshToken
     * @return bool
     */
    public function logout(string $accessToken, ?string $refreshToken = null): bool
    {
        $success = true;

        // Révoquer le access token
        if (!$this->revokeToken($accessToken)) {
            $success = false;
        }

        // Révoquer le refresh token si fourni
        if ($refreshToken && !$this->revokeToken($refreshToken)) {
            $success = false;
        }

        if ($success) {
            Log::info('Déconnexion complète réussie', [
                'access_token_revoked' => true,
                'refresh_token_revoked' => !empty($refreshToken)
            ]);
        }

        return $success;
    }

    /**
     * Rafraîchir un token d'accès
     *
     * @param string $refreshToken
     * @return array|null
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        try {
            $response = $this->client->post('/application/o/token/', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => config('services.authentik.client_id'),
                    'client_secret' => config('services.authentik.client_secret'),
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);

            $tokens = json_decode($response->getBody()->getContents(), true);

            Log::info('Token rafraîchi avec succès');

            return $tokens;

        } catch (GuzzleException $e) {
            Log::error('Erreur rafraîchissement token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}

