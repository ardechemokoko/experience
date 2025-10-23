# Test direct de la route protegee

$baseUrl = "http://localhost:8000"
$token = "eyJ1c2VyX2lkIjoyOCwiZW1haWwiOiJjYW5kaWRhdEB0ZXN0LmNvbSIsInJvbGUiOiJjYW5kaWRhdCIsImlhdCI6MTczNzc0OTI0MCwiZXhwIjoxNzM3NzUyODQwfQ.eyJzaWduYXR1cmUiOiJhYmNkZWYxMjM0NTY3ODkwYWJjZGVmMTIzNDU2Nzg5MGFiY2RlZjEyMzQ1Njc4OTAifQ"

Write-Host "Test de la route protegee avec token..." -ForegroundColor Yellow

try {
    $headers = @{ "Authorization" = "Bearer $token" }
    $r = Invoke-RestMethod "$baseUrl/api/candidats/mes-dossiers" -Headers $headers
    Write-Host "SUCCESS: Route accessible avec token" -ForegroundColor Green
    Write-Host "Response: $($r | ConvertTo-Json)" -ForegroundColor Cyan
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        Write-Host "Status Code: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    }
}
