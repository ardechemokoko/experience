# Test d'inscription avec PowerShell
$body = @{
    email = "candidat@test.com"
    password = "Password123!"
    password_confirmation = "Password123!"
    nom = "Candidat"
    prenom = "Test"
    contact = "0600000000"
    role = "candidat"
} | ConvertTo-Json

Write-Host "Test d'inscription de candidat@test.com"

try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/register" -Method POST -Body $body -ContentType "application/json"
    Write-Host "SUCCES !"
    Write-Host "Reponse: $($response | ConvertTo-Json -Depth 3)"
} catch {
    Write-Host "ERREUR:"
    Write-Host $_.Exception.Message
}