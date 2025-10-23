# Script pour ajouter les annotations de securite Swagger

Write-Host "=== AJOUT DES ANNOTATIONS DE SECURITE SWAGGER ===" -ForegroundColor Green
Write-Host ""

# Regenerer la documentation
Write-Host "Regeneration de la documentation Swagger..." -ForegroundColor Yellow
php artisan l5-swagger:generate

# Copier dans public
Copy-Item -Path storage/api-docs/api-docs.json -Destination public/api-docs.json -Force

Write-Host "Documentation Swagger mise a jour!" -ForegroundColor Green
Write-Host "Disponible a: http://localhost:8000/api/documentation" -ForegroundColor Cyan
Write-Host ""
Write-Host "Les routes protegees affichent maintenant:" -ForegroundColor Yellow
Write-Host "  - Le bouton Authorize pour saisir le token" -ForegroundColor White
Write-Host "  - L'icone de cadenas sur les routes protegees" -ForegroundColor White
Write-Host "  - Les reponses 401 dans la documentation" -ForegroundColor White