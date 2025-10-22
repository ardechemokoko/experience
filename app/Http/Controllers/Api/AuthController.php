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

            // 5. Authentifier automatiquement l'utilisateur via Authentik
            $authResult = $this->authentikService->authenticateUser($request->email, $request->password);

            if ($authResult) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inscription réussie. Bienvenue !',
                    'user' => [
                        'id' => $utilisateur->id,
                        'email' => $utilisateur->email,
                        'role' => $utilisateur->role,
                    ],
                    'access_token' => $authResult['tokens']['access_token'],
                    'refresh_token' => $authResult['tokens']['refresh_token'] ?? null,
                    'token_type' => 'Bearer',
                    'expires_in' => $authResult['tokens']['expires_in'] ?? 3600,
                ], 201);
            }

            // Si l'authentification automatique échoue, retourner quand même un succès
            $token = $this->generateAccessToken($utilisateur);

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie. Veuillez vous connecter.',
                'user' => [
                    'id' => $utilisateur->id,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
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
     * Connexion (authentification via Authentik)
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // 1. Authentifier via Authentik
            $authResult = $this->authentikService->authenticateUser($request->email, $request->password);

            if (!$authResult) {
                Log::warning('Tentative de connexion échouée (Authentik)', [
                    'email' => $request->email,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects. Veuillez vérifier votre email et mot de passe.',
                ], 401);
            }

            // 2. Récupérer ou créer l'utilisateur dans notre DB
            $utilisateur = Utilisateur::where('email', $request->email)->first();

            if (!$utilisateur) {
                // Si l'utilisateur existe dans Authentik mais pas dans notre DB, le créer
                DB::beginTransaction();
                try {
                    $utilisateur = Utilisateur::create([
                        'email' => $request->email,
                        'password' => Hash::make(Str::random(32)),
                        'role' => $authResult['user']['attributes']['role'][0] ?? 'candidat',
                    ]);

                    Personne::create([
                        'utilisateur_id' => $utilisateur->id,
                        'nom' => $authResult['user']['family_name'] ?? '',
                        'prenom' => $authResult['user']['given_name'] ?? $authResult['user']['name'] ?? '',
                        'email' => $request->email,
                        'contact' => $authResult['user']['attributes']['contact'][0] ?? '',
                        'adresse' => $authResult['user']['attributes']['adresse'][0] ?? '',
                    ]);

                    DB::commit();

                    Log::info('Utilisateur synchronisé depuis Authentik', [
                        'user_id' => $utilisateur->id,
                        'email' => $request->email
                    ]);
                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            Log::info('Connexion réussie via Authentik', [
                'user_id' => $utilisateur->id,
                'email' => $utilisateur->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie. Bienvenue !',
                'user' => [
                    'id' => $utilisateur->id,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                ],
                'access_token' => $authResult['tokens']['access_token'],
                'refresh_token' => $authResult['tokens']['refresh_token'] ?? null,
                'token_type' => 'Bearer',
                'expires_in' => $authResult['tokens']['expires_in'] ?? 3600,
            ]);

        } catch (Exception $e) {
            Log::error('Erreur lors de la connexion', [
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
     * Déconnexion
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Si vous utilisez Laravel Sanctum, invalidez le token actuel
            // $request->user()->currentAccessToken()->delete();
            
            Log::info('Déconnexion utilisateur', [
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie. À bientôt !',
            ]);

        } catch (Exception $e) {
            Log::error('Erreur lors de la déconnexion', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion.',
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
