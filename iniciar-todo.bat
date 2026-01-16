@echo off
REM ============================================
REM Script para iniciar todo automáticamente
REM ============================================

echo.
echo ============================================
echo   Configurando LUME - Panel Admin
echo ============================================
echo.

REM Verificar que MySQL esté corriendo
echo [1/4] Verificando MySQL...
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
    echo ERROR: MySQL no está corriendo. Por favor inicia MySQL en XAMPP.
    pause
    exit /b 1
)
echo ✅ MySQL está corriendo

REM Crear base de datos si no existe
echo.
echo [2/4] Creando base de datos...
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS lume_catalogo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
echo ✅ Base de datos lista

REM Importar estructura
echo.
echo [3/4] Importando estructura...
C:\xampp\mysql\bin\mysql.exe -u root lume_catalogo < database.sql >nul 2>&1
echo ✅ Estructura importada

REM Crear usuario admin
echo.
echo [4/4] Creando usuario admin...
C:\xampp\php\php.exe fix-usuario-admin.php
echo.

echo ============================================
echo   ¡Todo configurado!
echo ============================================
echo.
echo Iniciando servidor PHP...
echo.
echo Panel Admin: http://localhost:8080/admin/login.php
echo Usuario: Gisela
echo Contraseña: Luky123!
echo.
echo Presiona Ctrl+C para detener el servidor
echo.

REM Iniciar servidor PHP
C:\xampp\php\php.exe -S localhost:8080 -t .

