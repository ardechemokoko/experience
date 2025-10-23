<?php

/**
 * Test de connexion directe à Authentik
 * 
 * Usage: php test_login_direct.php email@example.com Password123!
 */

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$email = $argv[1] ?? 'mokoko3@gmail.com';
$password = $argv[2] ?? '';

if (!$password) {
    echo "⚠️  Mot de passe non fourni\n";
    echo "Usage: php test_login_direct.php {$email} VotreMotDePasse\n";
    exit(1);
}

echo "🔍 Test de Connexion Authentik\n";
echo "=====================================\n\n";

$baseUrl = rtrim($_ENV['AUTHENTIK_BASE_URL'] ?? '', '/');
$clientId = $_ENV['AUTHENTIK_CLIENT_ID'] ?? '';
$clientSecret = $_ENV['AUTHENTIK_CLIENT_SECRET'] ?? '';

echo "📋 Configuration :\n";
echo "   Base URL: {$baseUrl}\n";
echo "   Client ID: {$clientId}\n";
echo "   Client Secret: " . str_repeat('*', strlen($clientSecret) - 4) . substr($clientSecret, -4) . "\n";
echo "   Email: {$email}\n";
echo "   Password: " . str_repeat('*', strlen($password)) . "\n\n";

$client = new Client([
    'base_uri' => $baseUrl,
    'verify' => false,
]);

// Test Password Grant
echo "📍 Test : Password Grant OAuth2\n";
echo "   Endpoint: {$baseUrl}/application/o/token/\n";
echo "   Grant Type: password\n\n";

try {
    $requestData = [
        'grant_type' => 'password',
        'username' => $email,
        'password' => $password,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'scope' => 'openid email profile',
    ];

    echo "   📤 Envoi de la requête...\n";
    
    $response = $client->post('/application/o/token/', [
        'form_params' => $requestData,
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ]
    ]);

    echo "   ✅ Requête réussie !\n\n";
    
    $tokens = json_decode($response->getBody()->getContents(), true);
    
    echo "📊 Tokens reçus:\n";
    echo "   Access Token: " . substr($tokens['access_token'], 0, 50) . "...\n";
    echo "   Token Type: {$tokens['token_type']}\n";
    echo "   Expires In: {$tokens['expires_in']}s\n";
    
    if (isset($tokens['refresh_token'])) {
        echo "   Refresh Token: " . substr($tokens['refresh_token'], 0, 50) . "...\n";
    }
    
    echo "\n✅ LA CONNEXION FONCTIONNE !\n";
    echo "   → Le problème n'est PAS les identifiants\n";
    echo "   → Le problème est peut-être dans le code Laravel\n\n";

} catch (GuzzleHttp\Exception\ClientException $e) {
    $statusCode = $e->getResponse()->getStatusCode();
    $body = $e->getResponse()->getBody()->getContents();
    
    echo "   ❌ Erreur HTTP {$statusCode}\n";
    echo "   Réponse brute: {$body}\n\n";
    
    $errorData = json_decode($body, true);
    
    if (isset($errorData['error'])) {
        echo "🔍 DIAGNOSTIC:\n\n";
        
        switch ($errorData['error']) {
            case 'invalid_grant':
                echo "❌ INVALID_GRANT\n";
                echo "   Causes possibles:\n";
                echo "   1. ❌ Le mot de passe est INCORRECT\n";
                echo "   2. ❌ L'utilisateur n'est pas actif dans Authentik\n";
                echo "   3. ❌ Le username ({$email}) n'existe pas\n\n";
                
                echo "   Solutions:\n";
                echo "   ✅ Vérifiez le mot de passe dans Authentik\n";
                echo "   ✅ Directory → Users → {$email} → Vérifier 'Active'\n";
                echo "   ✅ Essayez de vous connecter directement sur Authentik\n\n";
                break;
                
            case 'invalid_client':
                echo "❌ INVALID_CLIENT\n";
                echo "   Causes possibles:\n";
                echo "   1. ❌ AUTHENTIK_CLIENT_ID incorrect\n";
                echo "   2. ❌ AUTHENTIK_CLIENT_SECRET incorrect\n\n";
                
                echo "   Solutions:\n";
                echo "   ✅ Vérifiez dans Authentik:\n";
                echo "      Applications → Providers → Votre Provider\n";
                echo "      Client ID: {$clientId}\n";
                echo "   ✅ Comparez avec votre .env\n\n";
                break;
                
            case 'unsupported_grant_type':
                echo "❌ UNSUPPORTED_GRANT_TYPE\n";
                echo "   Le Password Grant n'est PAS activé !\n\n";
                
                echo "   SOLUTION:\n";
                echo "   1. Authentik → Applications → Providers\n";
                echo "   2. Éditez votre Provider OAuth\n";
                echo "   3. Dans 'Authorization flow':\n";
                echo "      ☑ Cochez 'Resource Owner Password Credentials'\n";
                echo "   4. Sauvegardez\n";
                echo "   5. Réessayez\n\n";
                break;
                
            default:
                echo "❌ Erreur inconnue: {$errorData['error']}\n";
                if (isset($errorData['error_description'])) {
                    echo "   Description: {$errorData['error_description']}\n";
                }
                echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Erreur : " . $e->getMessage() . "\n\n";
}

echo "=====================================\n";
echo "📚 Documentation: GUIDE_LOGOUT_AUTHENTIK.md\n";

