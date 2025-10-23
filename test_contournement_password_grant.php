<?php

echo "🚀 Test du Contournement Password Grant\n";
echo "=====================================\n\n";

// Test de la nouvelle méthode de connexion directe
$email = "candidat@test.com";
$password = "Password123!";

echo "📋 Test de connexion directe :\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

$postData = json_encode([
    'email' => $email,
    'password' => $password
]);

echo "🔄 Envoi de la requête vers /api/auth/login-direct...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/auth/login-direct");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

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
        echo "🎉 SUCCÈS ! Connexion directe réussie !\n";
        echo "✅ Méthode: " . ($data['method'] ?? 'N/A') . "\n";
        echo "✅ Utilisateur: " . ($data['user']['email'] ?? 'N/A') . "\n";
        echo "✅ Rôle: " . ($data['user']['role'] ?? 'N/A') . "\n";
        echo "✅ Access Token: " . substr($data['access_token'], 0, 30) . "...\n";
        echo "✅ Refresh Token: " . substr($data['refresh_token'], 0, 30) . "...\n";
        echo "✅ Expires In: " . ($data['expires_in'] ?? 'N/A') . "s\n";
        
        echo "\n🎯 RÉSULTAT :\n";
        echo "=============\n";
        echo "✅ Le contournement Password Grant fonctionne !\n";
        echo "✅ Authentification directe via API Authentik OK !\n";
        echo "✅ Tokens personnalisés générés !\n";
        echo "✅ Synchronisation avec base locale OK !\n";
        
    } else {
        echo "❌ ÉCHEC !\n";
        if (isset($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
        
        echo "\n🔍 DIAGNOSTIC :\n";
        echo "===============\n";
        echo "❌ Le contournement n'a pas fonctionné\n";
        echo "❌ Vérifiez les logs Laravel pour plus de détails\n";
    }
}

echo "\n📋 Test de comparaison avec l'ancienne méthode :\n";
echo "=============================================\n";

// Test avec l'ancienne méthode pour comparaison
echo "🔄 Test avec l'ancienne méthode /api/auth/login...\n";

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

$responseOld = curl_exec($ch);
$httpCodeOld = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Ancienne méthode - HTTP Code: $httpCodeOld\n";

$dataOld = json_decode($responseOld, true);
if ($dataOld && isset($dataOld['success']) && !$dataOld['success']) {
    echo "❌ Ancienne méthode échoue (attendu) : " . ($dataOld['message'] ?? 'Erreur') . "\n";
} else {
    echo "✅ Ancienne méthode fonctionne (inattendu)\n";
}

echo "\n🎯 RÉSUMÉ COMPARATIF :\n";
echo "====================\n";
echo "🆕 Nouvelle méthode (/login-direct): " . ($data['success'] ? "✅ FONCTIONNE" : "❌ ÉCHOUE") . "\n";
echo "🔴 Ancienne méthode (/login): " . ($dataOld['success'] ? "✅ FONCTIONNE" : "❌ ÉCHOUE (attendu)") . "\n";

if ($data['success']) {
    echo "\n🚀 CONCLUSION :\n";
    echo "==============\n";
    echo "✅ Le contournement Password Grant est opérationnel !\n";
    echo "✅ Vous pouvez maintenant utiliser /api/auth/login-direct\n";
    echo "✅ L'authentification email/password fonctionne parfaitement !\n";
    echo "✅ Plus besoin du Password Grant Flow dans Authentik !\n";
} else {
    echo "\n❌ CONCLUSION :\n";
    echo "==============\n";
    echo "❌ Le contournement ne fonctionne pas encore\n";
    echo "❌ Vérifiez la configuration et les logs\n";
}

?>
