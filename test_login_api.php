<?php

echo "🧪 Test de l'API Laravel Login\n";
echo "=============================\n\n";

$email = "candidat@test.com";
$password = "Password123!";

$postData = json_encode([
    'email' => $email,
    'password' => $password
]);

echo "📋 Test avec :\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/auth/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "🔄 Envoi de la requête...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📤 Réponse :\n";
echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "❌ Erreur cURL: $error\n";
} else {
    echo "Réponse: $response\n\n";
    
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "✅ SUCCÈS ! Connexion OK\n";
        if (isset($data['access_token'])) {
            echo "Access Token: " . substr($data['access_token'], 0, 30) . "...\n";
        }
    } else {
        echo "❌ ÉCHEC !\n";
        if (isset($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
    }
}

echo "\n🎯 DIAGNOSTIC :\n";
echo "==============\n";

if ($httpCode == 422) {
    echo "❌ Erreur de validation\n";
    echo "→ Vérifiez les données envoyées\n";
} elseif ($httpCode == 401) {
    echo "❌ Non autorisé\n";
    echo "→ Identifiants incorrects\n";
} elseif ($httpCode == 200 && isset($data['success']) && !$data['success']) {
    echo "❌ Erreur Authentik\n";
    echo "→ Le Password Grant n'est pas activé dans le Provider\n";
    echo "→ Créez un nouveau Provider avec Password Grant activé\n";
} elseif ($httpCode == 200 && isset($data['success']) && $data['success']) {
    echo "✅ Tout fonctionne !\n";
} else {
    echo "❓ Erreur inconnue\n";
}

?>
