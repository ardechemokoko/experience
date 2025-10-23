# Script pour régénérer et copier le Swagger JSON

Write-Host "🚀 Régénération de la documentation Swagger..." -ForegroundColor Green

# Générer la documentation
php artisan l5-swagger:generate

# Copier dans public
Copy-Item -Path storage/api-docs/api-docs.json -Destination public/api-docs.json -Force

Write-Host "✅ Documentation générée et copiée avec succès!" -ForegroundColor Green
Write-Host "📄 Fichier disponible à: http://localhost:8000/api-docs.json" -ForegroundColor Cyan
Write-Host "📖 Documentation disponible à: http://localhost:8000/api/documentation" -ForegroundColor Cyan

