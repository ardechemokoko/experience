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
     * Authentifier un utilisateur via Authentik
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
}

