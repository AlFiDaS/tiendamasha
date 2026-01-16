# Script PowerShell para iniciar servidor PHP en puerto 8081
Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Iniciando Servidor PHP - LUME Admin" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Configurar ruta de PHP de XAMPP
$PHP_PATH = "C:\xampp\php\php.exe"

# Verificar si PHP de XAMPP existe
if (-not (Test-Path $PHP_PATH)) {
    Write-Host "ERROR: No se encontró PHP en $PHP_PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "Verifica que XAMPP esté instalado" -ForegroundColor Yellow
    Read-Host "Presiona Enter para salir"
    exit 1
}

Write-Host "Servidor iniciado en: http://localhost:8081" -ForegroundColor Green
Write-Host ""
Write-Host "Panel Admin: http://localhost:8081/admin/login.php" -ForegroundColor Cyan
Write-Host "API: http://localhost:8081/api/products.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Presiona Ctrl+C para detener el servidor" -ForegroundColor Yellow
Write-Host ""

# Iniciar servidor PHP usando PHP de XAMPP con router en puerto 8081
& $PHP_PATH -S localhost:8081 router.php

