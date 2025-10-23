<?php

echo "👤 Création d'utilisateur dans Authentik\n";
echo "======================================\n\n";

// Configuration
$baseUrl = "http://5.189.156.115:31015";
$email = "candidat@test.com";
$password = "Password123!";
$nom = "Candidat";
$prenom = "Test";

// Lire le token API depuis .env
$envFile = __DIR__ . '/.env';
$apiToken = '';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'AUTHENTIK_API_TOKEN=') === 0) {
            $apiToken = substr($line, strlen('AUTHENTIK_API_TOKEN='));
            break;
        }
    }
}

if (empty($apiToken)) {
    echo "❌ Token API non trouvé dans .env\n";
    exit(1);
}

echo "📋 Création de l'utilisateur :\n";
echo "Email: $email\n";
echo "Nom: $nom $prenom\n";
echo "Password: $password\n\n";

// Étape 1: Créer l'utilisateur
echo "🔄 Étape 1: Création de l'utilisateur...\n";

$userData = [
    'username' => $email,
    'email' => $email,
    'name' => "$nom $prenom",
    'is_active' => true
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/v3/core/users/");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Réponse HTTP: $httpCode\n";

if ($httpCode == 201) {
    $user = json_decode($response, true);
    $userId = $user['pk'];
    echo "✅ Utilisateur créé avec l'ID: $userId\n";
    
    // Étape 2: Définir le mot de passe
    echo "\n🔄 Étape 2: Définition du mot de passe...\n";
    
    $passwordData = [
        'password' => $password
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/v3/core/users/$userId/set_password/");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($passwordData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Réponse HTTP: $httpCode\n";
    
    if ($httpCode == 204) {
        echo "✅ Mot de passe défini\n";
        
        // Étape 3: Ajouter à un groupe (rôle)
        echo "\n🔄 Étape 3: Attribution du rôle candidat...\n";
        
        // Créer ou récupérer le groupe "candidats"
        $groupName = "candidats";
        
        // Vérifier si le groupe existe
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/v3/core/groups/?name=$groupName");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiToken
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $groups = json_decode($response, true);
        curl_close($ch);
        
        if (empty($groups['results'])) {
            // Créer le groupe
            echo "Création du groupe '$groupName'...\n";
            
            $groupData = [
                'name' => $groupName,
                'is_superuser' => false
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/v3/core/groups/");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($groupData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $group = json_decode($response, true);
            curl_close($ch);
            
            if ($httpCode == 201) {
                $groupId = $group['pk'];
                echo "✅ Groupe créé avec l'ID: $groupId\n";
            } else {
                echo "❌ Erreur création groupe: $httpCode\n";
                echo "Réponse: $response\n";
                exit(1);
            }
        } else {
            $groupId = $groups['results'][0]['pk'];
            echo "✅ Groupe existant trouvé avec l'ID: $groupId\n";
        }
        
        // Ajouter l'utilisateur au groupe
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/v3/core/groups/$groupId/");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'users' => [$userId]
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "Réponse HTTP: $httpCode\n";
        
        if ($httpCode == 200) {
            echo "✅ Utilisateur ajouté au groupe candidats\n";
        } else {
            echo "❌ Erreur ajout au groupe: $httpCode\n";
            echo "Réponse: $response\n";
        }
        
        echo "\n🎉 SUCCÈS ! Utilisateur créé et configuré\n";
        echo "=====================================\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
        echo "Rôle: candidat\n";
        echo "\nVous pouvez maintenant tester la connexion !\n";
        
    } else {
        echo "❌ Erreur définition mot de passe: $httpCode\n";
        echo "Réponse: $response\n";
    }
    
} elseif ($httpCode == 400) {
    $error = json_decode($response, true);
    if (isset($error['username']) && strpos($error['username'][0], 'already exists') !== false) {
        echo "⚠️ Utilisateur existe déjà\n";
        echo "✅ Vous pouvez directement tester la connexion !\n";
    } else {
        echo "❌ Erreur création utilisateur: $httpCode\n";
        echo "Réponse: $response\n";
    }
} else {
    echo "❌ Erreur création utilisateur: $httpCode\n";
    echo "Réponse: $response\n";
}

?>
