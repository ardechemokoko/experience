# Script pour regenerer et copier le Swagger JSON

Write-Host "Regeneration de la documentation Swagger..." -ForegroundColor Green

# Generer la documentation
php artisan l5-swagger:generate

# Copier dans public
Copy-Item -Path storage/api-docs/api-docs.json -Destination public/api-docs.json -Force

Write-Host "Documentation generee et copiee avec succes!" -ForegroundColor Green
Write-Host "Fichier disponible a: http://localhost:8000/api-docs.json" -ForegroundColor Cyan
Write-Host "Documentation disponible a: http://localhost:8000/api/documentation" -ForegroundColor Cyan