<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Utilisateur;
use App\Models\Personne;
use App\Services\AuthentikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class AuthController extends Controller
{
    protected AuthentikService $authentikService;

    public function __construct(AuthentikService $authentikService)
    {
        $this->authentikService = $authentikService;
    }

    /**
     * Rediriger vers Authentik pour l'authentification OAuth
     * 
     * @return JsonResponse
     */
    public function redirectToAuthentik(): JsonResponse
    {
        try {
            $authUrl = Socialite::driver('authentik')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl,
                'message' => 'Redirigez l\'utilisateur vers cette URL pour s\'authentifier.'
            ]);
        } catch (Exception $e) {
            Log::error('Erreur génération URL Authentik', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de l\'URL d\'authentification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * Callback après authentification Authentik
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function handleAuthentikCallback(Request $request): JsonResponse
    {
        try {
            // Récupérer les informations de l'utilisateur depuis Authentik
            $authentikUser = Socialite::driver('authentik')
                ->stateless()
                ->user();

            if (!$authentikUser || !$authentikUser->getEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de récupérer les informations utilisateur depuis Authentik.'
                ], 400);
            }

            DB::beginTransaction();
            
            try {
                // Chercher ou créer l'utilisateur dans la base
                $utilisateur = Utilisateur::where('email', $authentikUser->getEmail())->first();

                if (!$utilisateur) {
                    // Créer un nouvel utilisateur
                    $utilisateur = Utilisateur::create([
                        'email' => $authentikUser->getEmail(),
                        'password' => Hash::make(Str::random(32)), // Mot de passe aléatoire
                        'role' => 'candidat', // Rôle par défaut
                    ]);

                    // Créer la personne associée
                    Personne::create([
                        'utilisateur_id' => $utilisateur->id,
                        'nom' => $authentikUser->user['family_name'] ?? 'Non renseigné',
                        'prenom' => $authentikUser->user['given_name'] ?? $authentikUser->getName(),
                        'email' => $authentikUser->getEmail(),
                        'contact' => $authentikUser->user['phone_number'] ?? '',
                    ]);

                    Log::info('Nouvel utilisateur créé via Authentik', [
                        'user_id' => $utilisateur->id,
                        'email' => $utilisateur->email
                    ]);
                }

                DB::commit();

                // Générer un token d'accès
                $token = $this->generateAccessToken($utilisateur);

                return response()->json([
                    'success' => true,
                    'message' => 'Authentification réussie.',
                    'user' => [
                        'id' => $utilisateur->id,
                        'email' => $utilisateur->email,
                        'role' => $utilisateur->role,
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Erreur callback Authentik', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'authentification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * Inscription (avec création dans Authentik)
     * 
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // 1. Vérifier si l'utilisateur existe déjà dans Authentik
            if ($this->authentikService->userExists($request->email)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet email est déjà utilisé.',
                    'errors' => [
                        'email' => ['Cette adresse email est déjà utilisée.']
                    ]
                ], 422);
            }

            // 2. Créer l'utilisateur dans Authentik
            $authentikUser = $this->authentikService->createUser([
                'email' => $request->email,
                'password' => $request->password,
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'contact' => $request->contact,
                'adresse' => $request->adresse,
                'role' => $request->role ?? 'candidat',
            ]);

            if (!$authentikUser) {
                throw new Exception('Impossible de créer l\'utilisateur dans Authentik');
            }

            // 3. Créer l'utilisateur dans notre DB (sans stocker le mot de passe)
            $utilisateur = Utilisateur::create([
                'email' => $request->email,
                'password' => Hash::make(Str::random(32)), // Mot de passe aléatoire (non utilisé)
                'role' => $request->role ?? 'candidat',
            ]);

            // 4. Créer la personne associée
            Personne::create([
                'utilisateur_id' => $utilisateur->id,
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'adresse' => $request->adresse,
                'contact' => $request->contact,
            ]);

            DB::commit();

            Log::info('Nouvelle inscription réussie (Authentik + DB)', [
                'user_id' => $utilisateur->id,
                'email' => $utilisateur->email,
                'role' => $utilisateur->role,
                'authentik_pk' => $authentikUser['pk'] ?? null
            ]);

            // 5. Retourner l'URL d'authentification pour que l'utilisateur se connecte
            $authUrl = Socialite::driver('authentik')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie. Redirigez l\'utilisateur vers Authentik pour se connecter.',
                'user' => [
                    'id' => $utilisateur->id,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                ],
                'auth_url' => $authUrl,
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de l\'inscription', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue lors de la création du compte.'
            ], 500);
        }
    }

    /**
     * 🚀 NOUVELLE MÉTHODE : Connexion directe avec contournement Password Grant
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function loginDirect(LoginRequest $request): JsonResponse
    {
        try {
            Log::info('Tentative de connexion directe', [
                'email' => $request->email,
                'ip' => $request->ip()
            ]);

            // Utiliser la nouvelle méthode de contournement
            $result = $this->authentikService->authenticateUserDirect(
                $request->email,
                $request->password
            );

            if ($result['success']) {
                // Synchroniser avec la base locale
                $user = $this->synchronizeUserFromAuthentik($result['user']);

                Log::info('Connexion directe réussie', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'method' => 'direct_auth'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Connexion réussie !',
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                    'access_token' => $result['tokens']['access_token'],
                    'refresh_token' => $result['tokens']['refresh_token'],
                    'token_type' => $result['tokens']['token_type'],
                    'expires_in' => $result['tokens']['expires_in'],
                    'method' => 'direct_auth'
                ]);
            }

            Log::warning('Connexion directe échouée', [
                'email' => $request->email,
                'reason' => $result['message']
            ]);

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 401);

        } catch (Exception $e) {
            Log::error('Erreur lors de la connexion directe', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * Synchroniser un utilisateur Authentik avec la base locale
     * 
     * @param array $authentikUser
     * @return Utilisateur
     */
    private function synchronizeUserFromAuthentik(array $authentikUser): Utilisateur
    {
        $user = Utilisateur::where('email', $authentikUser['email'])->first();

        if (!$user) {
            // Créer l'utilisateur s'il n'existe pas
            $user = Utilisateur::create([
                'email' => $authentikUser['email'],
                'password' => Hash::make(Str::random(32)), // Mot de passe aléatoire
                'role' => $authentikUser['attributes']['role'][0] ?? 'candidat',
            ]);

            // Créer la personne associée
            Personne::create([
                'utilisateur_id' => $user->id,
                'nom' => $authentikUser['attributes']['nom'][0] ?? 'Non renseigné',
                'prenom' => $authentikUser['attributes']['prenom'][0] ?? $authentikUser['name'],
                'email' => $authentikUser['email'],
                'contact' => $authentikUser['attributes']['contact'][0] ?? '',
                'adresse' => $authentikUser['attributes']['adresse'][0] ?? '',
            ]);

            Log::info('Utilisateur créé via synchronisation Authentik', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }

        return $user;
    }

    /**
     * Obtenir l'URL d'authentification Authentik (pour Authorization Code Flow)
     * 
     * @return JsonResponse
     */
    public function getAuthUrl(): JsonResponse
    {
        try {
            $authUrl = Socialite::driver('authentik')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl,
                'message' => 'Redirigez l\'utilisateur vers cette URL pour s\'authentifier.'
            ]);
        } catch (Exception $e) {
            Log::error('Erreur génération URL Authentik', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de l\'URL d\'authentification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * Déconnexion - Révoque le token Authentik
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Récupérer le token depuis le header Authorization
            $authHeader = $request->header('Authorization');
            
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token d\'authentification manquant.',
                ], 401);
            }

            // Extraire le token
            $accessToken = substr($authHeader, 7); // Enlever "Bearer "

            // Récupérer le refresh token depuis le body (optionnel)
            $refreshToken = $request->input('refresh_token');

            // Révoquer les tokens côté Authentik
            $revoked = $this->authentikService->logout($accessToken, $refreshToken);

            if ($revoked) {
                Log::info('Déconnexion utilisateur réussie', [
                    'ip' => $request->ip(),
                    'access_token_revoked' => true,
                    'refresh_token_revoked' => !empty($refreshToken)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Déconnexion réussie. À bientôt !',
                ]);
            } else {
                // Même si la révocation échoue, on considère la déconnexion côté client
                Log::warning('Révocation token partielle', [
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Déconnexion effectuée. Le token a peut-être déjà expiré.',
                ]);
            }

        } catch (Exception $e) {
            Log::error('Erreur lors de la déconnexion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * Rafraîchir le token d'accès
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->input('refresh_token');

            if (!$refreshToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le refresh token est obligatoire.',
                ], 400);
            }

            // Rafraîchir le token via Authentik
            $newTokens = $this->authentikService->refreshAccessToken($refreshToken);

            if (!$newTokens) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de rafraîchir le token. Veuillez vous reconnecter.',
                ], 401);
            }

            Log::info('Token rafraîchi avec succès');

            return response()->json([
                'success' => true,
                'message' => 'Token rafraîchi avec succès.',
                'access_token' => $newTokens['access_token'],
                'refresh_token' => $newTokens['refresh_token'] ?? $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $newTokens['expires_in'] ?? 3600,
            ]);

        } catch (Exception $e) {
            Log::error('Erreur rafraîchissement token', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement du token.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $utilisateur = $request->user();

            if (!$utilisateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié. Veuillez vous connecter.',
                ], 401);
            }

            // Charger la relation personne avec ses données
            $utilisateur->load('personne');

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $utilisateur->id,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                    'personne' => $utilisateur->personne ? [
                        'id' => $utilisateur->personne->id,
                        'nom' => $utilisateur->personne->nom,
                        'prenom' => $utilisateur->personne->prenom,
                        'email' => $utilisateur->personne->email,
                        'contact' => $utilisateur->personne->contact,
                        'adresse' => $utilisateur->personne->adresse,
                    ] : null,
                    'created_at' => $utilisateur->created_at,
                    'updated_at' => $utilisateur->updated_at,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération du profil', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations utilisateur.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * Générer un token d'accès simple (à remplacer par Sanctum ou JWT)
     * 
     * @param Utilisateur $utilisateur
     * @return string
     */
    private function generateAccessToken(Utilisateur $utilisateur): string
    {
        // Pour l'instant, générer un token simple
        // TODO: Implémenter Laravel Sanctum ou JWT pour de vrais tokens sécurisés
        
        // Format: user_id|random_string|timestamp|hash
        $payload = [
            'user_id' => $utilisateur->id,
            'random' => Str::random(60),
            'timestamp' => time(),
            'role' => $utilisateur->role,
        ];
        
        $token = base64_encode(json_encode($payload));
        
        Log::info('Token généré', [
            'user_id' => $utilisateur->id,
            'role' => $utilisateur->role
        ]);
        
        return $token;
    }
}
