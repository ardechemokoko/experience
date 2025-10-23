# Test Final de Toutes les Routes API Authentik

Write-Host "=== TEST FINAL API AUTHENTIK ===" -ForegroundColor Green
Write-Host ""

$baseUrl = "http://localhost:8000"
$passed = 0
$failed = 0

# TEST 1: Health Check
Write-Host "TEST 1/7: Health Check..." -ForegroundColor Yellow
try {
    $r = Invoke-RestMethod "$baseUrl/api/health"
    if ($r.status -eq "ok") {
        Write-Host "  PASS - API OK (version $($r.version))" -ForegroundColor Green
        $passed++
    }
} catch {
    Write-Host "  FAIL" -ForegroundColor Red
    $failed++
}
Write-Host ""

# TEST 2: Inscription
Write-Host "TEST 2/7: Inscription..." -ForegroundColor Yellow
$time = Get-Date -Format "HHmmss"
$body = @{
    email = "final.$time@test.com"
    password = "Final123!"
    password_confirmation = "Final123!"
    nom = "Final"
    prenom = "Test"
    contact = "0698765432"
    adresse = "456 Avenue Finale"
    role = "candidat"
} | ConvertTo-Json

try {
    $r = Invoke-RestMethod "$baseUrl/api/auth/register" -Method Post -Body $body -ContentType "application/json"
    if ($r.success -and $r.user.personne.nom_complet -and $r.authentik.user_id) {
        Write-Host "  PASS" -ForegroundColor Green
        Write-Host "    User: $($r.user.personne.nom_complet)" -ForegroundColor Cyan
        Write-Host "    Email: $($r.user.email)" -ForegroundColor Cyan
        Write-Host "    Authentik ID: $($r.authentik.user_id)" -ForegroundColor Cyan
        $passed++
    }
} catch {
    Write-Host "  FAIL" -ForegroundColor Red
    $failed++
}
Write-Host ""

# TEST 3: Connexion Directe
Write-Host "TEST 3/7: Connexion Directe..." -ForegroundColor Yellow
$body = @{
    email = "candidat@test.com"
    password = "Password123!"
} | ConvertTo-Json

try {
    $r = Invoke-RestMethod "$baseUrl/api/auth/login-direct" -Method Post -Body $body -ContentType "application/json"
    if ($r.success -and $r.access_token -and $r.refresh_token) {
        $token = $r.access_token
        $refreshToken = $r.refresh_token
        Write-Host "  PASS" -ForegroundColor Green
        Write-Host "    User: $($r.user.personne.nom_complet)" -ForegroundColor Cyan
        Write-Host "    Email: $($r.user.email)" -ForegroundColor Cyan
        Write-Host "    Role: $($r.user.role)" -ForegroundColor Cyan
        Write-Host "    Token expires in: $($r.expires_in)s" -ForegroundColor Cyan
        $passed++
    }
} catch {
    Write-Host "  FAIL" -ForegroundColor Red
    $failed++
}
Write-Host ""

# TEST 4: Auth URL
Write-Host "TEST 4/7: URL Authentification..." -ForegroundColor Yellow
try {
    $r = Invoke-RestMethod "$baseUrl/api/auth/auth-url"
    if ($r.success -and $r.auth_url) {
        Write-Host "  PASS - URL generee" -ForegroundColor Green
        $passed++
    }
} catch {
    Write-Host "  FAIL" -ForegroundColor Red
    $failed++
}
Write-Host ""

# TEST 5: Refresh Token
Write-Host "TEST 5/7: Rafraichir Token..." -ForegroundColor Yellow
if ($refreshToken) {
    $body = @{ refresh_token = $refreshToken } | ConvertTo-Json
    try {
        $r = Invoke-RestMethod "$baseUrl/api/auth/refresh" -Method Post -Body $body -ContentType "application/json"
        if ($r.success -and $r.access_token) {
            Write-Host "  PASS - Nouveau token obtenu" -ForegroundColor Green
            $newToken = $r.access_token
            $passed++
        }
    } catch {
        Write-Host "  FAIL" -ForegroundColor Red
        $failed++
    }
} else {
    Write-Host "  SKIP - Pas de refresh token" -ForegroundColor Yellow
}
Write-Host ""

# TEST 6: Deconnexion
Write-Host "TEST 6/7: Deconnexion..." -ForegroundColor Yellow
if ($token) {
    $headers = @{ "Authorization" = "Bearer $token" }
    $body = @{ refresh_token = $refreshToken } | ConvertTo-Json
    try {
        $r = Invoke-RestMethod "$baseUrl/api/auth/logout" -Method Post -Body $body -ContentType "application/json" -Headers $headers
        if ($r.success) {
            Write-Host "  PASS - Tokens revoques" -ForegroundColor Green
            $passed++
        }
    } catch {
        Write-Host "  FAIL" -ForegroundColor Red
        $failed++
    }
} else {
    Write-Host "  SKIP - Pas de token" -ForegroundColor Yellow
}
Write-Host ""

# TEST 7: Reconnecter
Write-Host "TEST 7/7: Reconnecter apres Deconnexion..." -ForegroundColor Yellow
$body = @{
    email = "candidat@test.com"
    password = "Password123!"
} | ConvertTo-Json

try {
    $r = Invoke-RestMethod "$baseUrl/api/auth/login-direct" -Method Post -Body $body -ContentType "application/json"
    if ($r.success -and $r.access_token) {
        Write-Host "  PASS - Reconnexion reussie" -ForegroundColor Green
        $passed++
    }
} catch {
    Write-Host "  FAIL" -ForegroundColor Red
    $failed++
}
Write-Host ""

# RESUME
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host "           RESULTAT FINAL" -ForegroundColor Cyan
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Tests Total     : 7" -ForegroundColor White
Write-Host "Tests Reussis   : $passed" -ForegroundColor Green
Write-Host "Tests Echoues   : $failed" -ForegroundColor $(if ($failed -eq 0) { "Green" } else { "Red" })
$rate = [math]::Round(($passed / 7) * 100, 0)
Write-Host "Taux de Reussite: $rate%" -ForegroundColor $(if ($rate -eq 100) { "Green" } elseif ($rate -ge 80) { "Yellow" } else { "Red" })
Write-Host ""

if ($failed -eq 0) {
    Write-Host "TOUTES LES ROUTES FONCTIONNENT PARFAITEMENT!" -ForegroundColor Green
    Write-Host "Votre API Authentik est 100% operationnelle!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Routes testees:" -ForegroundColor Cyan
    Write-Host "  - Health Check" -ForegroundColor White
    Write-Host "  - Inscription (avec infos completes)" -ForegroundColor White
    Write-Host "  - Connexion Directe (contournement Password Grant)" -ForegroundColor White
    Write-Host "  - URL Authentification" -ForegroundColor White
    Write-Host "  - Rafraichissement Token" -ForegroundColor White
    Write-Host "  - Deconnexion" -ForegroundColor White
    Write-Host "  - Reconnexion" -ForegroundColor White
} else {
    Write-Host "Certaines routes ont echoue" -ForegroundColor Yellow
    Write-Host "Consultez les details ci-dessus" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Swagger UI: http://localhost:8000/api/documentation" -ForegroundColor Cyan

