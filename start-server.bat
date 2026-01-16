@echo off
REM ============================================
REM Iniciar servidor PHP integrado
REM ============================================

echo.
echo ============================================
echo   Iniciando Servidor PHP - LUME Admin
echo ============================================
echo.

REM Configurar ruta de PHP de XAMPP
set PHP_PATH=C:\xampp\php\php.exe

REM Verificar si PHP de XAMPP existe
if not exist "%PHP_PATH%" (
    echo ERROR: No se encontró PHP en %PHP_PATH%
    echo.
    echo Verifica que XAMPP esté instalado
    pause
    exit /b 1
)

echo Servidor iniciado en: http://localhost:8080
echo.
echo Panel Admin: http://localhost:8080/admin/login.php
echo API: http://localhost:8080/api/products.php
echo.
echo Presiona Ctrl+C para detener el servidor
echo.

REM Iniciar servidor PHP en puerto 8080 usando PHP de XAMPP con router
"%PHP_PATH%" -S localhost:8080 router.php

