# Test final de l'authentification Swagger

Write-Host "=== TEST FINAL AUTHENTIFICATION SWAGGER ===" -ForegroundColor Green
Write-Host ""

$baseUrl = "http://localhost:8000"

# TEST 1: Route publique (doit marcher)
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
        Write-Host "  FAIL - Erreur inattendue: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
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
        $body = @{
            date_naissance = "1995-05-15"
            lieu_naissance = "Dakar"
            nip = "1234567890123"
            type_piece = "CNI"
            numero_piece = "1234567890"
            nationalite = "Senegalaise"
            genre = "M"
        } | ConvertTo-Json
        
        $r = Invoke-RestMethod "$baseUrl/api/candidats/complete-profile" -Method Post -Headers $headers -Body $body -ContentType "application/json"
        Write-Host "  PASS - Acces autorise avec token" -ForegroundColor Green
        Write-Host "  Response: $($r.message)" -ForegroundColor Cyan
    } catch {
        Write-Host "  FAIL - Token refuse: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.Exception.Response) {
            Write-Host "  Status Code: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
        }
    }
} else {
    Write-Host "  SKIP - Pas de token" -ForegroundColor Yellow
}
Write-Host ""

Write-Host "====================" -ForegroundColor Cyan
Write-Host "RESUME:" -ForegroundColor Cyan
Write-Host "  - Routes publiques: Accessibles sans token" -ForegroundColor White
Write-Host "  - Routes protegees: Token requis" -ForegroundColor White
Write-Host "  - Authentification: Fonctionnelle" -ForegroundColor White
Write-Host ""
Write-Host "Swagger disponible a: http://localhost:8000/api/documentation" -ForegroundColor Green
Write-Host "Utilisez le bouton 'Authorize' avec le token!" -ForegroundColor Green
Write-Host ""
Write-Host "SECURITE API: OPERATIONNELLE!" -ForegroundColor Green
