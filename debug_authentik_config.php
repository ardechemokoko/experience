<?php

echo "🔍 DIAGNOSTIC AUTHENTIK - Configuration Laravel\n";
echo "==============================================\n\n";

// Charger le fichier .env
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "❌ Fichier .env non trouvé !\n";
    exit(1);
}

$env = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && !str_starts_with($line, '#')) {
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

echo "📋 Configuration Authentik dans .env :\n";
echo "------------------------------------\n";
echo "AUTHENTIK_BASE_URL: " . ($env['AUTHENTIK_BASE_URL'] ?? '❌ NON DÉFINI') . "\n";
echo "AUTHENTIK_CLIENT_ID: " . ($env['AUTHENTIK_CLIENT_ID'] ?? '❌ NON DÉFINI') . "\n";
echo "AUTHENTIK_CLIENT_SECRET: " . (isset($env['AUTHENTIK_CLIENT_SECRET']) ? '✅ DÉFINI (' . strlen($env['AUTHENTIK_CLIENT_SECRET']) . ' caractères)' : '❌ NON DÉFINI') . "\n";
echo "AUTHENTIK_API_TOKEN: " . (isset($env['AUTHENTIK_API_TOKEN']) ? '✅ DÉFINI (' . strlen($env['AUTHENTIK_API_TOKEN']) . ' caractères)' : '❌ NON DÉFINI') . "\n";
echo "\n";

// Test 1: Vérifier la connectivité
echo "🌐 Test de connectivité Authentik :\n";
echo "----------------------------------\n";

$baseUrl = $env['AUTHENTIK_BASE_URL'] ?? '';
if (empty($baseUrl)) {
    echo "❌ AUTHENTIK_BASE_URL non défini\n";
    exit(1);
}

$wellKnownUrl = rtrim($baseUrl, '/') . '/application/o/permis/.well-known/openid_configuration';
echo "URL Well-Known: $wellKnownUrl\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($wellKnownUrl, false, $context);
if ($response === false) {
    echo "❌ Impossible de se connecter à Authentik\n";
    echo "Erreur: " . error_get_last()['message'] . "\n";
} else {
    echo "✅ Connexion à Authentik OK\n";
    $config = json_decode($response, true);
    echo "Grant types supportés: " . implode(', ', $config['grant_types_supported'] ?? []) . "\n";
    echo "Scopes supportés: " . implode(', ', $config['scopes_supported'] ?? []) . "\n";
}
echo "\n";

// Test 2: Test de l'API Token
echo "🔑 Test du Token API :\n";
echo "---------------------\n";

$apiToken = $env['AUTHENTIK_API_TOKEN'] ?? '';
if (empty($apiToken)) {
    echo "❌ AUTHENTIK_API_TOKEN non défini\n";
} else {
    $apiUrl = rtrim($baseUrl, '/') . '/api/v3/core/users/';
    $headers = [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json'
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    if ($response === false) {
        echo "❌ Token API invalide ou expiré\n";
        echo "Erreur: " . error_get_last()['message'] . "\n";
    } else {
        $data = json_decode($response, true);
        echo "✅ Token API valide\n";
        echo "Nombre d'utilisateurs: " . count($data['results'] ?? []) . "\n";
    }
}
echo "\n";

// Test 3: Test Password Grant avec un utilisateur
echo "🔐 Test Password Grant :\n";
echo "------------------------\n";

$clientId = $env['AUTHENTIK_CLIENT_ID'] ?? '';
$clientSecret = $env['AUTHENTIK_CLIENT_SECRET'] ?? '';

if (empty($clientId) || empty($clientSecret)) {
    echo "❌ CLIENT_ID ou CLIENT_SECRET manquant\n";
} else {
    // Demander l'email et mot de passe
    echo "Entrez l'email de l'utilisateur à tester: ";
    $email = trim(fgets(STDIN));
    echo "Entrez le mot de passe: ";
    $password = trim(fgets(STDIN));
    
    $tokenUrl = rtrim($baseUrl, '/') . '/application/o/token/';
    $postData = http_build_query([
        'grant_type' => 'password',
        'username' => $email,
        'password' => $password,
        'scope' => 'openid email profile offline_access goauthentik.io/api'
    ]);
    
    $auth = base64_encode($clientId . ':' . $clientSecret);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . $auth
            ],
            'content' => $postData,
            'timeout' => 10
        ]
    ]);
    
    echo "Test avec email: $email\n";
    echo "URL Token: $tokenUrl\n";
    
    $response = @file_get_contents($tokenUrl, false, $context);
    if ($response === false) {
        echo "❌ Erreur lors du test Password Grant\n";
        echo "Erreur: " . error_get_last()['message'] . "\n";
    } else {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            echo "✅ Password Grant OK !\n";
            echo "Access Token: " . substr($data['access_token'], 0, 20) . "...\n";
            echo "Expires In: " . ($data['expires_in'] ?? 'N/A') . "s\n";
            if (isset($data['refresh_token'])) {
                echo "Refresh Token: " . substr($data['refresh_token'], 0, 20) . "...\n";
            }
        } else {
            echo "❌ Échec Password Grant\n";
            echo "Réponse: " . $response . "\n";
        }
    }
}

echo "\n";
echo "🎯 RÉSUMÉ :\n";
echo "===========\n";
echo "1. Vérifiez que tous les paramètres .env sont corrects\n";
echo "2. Vérifiez que l'utilisateur existe dans Authentik\n";
echo "3. Vérifiez que le mot de passe est correct\n";
echo "4. Vérifiez que l'utilisateur est actif dans Authentik\n";
echo "\n";

?>
