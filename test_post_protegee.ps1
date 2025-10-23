# Test d'une route POST protegee

$baseUrl = "http://localhost:8000"

# Obtenir un token d'abord
Write-Host "Obtention du token..." -ForegroundColor Yellow
try {
    $body = @{
        email = "candidat@test.com"
        password = "Password123!"
    } | ConvertTo-Json
    
    $r = Invoke-RestMethod "$baseUrl/api/auth/login-direct" -Method Post -Body $body -ContentType "application/json"
    
    if ($r.success -and $r.access_token) {
        $token = $r.access_token
        Write-Host "Token obtenu: $($token.Substring(0,50))..." -ForegroundColor Green
    }
} catch {
    Write-Host "Erreur lors de l'obtention du token: $($_.Exception.Message)" -ForegroundColor Red
    exit
}

# Test route POST protegee
Write-Host "Test route POST protegee..." -ForegroundColor Yellow
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
    Write-Host "SUCCESS: Route POST accessible avec token" -ForegroundColor Green
    Write-Host "Response: $($r.message)" -ForegroundColor Cyan
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        Write-Host "Status Code: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    }
}
