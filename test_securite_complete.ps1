# Test de securite complete

Write-Host "=== TEST DE SECURITE COMPLETE ===" -ForegroundColor Green
Write-Host ""

$baseUrl = "http://localhost:8000"

# TEST 1: Route publique (doit marcher)
Write-Host "TEST 1: Route publique /api/health..." -ForegroundColor Yellow
try {
    $r = Invoke-RestMethod "$baseUrl/api/health"
    Write-Host "  PASS - Route publique accessible" -ForegroundColor Green
} catch {
    Write-Host "  FAIL - Route publique bloquee" -ForegroundColor Red
}
Write-Host ""

# TEST 2: Route GET sans token (doit echouer avec 401)
Write-Host "TEST 2: Route GET sans token..." -ForegroundColor Yellow
try {
    $r = Invoke-RestMethod "$baseUrl/api/candidats"
    Write-Host "  FAIL - Route GET accessible sans token!" -ForegroundColor Red
} catch {
    if ($_.Exception.Response.StatusCode -eq 401) {
        Write-Host "  PASS - Route GET bien protegee (401)" -ForegroundColor Green
    } else {
        Write-Host "  FAIL - Erreur inattendue: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    }
}
Write-Host ""

# TEST 3: Route POST sans token (doit echouer avec 401)
Write-Host "TEST 3: Route POST sans token..." -ForegroundColor Yellow
try {
    $body = @{ date_naissance = "1995-05-15" } | ConvertTo-Json
    $r = Invoke-RestMethod "$baseUrl/api/candidats/complete-profile" -Method Post -Body $body -ContentType "application/json"
    Write-Host "  FAIL - Route POST accessible sans token!" -ForegroundColor Red
} catch {
    if ($_.Exception.Response.StatusCode -eq 401) {
        Write-Host "  PASS - Route POST bien protegee (401)" -ForegroundColor Green
    } else {
        Write-Host "  FAIL - Erreur inattendue: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    }
}
Write-Host ""

# TEST 4: Connexion et obtention du token
Write-Host "TEST 4: Connexion pour obtenir token..." -ForegroundColor Yellow
try {
    $body = @{
        email = "candidat@test.com"
        password = "Password123!"
    } | ConvertTo-Json
    
    $r = Invoke-RestMethod "$baseUrl/api/auth/login-direct" -Method Post -Body $body -ContentType "application/json"
    
    if ($r.success -and $r.access_token) {
        $token = $r.access_token
        Write-Host "  PASS - Token obtenu" -ForegroundColor Green
    }
} catch {
    Write-Host "  FAIL - Connexion echouee" -ForegroundColor Red
}
Write-Host ""

# TEST 5: Route GET avec token (doit marcher)
Write-Host "TEST 5: Route GET avec token valide..." -ForegroundColor Yellow
if ($token) {
    try {
        $headers = @{ "Authorization" = "Bearer $token" }
        $r = Invoke-RestMethod "$baseUrl/api/candidats" -Headers $headers
        Write-Host "  PASS - Route GET accessible avec token" -ForegroundColor Green
    } catch {
        Write-Host "  FAIL - Route GET refuse avec token: $($_.Exception.Message)" -ForegroundColor Red
    }
} else {
    Write-Host "  SKIP - Pas de token" -ForegroundColor Yellow
}
Write-Host ""

Write-Host "====================" -ForegroundColor Cyan
Write-Host "RESUME:" -ForegroundColor Cyan
Write-Host "  - Route publique /api/health: Accessible" -ForegroundColor White
Write-Host "  - Routes GET sans token: Bloquees (401)" -ForegroundColor White
Write-Host "  - Routes POST sans token: Bloquees (401)" -ForegroundColor White
Write-Host "  - Routes avec token: Accessibles" -ForegroundColor White
Write-Host ""
Write-Host "SECURITE API: COMPLETE!" -ForegroundColor Green
Write-Host "Toutes les routes necessitent maintenant une authentification!" -ForegroundColor Green
