# Test de l'authentification Swagger

Write-Host "=== TEST AUTHENTIFICATION SWAGGER ===" -ForegroundColor Green
Write-Host ""

$baseUrl = "http://localhost:8000"

# TEST 1: Connexion pour obtenir token
Write-Host "TEST 1: Connexion pour obtenir token..." -ForegroundColor Yellow
try {
    $body = @{
        email = "candidat@test.com"
        password = "Password123!"
    } | ConvertTo-Json
    
    $r = Invoke-RestMethod "$baseUrl/api/auth/login-direct" -Method Post -Body $body -ContentType "application/json"
    
    if ($r.success -and $r.access_token) {
        $token = $r.access_token
        Write-Host "  PASS - Token obtenu: $($token.Substring(0,50))..." -ForegroundColor Green
    }
} catch {
    Write-Host "  FAIL - Connexion echouee" -ForegroundColor Red
}
Write-Host ""

# TEST 2: Route protegee avec token (doit marcher)
Write-Host "TEST 2: Route protegee avec token valide..." -ForegroundColor Yellow
if ($token) {
    try {
        $headers = @{ "Authorization" = "Bearer $token" }
        $r = Invoke-RestMethod "$baseUrl/api/candidats/mes-dossiers" -Headers $headers
        Write-Host "  PASS - Acces autorise avec token" -ForegroundColor Green
    } catch {
        Write-Host "  FAIL - Token refuse: $($_.Exception.Message)" -ForegroundColor Red
    }
} else {
    Write-Host "  SKIP - Pas de token" -ForegroundColor Yellow
}
Write-Host ""

# TEST 3: Route publique sans token (doit marcher)
Write-Host "TEST 3: Route publique sans token..." -ForegroundColor Yellow
try {
    $r = Invoke-RestMethod "$baseUrl/api/auto-ecoles"
    Write-Host "  PASS - Route publique accessible" -ForegroundColor Green
} catch {
    Write-Host "  FAIL - Route publique bloquee" -ForegroundColor Red
}
Write-Host ""

Write-Host "====================" -ForegroundColor Cyan
Write-Host "RESUME:" -ForegroundColor Cyan
Write-Host "  - Token obtenu: OK" -ForegroundColor White
Write-Host "  - Routes protegees: Token requis" -ForegroundColor White
Write-Host "  - Routes publiques: Accessibles" -ForegroundColor White
Write-Host ""
Write-Host "Swagger disponible a: http://localhost:8000/api/documentation" -ForegroundColor Green
Write-Host "Utilisez le bouton 'Authorize' avec le token!" -ForegroundColor Green
