# Test Simple de Toutes les Routes

Write-Host "=== TEST COMPLET API AUTHENTIK ===" -ForegroundColor Green
Write-Host ""

$baseUrl = "http://localhost:8000"
$passed = 0
$failed = 0

# TEST 1: Health Check
Write-Host "TEST 1: Health Check..." -ForegroundColor Yellow
try {
    $r = Invoke-RestMethod "$baseUrl/api/health"
    if ($r.status -eq "ok") {
        Write-Host "  PASS - API OK" -ForegroundColor Green
        $passed++
    }
} catch {
    Write-Host "  FAIL" -ForegroundColor Red
    $failed++
}
Write-Host ""

# TEST 2: Inscription
Write-Host "TEST 2: Inscription..." -ForegroundColor Yellow
$time = Get-Date -Format "HHmmss"
$body = @{
    email = "test.$time@test.com"
    password = "Test123!"
    password_confirmation = "Test123!"
    nom = "Test"
    prenom = "User"
    contact = "0612345678"
    adresse = "Test"
    role = "candidat"
} | ConvertTo-Json

try {
    $r = Invoke-RestMethod "$baseUrl/api/auth/register" -Method Post -Body $body -ContentType "application/json"
    if ($r.success -and $r.user.personne.nom_complet) {
        Write-Host "  PASS - User: $($r.user.personne.nom_complet)" -ForegroundColor Green
        $passed++
    }
} catch {
    Write-Host "  FAIL - $($_.Exception.Message)" -ForegroundColor Red
    $failed++
}
Write-Host ""

# TEST 3: Connexion
Write-Host "TEST 3: Connexion Directe..." -ForegroundColor Yellow
$body = @{
    email = "candidat@test.com"
    password = "Password123!"
} | ConvertTo-Json

try {
    $r = Invoke-RestMethod "$baseUrl/api/auth/login-direct" -Method Post -Body $body -ContentType "application/json"
    if ($r.success -and $r.access_token) {
        $token = $r.access_token
        Write-Host "  PASS - Token obtenu" -ForegroundColor Green
        Write-Host "  User: $($r.user.personne.nom_complet)" -ForegroundColor Cyan
        $passed++
    }
} catch {
    Write-Host "  FAIL" -ForegroundColor Red
    $failed++
}
Write-Host ""

# TEST 4: Auth URL
Write-Host "TEST 4: URL Authentification..." -ForegroundColor Yellow
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

# TEST 5: Profil (protege)
Write-Host "TEST 5: Profil Utilisateur..." -ForegroundColor Yellow
if ($token) {
    try {
        $headers = @{ "Authorization" = "Bearer $token" }
        $r = Invoke-RestMethod "$baseUrl/api/auth/me" -Headers $headers
        if ($r.success -and $r.user) {
            Write-Host "  PASS - Profil: $($r.user.email)" -ForegroundColor Green
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

# RESUME
Write-Host "==================" -ForegroundColor Cyan
Write-Host "TESTS PASSES: $passed" -ForegroundColor Green
Write-Host "TESTS ECHOUES: $failed" -ForegroundColor Red
$total = $passed + $failed
$rate = [math]::Round(($passed / $total) * 100, 0)
Write-Host "TAUX: $rate%" -ForegroundColor $(if ($rate -ge 80) { "Green" } else { "Yellow" })
Write-Host ""

if ($failed -eq 0) {
    Write-Host "TOUTES LES ROUTES FONCTIONNENT!" -ForegroundColor Green
} else {
    Write-Host "Certaines routes ont echoue" -ForegroundColor Yellow
}

