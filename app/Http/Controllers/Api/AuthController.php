<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Personne;
use App\Models\Utilisateur;
use App\Services\AuthentikService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * @OA\Info(
 *     title="🚗 Auto-École API - Authentification Authentik",
 *     version="1.0.0",
 *     description="API complète pour la gestion de l'authentification avec Authentik comme IAM central. Cette API permet l'inscription, la connexion, la gestion des utilisateurs et des tokens avec un système de contournement du Password Grant Flow.",
 *     @OA\Contact(
 *         name="Support Auto-École API",
 *         email="support@auto-ecole-api.com",
 *         url="https://auto-ecole-api.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Serveur de développement local"
 * )
 * 
 * @OA\Server(
 *     url="https://9c8r7bbvybn.preview.infomaniak.website",
 *     description="Serveur de production Infomaniak"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token d'accès JWT obtenu via l'authentification"
 * )
 * 
 * @OA\Tag(
 *     name="🔐 Authentification",
 *     description="Endpoints d'authentification avec Authentik"
 * )
 * 
 * @OA\Tag(
 *     name="🔄 Tokens",
 *     description="Gestion des tokens d'accès et de rafraîchissement"
 * )
 */
class AuthController extends Controller
{
    protected AuthentikService $authentikService;

    public function __construct(AuthentikService $authentikService)
    {
        $this->authentikService = $authentikService;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     operationId="registerUser",
     *     tags={"🔐 Authentification"},
     *     summary="📝 Inscription d'un nouvel utilisateur",
     *     description="Crée un nouvel utilisateur dans Authentik et dans la base de données locale. Retourne l'URL d'authentification pour que l'utilisateur puisse se connecter.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password","password_confirmation","nom","prenom","contact","role"},
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="contact", type="string", example="0612345678"),
     *             @OA\Property(property="adresse", type="string", example="123 Rue de la Paix, Paris", nullable=true),
     *             @OA\Property(property="role", type="string", enum={"candidat","responsable_auto_ecole","admin"}, example="candidat")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="✅ Inscription réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inscription réussie. Redirigez l'utilisateur vers Authentik pour se connecter."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string", example="019a0e63-a1cd-7012-9586-57868bb66c6f"),
     *                 @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                 @OA\Property(property="role", type="string", example="candidat"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-23T00:00:00+00:00"),
     *                 @OA\Property(property="personne", type="object",
     *                     @OA\Property(property="id", type="string", example="019a0e63-a1cd-7012-9586-57868bb66c6f"),
     *                     @OA\Property(property="nom", type="string", example="Dupont"),
     *                     @OA\Property(property="prenom", type="string", example="Jean"),
     *                     @OA\Property(property="nom_complet", type="string", example="Jean Dupont"),
     *                     @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                     @OA\Property(property="contact", type="string", example="0612345678"),
     *                     @OA\Property(property="adresse", type="string", example="123 Rue de la Paix, Paris")
     *                 )
     *             ),
     *             @OA\Property(property="auth_url", type="string", example="http://5.189.156.115:31015/application/o/authorize/?client_id=..."),
     *             @OA\Property(property="authentik", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=28),
     *                 @OA\Property(property="username", type="string", example="jean.dupont@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="❌ Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(response=500, description="❌ Erreur serveur")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            Log::info('Tentative d\'inscription', [
                'email' => $request->email,
                'role' => $request->role
            ]);

            // Créer l'utilisateur dans Authentik
            $authentikUser = $this->authentikService->createUser([
                'email' => $request->email,
                'password' => $request->password,
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'contact' => $request->contact,
                'adresse' => $request->adresse,
                'role' => $request->role,
            ]);

            // Créer l'utilisateur dans la base locale
            $user = Utilisateur::create([
                'email' => $request->email,
                'password' => Hash::make(Str::random(32)),
                'role' => $request->role,
            ]);

            // Créer la personne associée
            Personne::create([
                'utilisateur_id' => $user->id,
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'contact' => $request->contact,
                'adresse' => $request->adresse,
            ]);

            DB::commit();

            $authUrl = Socialite::driver('authentik')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            // Recharger l'utilisateur avec ses relations
            $user->load('personne');

            Log::info('Nouvelle inscription réussie (Authentik + DB)', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'authentik_pk' => $authentikUser['pk']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie. Redirigez l\'utilisateur vers Authentik pour se connecter.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at->toIso8601String(),
                    'personne' => [
                        'id' => $user->personne->id,
                        'nom' => $user->personne->nom,
                        'prenom' => $user->personne->prenom,
                        'nom_complet' => $user->personne->prenom . ' ' . $user->personne->nom,
                        'email' => $user->personne->email,
                        'contact' => $user->personne->contact,
                        'adresse' => $user->personne->adresse,
                    ]
                ],
                'auth_url' => $authUrl,
                'authentik' => [
                    'user_id' => $authentikUser['pk'],
                    'username' => $authentikUser['username'],
                ],
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
     * @OA\Post(
     *     path="/api/auth/login-direct",
     *     operationId="loginDirect",
     *     tags={"🔐 Authentification"},
     *     summary="🚀 Connexion directe (Contournement Password Grant)",
     *     description="Authentifie un utilisateur directement via l'API Authentik en contournant le problème du Password Grant Flow. Cette méthode utilise l'API Authentik directement pour vérifier les identifiants et génère des tokens personnalisés.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie !"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string", example="019a0e34-d153-7330-8cb6-80b14fd8811c"),
     *                 @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                 @OA\Property(property="role", type="string", example="candidat"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-23T00:00:00+00:00"),
     *                 @OA\Property(property="personne", type="object",
     *                     @OA\Property(property="id", type="string", example="019a0e34-d153-7330-8cb6-80b14fd8811c"),
     *                     @OA\Property(property="nom", type="string", example="Dupont"),
     *                     @OA\Property(property="prenom", type="string", example="Jean"),
     *                     @OA\Property(property="nom_complet", type="string", example="Jean Dupont"),
     *                     @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                     @OA\Property(property="contact", type="string", example="0612345678"),
     *                     @OA\Property(property="adresse", type="string", example="123 Rue de la Paix, Paris")
     *                 )
     *             ),
     *             @OA\Property(property="access_token", type="string", example="eyJ1c2VyX2lkIjoyOCwiZW1haWwiOi..."),
     *             @OA\Property(property="refresh_token", type="string", example="eyJ1c2VyX2lkIjoyOCwidHlwZSI6In..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *             @OA\Property(property="method", type="string", example="direct_auth"),
     *             @OA\Property(property="authentik", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=28),
     *                 @OA\Property(property="username", type="string", example="jean.dupont@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="❌ Identifiants incorrects",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Identifiants incorrects. Veuillez vérifier votre email et mot de passe.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="❌ Erreur de validation"),
     *     @OA\Response(response=500, description="❌ Erreur serveur")
     * )
     */
    public function loginDirect(LoginRequest $request): JsonResponse
    {
        try {
            Log::info('Tentative de connexion directe', [
                'email' => $request->email,
                'ip' => $request->ip()
            ]);

            $result = $this->authentikService->authenticateUserDirect(
                $request->email,
                $request->password
            );

            if ($result['success']) {
                $user = $this->synchronizeUserFromAuthentik($result['user']);
                
                // Recharger l'utilisateur avec ses relations
                $user->load('personne');

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
                        'created_at' => $user->created_at->toIso8601String(),
                        'personne' => [
                            'id' => $user->personne->id,
                            'nom' => $user->personne->nom,
                            'prenom' => $user->personne->prenom,
                            'nom_complet' => $user->personne->prenom . ' ' . $user->personne->nom,
                            'email' => $user->personne->email,
                            'contact' => $user->personne->contact,
                            'adresse' => $user->personne->adresse,
                        ]
                    ],
                    'access_token' => $result['tokens']['access_token'],
                    'refresh_token' => $result['tokens']['refresh_token'],
                    'token_type' => $result['tokens']['token_type'],
                    'expires_in' => $result['tokens']['expires_in'],
                    'method' => 'direct_auth',
                    'authentik' => [
                        'user_id' => $result['user']['pk'],
                        'username' => $result['user']['username'],
                    ],
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
     * @OA\Get(
     *     path="/api/auth/auth-url",
     *     operationId="getAuthUrl",
     *     tags={"🔐 Authentification"},
     *     summary="🔗 Obtenir l'URL d'authentification",
     *     description="Génère l'URL d'authentification pour l'Authorization Code Flow avec Authentik. Cette URL doit être utilisée pour rediriger l'utilisateur vers Authentik pour s'authentifier.",
     *     @OA\Response(
     *         response=200,
     *         description="✅ URL d'authentification générée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="auth_url", type="string", example="http://5.189.156.115:31015/application/o/authorize/?client_id=..."),
     *             @OA\Property(property="message", type="string", example="Redirigez l'utilisateur vers cette URL pour s'authentifier.")
     *         )
     *     ),
     *     @OA\Response(response=500, description="❌ Erreur serveur")
     * )
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
     * @OA\Get(
     *     path="/api/auth/authentik/redirect",
     *     operationId="redirectToAuthentik",
     *     tags={"🔐 Authentification"},
     *     summary="🔄 Redirection vers Authentik",
     *     description="Redirige l'utilisateur vers Authentik pour l'authentification OAuth.",
     *     @OA\Response(
     *         response=302,
     *         description="🔄 Redirection vers Authentik"
     *     ),
     *     @OA\Response(response=500, description="❌ Erreur serveur")
     * )
     */
    public function redirectToAuthentik()
    {
        try {
            Log::info('Redirection vers Authentik pour authentification');
            return Socialite::driver('authentik')->stateless()->redirect();
        } catch (Exception $e) {
            Log::error('Erreur lors de la redirection vers Authentik', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la redirection vers Authentik.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/authentik/callback",
     *     operationId="handleAuthentikCallback",
     *     tags={"🔐 Authentification"},
     *     summary="📞 Callback d'authentification Authentik",
     *     description="Gère le callback d'authentification après que l'utilisateur s'est connecté sur Authentik.",
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="Code d'autorisation fourni par Authentik",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Authentification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Authentification réussie."),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(response=400, description="❌ Erreur de callback"),
     *     @OA\Response(response=500, description="❌ Erreur serveur")
     * )
     */
    public function handleAuthentikCallback(): JsonResponse
    {
        try {
            Log::info('Callback Authentik reçu');

            $authentikUser = Socialite::driver('authentik')->stateless()->user();

            DB::beginTransaction();

            $user = $this->synchronizeUserFromAuthentik([
                'email' => $authentikUser->getEmail(),
                'name' => $authentikUser->getName(),
                'attributes' => $authentikUser->user['attributes'] ?? [],
            ]);

            DB::commit();

            $accessToken = $this->generateAccessToken($user);

            Log::info('Authentification OAuth réussie', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Authentification réussie.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors du callback Authentik', [
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
     * @OA\Post(
     *     path="/api/auth/logout",
     *     operationId="logoutUser",
     *     tags={"🔄 Tokens"},
     *     summary="🚪 Déconnexion de l'utilisateur",
     *     description="Déconnecte l'utilisateur en révoquant ses tokens d'accès et de rafraîchissement dans Authentik.",
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="refresh_token", type="string", example="eyJ1c2VyX2lkIjoyOCwidHlwZSI6In...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie. À bientôt !")
     *         )
     *     ),
     *     @OA\Response(response=401, description="❌ Token manquant"),
     *     @OA\Response(response=500, description="❌ Erreur serveur")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $accessToken = $request->bearerToken();
            $refreshToken = $request->input('refresh_token');

            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token d\'authentification manquant.'
                ], 401);
            }

            $this->authentikService->logout($accessToken, $refreshToken);

            Log::info('Déconnexion réussie', [
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie. À bientôt !'
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
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     operationId="refreshToken",
     *     tags={"🔄 Tokens"},
     *     summary="🔄 Rafraîchir le token d'accès",
     *     description="Renouvelle le token d'accès en utilisant le token de rafraîchissement.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="eyJ1c2VyX2lkIjoyOCwidHlwZSI6In...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi avec succès."),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(response=400, description="❌ Refresh token manquant"),
     *     @OA\Response(response=401, description="❌ Refresh token invalide"),
     *     @OA\Response(response=500, description="❌ Erreur serveur")
     * )
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->input('refresh_token');

            if (!$refreshToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le refresh token est obligatoire.'
                ], 400);
            }

            $tokens = $this->authentikService->refreshAccessToken($refreshToken);

            if (!$tokens) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de rafraîchir le token. Veuillez vous reconnecter.'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token rafraîchi avec succès.',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $tokens['expires_in'] ?? 3600,
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

    private function synchronizeUserFromAuthentik(array $authentikUser): Utilisateur
    {
        $user = Utilisateur::where('email', $authentikUser['email'])->first();

        if (!$user) {
            $user = Utilisateur::create([
                'email' => $authentikUser['email'],
                'password' => Hash::make(Str::random(32)),
                'role' => $authentikUser['attributes']['role'][0] ?? 'candidat',
            ]);

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

    private function generateAccessToken(Utilisateur $user): string
    {
        $payload = [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'exp' => time() + 3600,
            'iat' => time(),
        ];

        return base64_encode(json_encode($payload));
    }
}

