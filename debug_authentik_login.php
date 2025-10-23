<?php

/**
 * Script de debug pour la connexion Authentik
 * 
 * Usage: php debug_authentik_login.php email@example.com Password123!
 */

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$email || !$password) {
    echo "Usage: php debug_authentik_login.php email@example.com Password123!\n";
    exit(1);
}

echo "🔍 Debug Connexion Authentik\n";
echo "=====================================\n\n";

$baseUrl = rtrim($_ENV['AUTHENTIK_BASE_URL'] ?? '', '/');
$clientId = $_ENV['AUTHENTIK_CLIENT_ID'] ?? '';
$clientSecret = $_ENV['AUTHENTIK_CLIENT_SECRET'] ?? '';
$apiToken = $_ENV['AUTHENTIK_API_TOKEN'] ?? '';

echo "📋 Configuration :\n";
echo "   Base URL: {$baseUrl}\n";
echo "   Client ID: " . substr($clientId, 0, 10) . "...\n";
echo "   Email testé: {$email}\n\n";

// Test 1 : Vérifier si l'utilisateur existe
echo "📍 Test 1 : Vérifier si l'utilisateur existe dans Authentik\n";

$client = new Client([
    'base_uri' => $baseUrl,
    'verify' => false,
]);

try {
    $response = $client->get('/api/v3/core/users/', [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiToken,
        ],
        'query' => [
            'email' => $email,
        ]
    ]);

    $data = json_decode($response->getBody()->getContents(), true);
    
    if (isset($data['results']) && count($data['results']) > 0) {
        $user = $data['results'][0];
        echo "   ✅ Utilisateur trouvé !\n";
        echo "   User ID: {$user['pk']}\n";
        echo "   Username: {$user['username']}\n";
        echo "   Email: {$user['email']}\n";
        echo "   Active: " . ($user['is_active'] ? 'Oui' : 'Non') . "\n";
        echo "   Groupes: " . implode(', ', array_map(fn($g) => $g['name'], $user['groups_obj'] ?? [])) . "\n\n";
    } else {
        echo "   ❌ Utilisateur NON trouvé dans Authentik\n";
        echo "   → L'utilisateur n'existe pas avec cet email\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ Erreur : " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2 : Tester l'authentification Password Grant
echo "📍 Test 2 : Tester l'authentification Password Grant\n";

try {
    $response = $client->post('/application/o/token/', [
        'form_params' => [
            'grant_type' => 'password',
            'username' => $email,
            'password' => $password,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ],
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]
    ]);

    $tokens = json_decode($response->getBody()->getContents(), true);
    
    echo "   ✅ Authentification réussie !\n";
    echo "   Access Token: " . substr($tokens['access_token'], 0, 30) . "...\n";
    echo "   Token Type: {$tokens['token_type']}\n";
    echo "   Expires In: {$tokens['expires_in']}s\n\n";

} catch (GuzzleHttp\Exception\ClientException $e) {
    $statusCode = $e->getResponse()->getStatusCode();
    $body = $e->getResponse()->getBody()->getContents();
    
    echo "   ❌ Échec authentification (Code {$statusCode})\n";
    echo "   Réponse: {$body}\n\n";
    
    if ($statusCode === 400) {
        $error = json_decode($body, true);
        
        if (isset($error['error'])) {
            echo "   🔍 Analyse de l'erreur:\n";
            
            switch ($error['error']) {
                case 'invalid_grant':
                    echo "   ❌ INVALID GRANT - Causes possibles:\n";
                    echo "      1. Le mot de passe est incorrect\n";
                    echo "      2. L'utilisateur n'est pas actif\n";
                    echo "      3. Le username/email est incorrect\n";
                    echo "      4. Le Password Grant n'est pas activé\n\n";
                    break;
                    
                case 'invalid_client':
                    echo "   ❌ INVALID CLIENT - Causes possibles:\n";
                    echo "      1. Client ID incorrect\n";
                    echo "      2. Client Secret incorrect\n";
                    echo "      3. Le Provider n'existe pas\n\n";
                    break;
                    
                case 'unsupported_grant_type':
                    echo "   ❌ UNSUPPORTED GRANT TYPE - Causes:\n";
                    echo "      1. Password Grant n'est PAS activé dans le Provider\n";
                    echo "      2. Il faut activer 'Resource Owner Password Credentials'\n\n";
                    break;
                    
                default:
                    echo "   ❌ Erreur: {$error['error']}\n\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Erreur : " . $e->getMessage() . "\n\n";
}

// Test 3 : Informations sur le Provider
echo "📍 Test 3 : Vérifier la configuration du Provider OAuth\n";

echo "   ⚠️  Pour que le Password Grant fonctionne, vérifiez dans Authentik:\n\n";
echo "   1. Applications → Providers → Votre Provider\n";
echo "   2. Dans 'Authorization flow', vérifiez que:\n";
echo "      ☑ Resource Owner Password Credentials est COCHÉ\n\n";

echo "   3. Si ce n'est pas coché:\n";
echo "      a. Éditez le Provider\n";
echo "      b. Cochez 'Resource Owner Password Credentials'\n";
echo "      c. Sauvegardez\n";
echo "      d. Réessayez ce script\n\n";

echo "=====================================\n";
echo "📝 Résumé:\n\n";
echo "   1. Utilisateur existe ? → Voir Test 1\n";
echo "   2. Authentification OK ? → Voir Test 2\n";
echo "   3. Si erreur 'unsupported_grant_type' → Activer Password Grant\n";
echo "   4. Si erreur 'invalid_grant' → Vérifier le mot de passe\n\n";

