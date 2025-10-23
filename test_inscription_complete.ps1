# Script de test pour l'inscription complète

Write-Host "🧪 Test d'Inscription Complète" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Green
Write-Host ""

$url = "http://localhost:8000/api/auth/register"

$body = @{
    email = "nouveau.user@test.com"
    password = "Password123!"
    password_confirmation = "Password123!"
    nom = "Utilisateur"
    prenom = "Nouveau"
    contact = "0698765432"
    adresse = "456 Avenue de Test, Lyon"
    role = "candidat"
} | ConvertTo-Json

Write-Host "📋 Données d'inscription:" -ForegroundColor Cyan
Write-Host $body
Write-Host ""

Write-Host "🔄 Envoi de la requête..." -ForegroundColor Yellow

try {
    $response = Invoke-RestMethod -Uri $url -Method Post -Body $body -ContentType "application/json"
    
    Write-Host "✅ SUCCÈS !" -ForegroundColor Green
    Write-Host ""
    Write-Host "📊 Réponse complète:" -ForegroundColor Cyan
    $response | ConvertTo-Json -Depth 10 | Write-Host
    
    Write-Host ""
    Write-Host "👤 Informations Utilisateur:" -ForegroundColor Magenta
    Write-Host "   ID: $($response.user.id)" -ForegroundColor White
    Write-Host "   Email: $($response.user.email)" -ForegroundColor White
    Write-Host "   Rôle: $($response.user.role)" -ForegroundColor White
    Write-Host "   Créé le: $($response.user.created_at)" -ForegroundColor White
    
    Write-Host ""
    Write-Host "🧑 Données Personnelles:" -ForegroundColor Magenta
    Write-Host "   Nom complet: $($response.user.personne.nom_complet)" -ForegroundColor White
    Write-Host "   Nom: $($response.user.personne.nom)" -ForegroundColor White
    Write-Host "   Prénom: $($response.user.personne.prenom)" -ForegroundColor White
    Write-Host "   Email: $($response.user.personne.email)" -ForegroundColor White
    Write-Host "   Contact: $($response.user.personne.contact)" -ForegroundColor White
    Write-Host "   Adresse: $($response.user.personne.adresse)" -ForegroundColor White
    
    Write-Host ""
    Write-Host "🔐 Authentik:" -ForegroundColor Magenta
    Write-Host "   User ID: $($response.authentik.user_id)" -ForegroundColor White
    Write-Host "   Username: $($response.authentik.username)" -ForegroundColor White
    
    Write-Host ""
    Write-Host "🔗 URL d'authentification:" -ForegroundColor Cyan
    Write-Host "   $($response.auth_url)" -ForegroundColor White
    
} catch {
    Write-Host "❌ ERREUR !" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host ""
        Write-Host "Détails de l'erreur:" -ForegroundColor Yellow
        Write-Host $responseBody -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "✅ Test terminé" -ForegroundColor Green

