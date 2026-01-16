<?php
/**
 * ============================================
 * HELPER: Autenticación y Seguridad
 * ============================================
 * Manejo de sesiones, CSRF, autenticación
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Iniciar sesión segura
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configuración segura de sesiones
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        
        session_name(SESSION_NAME);
        session_start();
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Generar token CSRF
 * @return string
 */
function generateCSRFToken() {
    startSecureSession();
    
    // Cargar helper de seguridad si está disponible
    if (file_exists(__DIR__ . '/security.php')) {
        require_once __DIR__ . '/security.php';
        if (function_exists('generateSecureCSRFToken')) {
            return generateSecureCSRFToken();
        }
    }
    
    // Fallback al método original
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_created'] = time();
    }
    
    // Regenerar token cada 30 minutos para mayor seguridad
    if (isset($_SESSION['csrf_token_created']) && 
        (time() - $_SESSION['csrf_token_created']) > 1800) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_created'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    startSecureSession();
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verificar si el usuario está autenticado
 * @return bool
 */
function isAuthenticated() {
    startSecureSession();
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    // Verificar expiración de sesión
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        logout();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Requerir autenticación (redirige si no está autenticado)
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

/**
 * Login de usuario
 * @param string $username
 * @param string $password
 * @return bool
 */
function login($username, $password) {
    startSecureSession();
    
    // Verificar intentos de login
    $attemptsKey = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
    if (isset($_SESSION[$attemptsKey])) {
        $attempts = $_SESSION[$attemptsKey];
        if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
            $timeLeft = LOGIN_LOCKOUT_TIME - (time() - $attempts['time']);
            if ($timeLeft > 0) {
                return false; // Usuario bloqueado
            } else {
                unset($_SESSION[$attemptsKey]);
            }
        }
    }
    
    // Buscar usuario en BD
    $sql = "SELECT * FROM admin_users WHERE username = :username LIMIT 1";
    $user = fetchOne($sql, ['username' => $username]);
    
    if (!$user) {
        incrementLoginAttempts($attemptsKey);
        return false;
    }
    
    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        incrementLoginAttempts($attemptsKey);
        return false;
    }
    
    // Login exitoso
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['last_activity'] = time();
    
    // Limpiar intentos
    unset($_SESSION[$attemptsKey]);
    
    // Actualizar último login
    $updateSql = "UPDATE admin_users SET last_login = NOW() WHERE id = :id";
    executeQuery($updateSql, ['id' => $user['id']]);
    
    return true;
}

/**
 * Incrementar intentos de login
 * @param string $key
 */
function incrementLoginAttempts($key) {
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    
    $_SESSION[$key]['count']++;
    $_SESSION[$key]['time'] = time();
}

/**
 * Cerrar sesión
 */
function logout() {
    startSecureSession();
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Obtener usuario actual
 * @return array|false
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return false;
    }
    
    if (!isset($_SESSION['admin_user_id'])) {
        return false;
    }
    
    $sql = "SELECT id, username, email FROM admin_users WHERE id = :id LIMIT 1";
    return fetchOne($sql, ['id' => $_SESSION['admin_user_id']]);
}

/**
 * Sanitizar entrada
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

