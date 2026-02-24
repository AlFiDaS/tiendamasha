<?php
/**
 * Configuración de la plataforma Somos Tiendi
 * Se usa en las páginas de platform/ (login, register, dashboard, etc.)
 */

if (!defined('TIENDI_PLATFORM')) {
    define('TIENDI_PLATFORM', true);
}

require_once dirname(__FILE__) . '/helpers/env.php';
loadEnv();

// Detección de entorno
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? __FILE__;
$isProduction = (strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false) ||
                (strpos($scriptFile, 'htdocs') !== false && strpos($host, 'localhost') === false);

// Errores
if ($isProduction) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__FILE__) . '/logs/platform-errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// BD de la plataforma
define('PLATFORM_DB_HOST', env('PLATFORM_DB_HOST', 'localhost'));
define('PLATFORM_DB_NAME', env('PLATFORM_DB_NAME', 'somostiendi_platform'));
define('PLATFORM_DB_USER', env('PLATFORM_DB_USER', 'root'));
define('PLATFORM_DB_PASS', env('PLATFORM_DB_PASS', ''));
define('PLATFORM_DB_CHARSET', 'utf8mb4');

// BD para crear tiendas nuevas (mismas credenciales con permisos de CREATE DATABASE)
define('STORE_DB_HOST', env('DB_HOST', 'localhost'));
define('STORE_DB_USER', env('DB_USER', 'root'));
define('STORE_DB_PASS', env('DB_PASS', ''));

// Rutas
define('PLATFORM_BASE_PATH', dirname(__FILE__));

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
if (isset($_SERVER['HTTP_HOST'])) {
    $urlHost = $_SERVER['HTTP_HOST'];
} elseif (isset($_SERVER['SERVER_NAME'])) {
    $urlHost = $_SERVER['SERVER_NAME'];
    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
        $urlHost .= ':' . $_SERVER['SERVER_PORT'];
    }
} else {
    $urlHost = 'localhost:8080';
}

define('PLATFORM_URL', $protocol . '://' . $urlHost);
define('PLATFORM_PAGES_URL', PLATFORM_URL . '/platform');

// Sesión
define('PLATFORM_SESSION_NAME', 'TIENDI_PLATFORM_SESSION');
define('PLATFORM_SESSION_LIFETIME', 3600 * 24 * 7); // 7 días

// Seguridad
define('PLATFORM_MAX_LOGIN_ATTEMPTS', 5);
define('PLATFORM_LOGIN_LOCKOUT_TIME', 900);

// Super Admin - emails con acceso al panel de administración global
// Podés agregar más separados por coma en el .env: "email1@x.com,email2@x.com"
define('PLATFORM_SUPERADMIN_EMAILS', env('SUPERADMIN_EMAILS', ''));

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Helpers de plataforma
require_once PLATFORM_BASE_PATH . '/helpers/platform-db.php';
require_once PLATFORM_BASE_PATH . '/helpers/platform-auth.php';
require_once PLATFORM_BASE_PATH . '/helpers/superadmin.php';
