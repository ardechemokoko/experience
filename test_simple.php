<?php

echo "🧪 Test Simple Authentik\n";
echo "========================\n\n";

$email = "candidat@test.com";
$password = "Password123!";
$baseUrl = "http://5.189.156.115:31015";
$clientId = "JpMm7W7oeisa2EWDsfxyX0xNoF9SEYlOnKDfGxu2";

// Lire le client secret depuis .env
$envFile = __DIR__ . '/.env';
$clientSecret = '';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'AUTHENTIK_CLIENT_SECRET=') === 0) {
            $clientSecret = substr($line, strlen('AUTHENTIK_CLIENT_SECRET='));
            break;
        }
    }
}

echo "📋 Paramètres :\n";
echo "Email: $email\n";
echo "Base URL: $baseUrl\n";
echo "Client ID: $clientId\n";
echo "Client Secret: " . (empty($clientSecret) ? "❌ NON TROUVÉ" : "✅ OK (" . strlen($clientSecret) . " caractères)") . "\n\n";

if (empty($clientSecret)) {
    echo "❌ Client Secret non trouvé dans .env\n";
    exit(1);
}

// Test Password Grant
$tokenUrl = "$baseUrl/application/o/token/";
$postData = http_build_query([
    'grant_type' => 'password',
    'username' => $email,
    'password' => $password,
    'scope' => 'openid email profile offline_access goauthentik.io/api'
]);

$auth = base64_encode($clientId . ':' . $clientSecret);

echo "🔐 Test Password Grant :\n";
echo "URL: $tokenUrl\n";
echo "Grant Type: password\n";
echo "Username: $email\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $auth,
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📤 Réponse HTTP :\n";
echo "Code: $httpCode\n";

if ($error) {
    echo "❌ Erreur cURL: $error\n";
} else {
    echo "Réponse: $response\n\n";
    
    $data = json_decode($response, true);
    if ($data && isset($data['access_token'])) {
        echo "✅ SUCCÈS ! Connexion OK\n";
        echo "Access Token: " . substr($data['access_token'], 0, 30) . "...\n";
        if (isset($data['refresh_token'])) {
            echo "Refresh Token: " . substr($data['refresh_token'], 0, 30) . "...\n";
        }
    } else {
        echo "❌ ÉCHEC ! Erreur dans la réponse\n";
        if (isset($data['error'])) {
            echo "Erreur: " . $data['error'] . "\n";
            if (isset($data['error_description'])) {
                echo "Description: " . $data['error_description'] . "\n";
            }
        }
    }
}

echo "\n🎯 DIAGNOSTIC :\n";
echo "==============\n";

if ($httpCode == 405) {
    echo "❌ Erreur 405: Method Not Allowed\n";
    echo "→ Le Password Grant n'est PAS activé dans le Provider\n";
    echo "→ Allez dans Authentik → Providers → Éditer votre Provider\n";
    echo "→ Cochez 'Resource Owner Password Credentials'\n";
} elseif ($httpCode == 400 && isset($data['error']) && $data['error'] == 'invalid_grant') {
    echo "❌ Erreur invalid_grant\n";
    echo "→ L'utilisateur n'existe pas OU le mot de passe est incorrect\n";
    echo "→ Vérifiez dans Authentik → Users\n";
} elseif ($httpCode == 401) {
    echo "❌ Erreur 401: Unauthorized\n";
    echo "→ Client ID ou Client Secret incorrect\n";
    echo "→ Vérifiez les credentials dans .env\n";
} elseif ($httpCode == 200) {
    echo "✅ Tout fonctionne ! Le problème est ailleurs.\n";
} else {
    echo "❓ Erreur inconnue. Code HTTP: $httpCode\n";
}

?>
