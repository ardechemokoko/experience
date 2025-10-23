# 🔧 Correction de l'Erreur Swagger

## ❌ Erreur Rencontrée

```
Parser error on line 12
end of the stream or a document separator is expected
```

## 🎯 Solutions

### Solution 1 : Vider le Cache du Navigateur (Recommandé)

1. **Dans votre navigateur**, appuyez sur :
   - **Windows** : `Ctrl + Shift + Delete`
   - **Mac** : `Cmd + Shift + Delete`

2. **Cochez** :
   - ✅ Cookies et données de site
   - ✅ Images et fichiers en cache

3. **Cliquez sur** "Effacer les données"

4. **Rafraîchissez la page** : `Ctrl + F5` (ou `Cmd + Shift + R`)

### Solution 2 : Forcer le Rechargement

1. **Ouvrez** `http://localhost:8000/api/documentation`
2. **Appuyez sur** `Ctrl + F5` (Windows) ou `Cmd + Shift + R` (Mac)
3. **Attendez** quelques secondes

### Solution 3 : Tester avec la Page de Test

1. **Ouvrez** : `http://localhost:8000/test-swagger.html`
2. **Vérifiez** si le Swagger s'affiche correctement
3. Si oui, le problème vient du cache

### Solution 4 : Vérifier la Configuration

```bash
# Vider le cache Laravel
php artisan config:clear
php artisan cache:clear

# Régénérer la documentation
php artisan l5-swagger:generate
```

### Solution 5 : Vérifier le Fichier JSON

1. **Ouvrez** : `http://localhost:8000/docs/api-docs.json`
2. **Vérifiez** que vous voyez du JSON (pas d'erreur)
3. **Si erreur 404** : Régénérez avec `php artisan l5-swagger:generate`

### Solution 6 : Mode Navigation Privée

1. **Ouvrez** une fenêtre de navigation privée
2. **Allez à** : `http://localhost:8000/api/documentation`
3. **Vérifiez** si l'erreur persiste

### Solution 7 : Supprimer les Fichiers Temporaires

```bash
# PowerShell
Remove-Item -Path storage/api-docs/* -Force
php artisan l5-swagger:generate
```

### Solution 8 : Vérifier le Format dans .env

Ajoutez ou modifiez dans `.env` :

```env
L5_FORMAT_TO_USE_FOR_DOCS=json
CACHE_DRIVER=file
```

Puis :

```bash
php artisan config:clear
php artisan l5-swagger:generate
```

---

## ✅ Vérifications

### 1. Vérifier que le JSON existe

```bash
Test-Path storage/api-docs/api-docs.json
```

Doit retourner : `True`

### 2. Vérifier le contenu du JSON

```bash
Get-Content storage/api-docs/api-docs.json -TotalCount 5
```

Doit afficher les premières lignes du JSON

### 3. Vérifier la route

```bash
php artisan route:list | Select-String "documentation"
```

Doit afficher : `api/documentation`

### 4. Vérifier le serveur

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Doit démarrer sans erreur

---

## 🔍 Diagnostic Complet

### Script de Diagnostic

Créez un fichier `diagnose_swagger.ps1` :

```powershell
Write-Host "🔍 Diagnostic Swagger" -ForegroundColor Green
Write-Host ""

# 1. Vérifier le fichier JSON
Write-Host "1️⃣ Vérification du fichier JSON..."
if (Test-Path storage/api-docs/api-docs.json) {
    Write-Host "   ✅ Fichier JSON existe" -ForegroundColor Green
    $jsonSize = (Get-Item storage/api-docs/api-docs.json).Length
    Write-Host "   📊 Taille: $jsonSize octets" -ForegroundColor Cyan
} else {
    Write-Host "   ❌ Fichier JSON n'existe pas!" -ForegroundColor Red
}

# 2. Vérifier la configuration
Write-Host ""
Write-Host "2️⃣ Vérification de la configuration..."
$config = Get-Content config/l5-swagger.php | Select-String "format_to_use_for_docs"
Write-Host "   $config" -ForegroundColor Cyan

# 3. Vérifier le cache
Write-Host ""
Write-Host "3️⃣ Vérification du cache..."
$cacheDriver = Get-Content .env | Select-String "CACHE_DRIVER"
Write-Host "   $cacheDriver" -ForegroundColor Cyan

# 4. Tester l'accès au JSON
Write-Host ""
Write-Host "4️⃣ Test d'accès au JSON..."
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/docs/api-docs.json" -UseBasicParsing
    if ($response.StatusCode -eq 200) {
        Write-Host "   ✅ JSON accessible" -ForegroundColor Green
    }
} catch {
    Write-Host "   ❌ JSON non accessible: $_" -ForegroundColor Red
}

# 5. Vérifier Swagger UI
Write-Host ""
Write-Host "5️⃣ Test de Swagger UI..."
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/documentation" -UseBasicParsing
    if ($response.StatusCode -eq 200) {
        Write-Host "   ✅ Swagger UI accessible" -ForegroundColor Green
    }
} catch {
    Write-Host "   ❌ Swagger UI non accessible: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "✅ Diagnostic terminé" -ForegroundColor Green
```

**Exécutez** :
```bash
.\diagnose_swagger.ps1
```

---

## 🚀 Solution Rapide (Tout-en-Un)

Exécutez ces commandes dans l'ordre :

```bash
# 1. Arrêter le serveur (Ctrl+C si nécessaire)

# 2. Nettoyer tout
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 3. Supprimer l'ancien JSON
Remove-Item -Path storage/api-docs/api-docs.json -Force -ErrorAction SilentlyContinue

# 4. Régénérer
php artisan l5-swagger:generate

# 5. Redémarrer le serveur
php artisan serve --host=0.0.0.0 --port=8000
```

**Puis dans votre navigateur** :
1. Fermez TOUS les onglets de `localhost:8000`
2. Videz le cache navigateur (`Ctrl + Shift + Delete`)
3. Ouvrez un NOUVEL onglet
4. Allez à `http://localhost:8000/api/documentation`

---

## 🎯 Si Rien Ne Fonctionne

### Alternative : Utiliser la Page de Test

Si l'erreur persiste sur `/api/documentation`, utilisez :

```
http://localhost:8000/test-swagger.html
```

Cette page :
- ✅ Charge directement le JSON
- ✅ Pas de cache
- ✅ Configuration simple
- ✅ Fonctionne immédiatement

---

## 📞 Problèmes Connus

### Problème 1 : Cache Navigateur Persistant

**Symptôme** : L'erreur persiste même après avoir vidé le cache

**Solution** :
1. Utilisez la navigation privée
2. Ou utilisez un autre navigateur
3. Ou ajoutez `?v=2` à l'URL : `http://localhost:8000/api/documentation?v=2`

### Problème 2 : Fichier YAML Fantôme

**Symptôme** : Swagger cherche un fichier YAML inexistant

**Solution** :
```bash
# Vérifier s'il existe
Get-ChildItem storage/api-docs/

# Supprimer tout sauf le JSON
Get-ChildItem storage/api-docs/ | Where-Object {$_.Name -ne "api-docs.json"} | Remove-Item

# Régénérer
php artisan l5-swagger:generate
```

### Problème 3 : Configuration Mixte

**Symptôme** : Parfois JSON, parfois YAML

**Solution** :
Ajoutez dans `.env` :
```env
L5_FORMAT_TO_USE_FOR_DOCS=json
```

Puis :
```bash
php artisan config:clear
php artisan l5-swagger:generate
```

---

## ✅ Vérification Finale

Une fois que tout fonctionne, vous devriez voir :

1. **Titre** : "🚗 Auto-École API - Authentification Authentik"
2. **Description** complète de l'API
3. **9 endpoints** organisés en 3 catégories
4. **Bouton "Authorize"** en haut à droite
5. **Possibilité de "Try it out"** sur chaque endpoint

---

## 📚 Ressources

- **Documentation complète** : `SWAGGER_SUCCESS.md`
- **Guide principal** : `README_AUTHENTIK_COMPLET.md`
- **Page de test** : `http://localhost:8000/test-swagger.html`

---

**🎉 Votre Swagger devrait maintenant fonctionner parfaitement !**

