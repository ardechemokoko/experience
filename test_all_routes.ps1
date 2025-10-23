# Script de Test Complet de Toutes les Routes API Authentik

Write-Host "🧪 TEST COMPLET DE TOUTES LES ROUTES API AUTHENTIK" -ForegroundColor Green
Write-Host "====================================================" -ForegroundColor Green
Write-Host ""

$baseUrl = "http://localhost:8000"
$testsPassed = 0
$testsFailed = 0
$testsTotal = 0

# Variables pour stocker les tokens
$accessToken = ""
$refreshToken = ""
$userId = ""

# Fonction pour afficher les résultats
function Show-TestResult {
    param($testName, $success, $details = "")
    
    $script:testsTotal++
    
    if ($success) {
        Write-Host "✅ TEST $script:testsTotal : $testName" -ForegroundColor Green
        $script:testsPassed++
    } else {
        Write-Host "❌ TEST $script:testsTotal : $testName" -ForegroundColor Red
        $script:testsFailed++
    }
    
    if ($details) {
        Write-Host "   $details" -ForegroundColor Cyan
    }
    Write-Host ""
}

# ==================================================
# TEST 1 : Health Check
# ==================================================
Write-Host "📋 TEST 1/9 : Health Check" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/api/health" -Method Get
    
    if ($response.status -eq "ok") {
        Show-TestResult "Health Check" $true "API fonctionnelle (v$($response.version))"
    } else {
        Show-TestResult "Health Check" $false "Status incorrect : $($response.status)"
    }
} catch {
    Show-TestResult "Health Check" $false "Erreur : $($_.Exception.Message)"
}

# ==================================================
# TEST 2 : Inscription Nouvel Utilisateur
# ==================================================
Write-Host "📋 TEST 2/9 : Inscription" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow

$timestamp = Get-Date -Format "HHmmss"
$testEmail = "test.auto.$timestamp@example.com"

$registerData = @{
    email = $testEmail
    password = "TestAuto123!"
    password_confirmation = "TestAuto123!"
    nom = "Auto"
    prenom = "Test"
    contact = "0612345678"
    adresse = "123 Rue du Test"
    role = "candidat"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/register" -Method Post -Body $registerData -ContentType "application/json"
    
    if ($response.success -and $response.user.id -and $response.user.personne.nom_complet -and $response.authentik.user_id) {
        $userId = $response.user.id
        Show-TestResult "Inscription" $true "User: $($response.user.personne.nom_complet) | Authentik ID: $($response.authentik.user_id)"
        
        Write-Host "   📊 Informations créées:" -ForegroundColor Magenta
        Write-Host "      - User ID: $($response.user.id)" -ForegroundColor White
        Write-Host "      - Email: $($response.user.email)" -ForegroundColor White
        Write-Host "      - Nom complet: $($response.user.personne.nom_complet)" -ForegroundColor White
        Write-Host "      - Contact: $($response.user.personne.contact)" -ForegroundColor White
        Write-Host "      - Authentik User ID: $($response.authentik.user_id)" -ForegroundColor White
        Write-Host ""
    } else {
        Show-TestResult "Inscription" $false "Réponse incomplète"
    }
} catch {
    Show-TestResult "Inscription" $false "Erreur : $($_.Exception.Message)"
}

# ==================================================
# TEST 3 : Connexion Directe avec l'utilisateur existant
# ==================================================
Write-Host "📋 TEST 3/9 : Connexion Directe" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow

$loginData = @{
    email = "candidat@test.com"
    password = "Password123!"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/login-direct" -Method Post -Body $loginData -ContentType "application/json"
    
    if ($response.success -and $response.access_token -and $response.refresh_token) {
        $accessToken = $response.access_token
        $refreshToken = $response.refresh_token
        
        Show-TestResult "Connexion Directe" $true "Tokens obtenus | Method: $($response.method)"
        
        Write-Host "   🔑 Tokens générés:" -ForegroundColor Magenta
        Write-Host "      - Access Token: $($accessToken.Substring(0, 30))..." -ForegroundColor White
        Write-Host "      - Refresh Token: $($refreshToken.Substring(0, 30))..." -ForegroundColor White
        Write-Host "      - Expires in: $($response.expires_in)s" -ForegroundColor White
        Write-Host "      - User: $($response.user.personne.nom_complet)" -ForegroundColor White
        Write-Host ""
    } else {
        Show-TestResult "Connexion Directe" $false "Tokens manquants"
    }
} catch {
    Show-TestResult "Connexion Directe" $false "Erreur : $($_.Exception.Message)"
}

# ==================================================
# TEST 4 : Obtenir URL d'Authentification
# ==================================================
Write-Host "📋 TEST 4/9 : URL d'Authentification" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/auth-url" -Method Get
    
    if ($response.success -and $response.auth_url) {
        Show-TestResult "URL d'Authentification" $true "URL générée"
        Write-Host "   🔗 URL: $($response.auth_url.Substring(0, 80))..." -ForegroundColor Cyan
        Write-Host ""
    } else {
        Show-TestResult "URL d'Authentification" $false "URL manquante"
    }
} catch {
    Show-TestResult "URL d'Authentification" $false "Erreur : $($_.Exception.Message)"
}

# ==================================================
# TEST 5 : Profil Utilisateur (Route Protégée)
# ==================================================
Write-Host "📋 TEST 5/9 : Profil Utilisateur (Protégé)" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow

if ($accessToken) {
    try {
        $headers = @{
            "Authorization" = "Bearer $accessToken"
            "Accept" = "application/json"
        }
        
        $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/me" -Method Get -Headers $headers
        
        if ($response.success -and $response.user) {
            Show-TestResult "Profil Utilisateur" $true "Profil récupéré"
            
            Write-Host "   👤 Profil complet:" -ForegroundColor Magenta
            Write-Host "      - ID: $($response.user.id)" -ForegroundColor White
            Write-Host "      - Email: $($response.user.email)" -ForegroundColor White
            Write-Host "      - Rôle: $($response.user.role)" -ForegroundColor White
            Write-Host "      - Nom complet: $($response.user.personne.nom) $($response.user.personne.prenom)" -ForegroundColor White
            Write-Host "      - Contact: $($response.user.personne.contact)" -ForegroundColor White
            Write-Host ""
        } else {
            Show-TestResult "Profil Utilisateur" $false "Profil incomplet"
        }
    } catch {
        Show-TestResult "Profil Utilisateur" $false "Erreur : $($_.Exception.Message)"
    }
} else {
    Show-TestResult "Profil Utilisateur" $false "Pas de token (connexion échouée)"
}

# ==================================================
# TEST 6 : Rafraîchir Token
# ==================================================
Write-Host "📋 TEST 6/9 : Rafraîchir Token" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow

if ($refreshToken) {
    $refreshData = @{
        refresh_token = $refreshToken
    } | ConvertTo-Json
    
    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/refresh" -Method Post -Body $refreshData -ContentType "application/json"
        
        if ($response.success -and $response.access_token) {
            $newAccessToken = $response.access_token
            Show-TestResult "Rafraîchir Token" $true "Nouveaux tokens obtenus"
            
            Write-Host "   🔄 Nouveaux tokens:" -ForegroundColor Magenta
            Write-Host "      - New Access Token: $($newAccessToken.Substring(0, 30))..." -ForegroundColor White
            Write-Host ""
        } else {
            Show-TestResult "Rafraîchir Token" $false "Nouveaux tokens manquants"
        }
    } catch {
        Show-TestResult "Rafraîchir Token" $false "Erreur : $($_.Exception.Message)"
    }
} else {
    Show-TestResult "Rafraîchir Token" $false "Pas de refresh token (connexion échouée)"
}

# ==================================================
# TEST 7 : Déconnexion (Route Protégée)
# ==================================================
Write-Host "📋 TEST 7/9 : Déconnexion" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow

if ($accessToken -and $refreshToken) {
    $logoutData = @{
        refresh_token = $refreshToken
    } | ConvertTo-Json
    
    try {
        $headers = @{
            "Authorization" = "Bearer $accessToken"
            "Accept" = "application/json"
        }
        
        $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/logout" -Method Post -Body $logoutData -ContentType "application/json" -Headers $headers
        
        if ($response.success) {
            Show-TestResult "Déconnexion" $true "Tokens révoqués"
        } else {
            Show-TestResult "Déconnexion" $false "Révocation échouée"
        }
    } catch {
        Show-TestResult "Déconnexion" $false "Erreur : $($_.Exception.Message)"
    }
} else {
    Show-TestResult "Déconnexion" $false "Pas de tokens (connexion échouée)"
}

# ==================================================
# TEST 8 : Vérifier Révocation (Doit échouer)
# ==================================================
Write-Host "📋 TEST 8/9 : Vérifier Révocation du Token" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow

if ($accessToken) {
    try {
        $headers = @{
            "Authorization" = "Bearer $accessToken"
            "Accept" = "application/json"
        }
        
        $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/me" -Method Get -Headers $headers
        
        # Si on arrive ici, c'est que le token fonctionne encore (pas bon)
        Show-TestResult "Vérification Révocation" $false "Le token fonctionne encore (devrait être révoqué)"
    } catch {
        # Si on a une erreur 401, c'est bon (token révoqué)
        if ($_.Exception.Response.StatusCode -eq 401) {
            Show-TestResult "Vérification Révocation" $true "Token bien révoqué (401 Unauthorized)"
        } else {
            Show-TestResult "Vérification Révocation" $false "Erreur inattendue : $($_.Exception.Message)"
        }
    }
} else {
    Show-TestResult "Vérification Révocation" $false "Pas de token (connexion échouée)"
}

# ==================================================
# TEST 9 : Reconnecter Après Déconnexion
# ==================================================
Write-Host "📋 TEST 9/9 : Reconnecter Après Déconnexion" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/login-direct" -Method Post -Body $loginData -ContentType "application/json"
    
    if ($response.success -and $response.access_token) {
        Show-TestResult "Reconnecter" $true "Reconnexion réussie | Nouveaux tokens obtenus"
    } else {
        Show-TestResult "Reconnecter" $false "Reconnexion échouée"
    }
} catch {
    Show-TestResult "Reconnecter" $false "Erreur : $($_.Exception.Message)"
}

# ==================================================
# RÉSUMÉ FINAL
# ==================================================
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "📊 RÉSUMÉ FINAL" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Tests Total     : $testsTotal" -ForegroundColor White
Write-Host "Tests Réussis   : $testsPassed" -ForegroundColor Green
Write-Host "Tests Échoués   : $testsFailed" -ForegroundColor Red
Write-Host ""

$successRate = [math]::Round(($testsPassed / $testsTotal) * 100, 2)
Write-Host "Taux de Réussite: $successRate%" -ForegroundColor $(if ($successRate -ge 80) { "Green" } elseif ($successRate -ge 50) { "Yellow" } else { "Red" })
Write-Host ""

if ($testsFailed -eq 0) {
    Write-Host "🎉 FÉLICITATIONS ! Tous les tests sont passés !" -ForegroundColor Green
    Write-Host "✅ Votre API Authentik est 100% fonctionnelle !" -ForegroundColor Green
} elseif ($successRate -ge 80) {
    Write-Host "⚠️ La plupart des tests sont passés, mais certains ont échoué." -ForegroundColor Yellow
    Write-Host "📋 Consultez les détails ci-dessus pour corriger les problèmes." -ForegroundColor Yellow
} else {
    Write-Host "❌ De nombreux tests ont échoué." -ForegroundColor Red
    Write-Host "🔍 Vérifiez la configuration et les logs Laravel." -ForegroundColor Red
}

Write-Host ""
Write-Host "📚 Documentation:" -ForegroundColor Cyan
Write-Host "   - Guide complet: GUIDE_TEST_SWAGGER_AUTHENTIK.md" -ForegroundColor White
Write-Host "   - Swagger UI: http://localhost:8000/api/documentation" -ForegroundColor White
Write-Host ""

