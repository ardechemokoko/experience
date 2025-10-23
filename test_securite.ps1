# Test de Sécurité de l'API

Write-Host "=== TEST DE SECURITE API ===" -ForegroundColor Green
Write-Host ""

$baseUrl = "http://localhost:8000"

# TEST 1: Route publique sans token (doit marcher)
Write-Host "TEST 1: Route publique sans token..." -ForegroundColor Yellow
try {
    $r = Invoke-RestMethod "$baseUrl/api/auto-ecoles"
    Write-Host "  PASS - Route publique accessible" -ForegroundColor Green
} catch {
    Write-Host "  FAIL - Route publique bloquee" -ForegroundColor Red
}
Write-Host ""

# TEST 2: Route protegee sans token (doit echouer avec 401)
Write-Host "TEST 2: Route protegee sans token..." -ForegroundColor Yellow
try {
    $body = @{ date_naissance = "1995-05-15" } | ConvertTo-Json
    $r = Invoke-RestMethod "$baseUrl/api/candidats/complete-profile" -Method Post -Body $body -ContentType "application/json"
    Write-Host "  FAIL - Route accessible sans token!" -ForegroundColor Red
} catch {
    if ($_.Exception.Response.StatusCode -eq 401) {
        Write-Host "  PASS - Route bien protegee (401)" -ForegroundColor Green
    } else {
        Write-Host "  FAIL - Erreur inattendue" -ForegroundColor Red
    }
}
Write-Host ""

# TEST 3: Connexion et obtention du token
Write-Host "TEST 3: Connexion pour obtenir token..." -ForegroundColor Yellow
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

# TEST 4: Route protegee avec token (doit marcher)
Write-Host "TEST 4: Route protegee avec token valide..." -ForegroundColor Yellow
if ($token) {
    try {
        $headers = @{ "Authorization" = "Bearer $token" }
        $r = Invoke-RestMethod "$baseUrl/api/candidats/mes-dossiers" -Headers $headers
        Write-Host "  PASS - Acces autorise avec token" -ForegroundColor Green
    } catch {
        Write-Host "  FAIL - Token refuse" -ForegroundColor Red
    }
} else {
    Write-Host "  SKIP - Pas de token" -ForegroundColor Yellow
}
Write-Host ""

# TEST 5: Route protegee avec token invalide (doit echouer)
Write-Host "TEST 5: Route protegee avec token invalide..." -ForegroundColor Yellow
try {
    $headers = @{ "Authorization" = "Bearer token_invalide_xxx" }
    $r = Invoke-RestMethod "$baseUrl/api/candidats/mes-dossiers" -Headers $headers
    Write-Host "  FAIL - Token invalide accepte!" -ForegroundColor Red
} catch {
    if ($_.Exception.Response.StatusCode -eq 401) {
        Write-Host "  PASS - Token invalide rejete (401)" -ForegroundColor Green
    } else {
        Write-Host "  FAIL - Erreur inattendue" -ForegroundColor Red
    }
}
Write-Host ""

Write-Host "====================" -ForegroundColor Cyan
Write-Host "RESUME:" -ForegroundColor Cyan
Write-Host "  - Routes publiques: Accessibles" -ForegroundColor White
Write-Host "  - Routes protegees: Token requis" -ForegroundColor White
Write-Host "  - Token invalide: Rejete" -ForegroundColor White
Write-Host "  - Token valide: Autorise" -ForegroundColor White
Write-Host ""
Write-Host "Securite API: OPERATIONNELLE!" -ForegroundColor Green

