<?php
/**
 * ============================================
 * CONFIGURACIÓN: Somos Tiendi
 * ============================================
 * Archivo de configuración central
 * Soporta contexto de tienda dinámico (multi-tenant)
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

require_once dirname(__FILE__) . '/helpers/env.php';
loadEnv();

// ============================================
// DETECCIÓN DE ENTORNO
// ============================================
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? __FILE__;
$isProduction = (strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false) ||
                (strpos($scriptFile, 'htdocs') !== false && strpos($host, 'localhost') === false);

// ============================================
// CONFIGURACIÓN DE ERRORES
// ============================================
if ($isProduction) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__FILE__) . '/logs/php-errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================
// Si store-router.php ya configuró el contexto de tienda, usamos su db_name
// pero las credenciales de conexión son las mismas (mismo servidor MySQL)
if (defined('STORE_CONTEXT_LOADED') && defined('CURRENT_STORE_DB')) {
    define('DB_HOST', env('DB_HOST', 'localhost'));
    define('DB_NAME', CURRENT_STORE_DB);
    define('DB_USER', env('DB_USER', 'root'));
    define('DB_PASS', env('DB_PASS', ''));
} else {
    define('DB_HOST', env('DB_HOST', 'localhost'));
    define('DB_NAME', env('DB_NAME', 'lume_catalogo'));
    define('DB_USER', env('DB_USER', 'root'));
    define('DB_PASS', env('DB_PASS', ''));
}
define('DB_CHARSET', 'utf8mb4');

// ============================================
// RUTAS DEL SISTEMA
// ============================================
define('BASE_PATH', dirname(__FILE__));

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
} elseif (isset($_SERVER['SERVER_NAME'])) {
    $host = $_SERVER['SERVER_NAME'];
    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
        $host .= ':' . $_SERVER['SERVER_PORT'];
    }
} else {
    $host = 'localhost:8080';
}

define('BASE_URL', $protocol . '://' . $host);

// URLs dinámicas según contexto de tienda
if (defined('CURRENT_STORE_SLUG')) {
    $storeBase = '/' . CURRENT_STORE_SLUG;
    define('STORE_URL', BASE_URL . $storeBase);
    define('ADMIN_PATH', BASE_PATH . '/admin');
    define('ADMIN_URL', BASE_URL . $storeBase . '/admin');
    define('API_URL', BASE_URL . $storeBase . '/api');
} else {
    define('STORE_URL', BASE_URL);
    define('ADMIN_PATH', BASE_PATH . '/admin');
    define('ADMIN_URL', BASE_URL . '/admin');
    define('API_URL', BASE_URL . '/api');
}

if ($isProduction) {
    define('IMAGES_PATH', BASE_PATH . '/images');
} else {
    define('IMAGES_PATH', BASE_PATH . '/public/images');
}

if (defined('CURRENT_STORE_SLUG')) {
    define('IMAGES_URL', BASE_URL . '/' . CURRENT_STORE_SLUG . '/images');
} else {
    define('IMAGES_URL', BASE_URL . '/images');
}

define('API_PATH', BASE_PATH . '/api');

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================
if (!defined('CSRF_SECRET')) {
    $csrfSecretFile = BASE_PATH . '/.csrf_secret';
    if (file_exists($csrfSecretFile) && is_readable($csrfSecretFile)) {
        $secret = trim(file_get_contents($csrfSecretFile));
        if (strlen($secret) >= 32) {
            define('CSRF_SECRET', $secret);
        } else {
            $newSecret = bin2hex(random_bytes(32));
            @file_put_contents($csrfSecretFile, $newSecret, LOCK_EX);
            define('CSRF_SECRET', $newSecret);
        }
    } else {
        $newSecret = bin2hex(random_bytes(32));
        @file_put_contents($csrfSecretFile, $newSecret, LOCK_EX);
        @chmod($csrfSecretFile, 0600);
        define('CSRF_SECRET', $newSecret);
    }
}

define('SESSION_NAME', 'LUME_ADMIN_SESSION');
define('SESSION_LIFETIME', 3600 * 24);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

// ============================================
// CONFIGURACIÓN DE SUBIDA DE ARCHIVOS
// ============================================
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// ============================================
// CONFIGURACIÓN DE MERCADOPAGO (desde .env)
// ============================================
define('MERCADOPAGO_ACCESS_TOKEN', env('MERCADOPAGO_ACCESS_TOKEN', ''));
define('MERCADOPAGO_TEST_MODE', env('MERCADOPAGO_TEST_MODE', true));

// ============================================
// CONFIGURACIÓN DE TELEGRAM (desde .env)
// ============================================
define('TELEGRAM_BOT_TOKEN', env('TELEGRAM_BOT_TOKEN', ''));
define('TELEGRAM_CHAT_ID', env('TELEGRAM_CHAT_ID', ''));

// ============================================
// CONFIGURACIÓN GENERAL
// ============================================
define('SITE_NAME', 'Somos Tiendi');
define('TIMEZONE', 'America/Argentina/Buenos_Aires');
date_default_timezone_set(TIMEZONE);

// ============================================
// AUTOLOAD DE HELPERS
// ============================================
$helpersPath = BASE_PATH . '/helpers';
if (is_dir($helpersPath)) {
    require_once $helpersPath . '/db.php';
    require_once $helpersPath . '/auth.php';
}
