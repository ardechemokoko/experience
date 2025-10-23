<?php

namespace App\Http\Middleware;

use App\Models\Utilisateur;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthentikTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Récupérer le token depuis le header Authorization
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token d\'authentification manquant. Veuillez vous connecter.'
            ], 401);
        }

        try {
            // Essayer de décoder comme JWT (3 parties)
            $parts = explode('.', $token);
            
            if (count($parts) === 3) {
                // Vrai JWT - décoder le payload (partie 2)
                $payload = json_decode(base64_decode($parts[1]), true);
            } else {
                // Token base64 simple - décoder directement
                $payload = json_decode(base64_decode($token), true);
            }

            if (!$payload || !isset($payload['user_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide.'
                ], 401);
            }

            // Vérifier l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expiré. Veuillez vous reconnecter.',
                    'expired_at' => date('Y-m-d H:i:s', $payload['exp'])
                ], 401);
            }

            // Récupérer l'utilisateur par email (car l'ID Authentik est différent de l'ID local)
            $user = Utilisateur::with('personne')->where('email', $payload['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé.'
                ], 401);
            }

            // Attacher l'utilisateur à la requête
            $request->attributes->set('authenticated_user', $user);
            $request->attributes->set('token_payload', $payload);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation du token.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 401);
        }

        return $next($request);
    }
}
