<?php
/**
 * Autenticación para la plataforma Somos Tiendi
 * Maneja sesiones, login, registro y CSRF de usuarios de plataforma
 */

if (!defined('TIENDI_PLATFORM')) {
    die('Acceso directo no permitido');
}

function platformStartSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);

        session_name(PLATFORM_SESSION_NAME);
        session_start();

        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } elseif (time() - $_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }
}

function platformCSRFToken() {
    platformStartSession();
    if (empty($_SESSION['_platform_csrf']) || (time() - ($_SESSION['_platform_csrf_ts'] ?? 0)) > 3600) {
        $_SESSION['_platform_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['_platform_csrf_ts'] = time();
    }
    return $_SESSION['_platform_csrf'];
}

function platformValidateCSRF($token) {
    platformStartSession();
    return !empty($_SESSION['_platform_csrf']) && hash_equals($_SESSION['_platform_csrf'], $token);
}

function platformIsAuthenticated() {
    platformStartSession();

    if (empty($_SESSION['platform_user_id']) || empty($_SESSION['platform_logged_in'])) {
        return false;
    }

    if (isset($_SESSION['platform_last_activity']) &&
        (time() - $_SESSION['platform_last_activity']) > PLATFORM_SESSION_LIFETIME) {
        platformLogout();
        return false;
    }

    $_SESSION['platform_last_activity'] = time();
    return true;
}

function platformRequireAuth() {
    if (!platformIsAuthenticated()) {
        header('Location: ' . PLATFORM_PAGES_URL . '/login.php');
        exit;
    }
}

function platformRegister($name, $email, $password) {
    $email = strtolower(trim($email));

    $existing = platformFetchOne(
        'SELECT id FROM platform_users WHERE email = :email',
        ['email' => $email]
    );
    if ($existing) {
        return ['success' => false, 'error' => 'Ya existe una cuenta con ese email'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = platformQuery(
        'INSERT INTO platform_users (name, email, password) VALUES (:name, :email, :password)',
        ['name' => trim($name), 'email' => $email, 'password' => $hash]
    );

    if (!$stmt) {
        return ['success' => false, 'error' => 'Error al crear la cuenta'];
    }

    $userId = platformLastInsertId();
    platformSetSession($userId, trim($name), $email);

    return ['success' => true, 'user_id' => $userId];
}

function platformLogin($email, $password) {
    platformStartSession();

    $attemptsKey = 'platform_attempts_' . md5($email . ($_SERVER['REMOTE_ADDR'] ?? ''));
    if (!empty($_SESSION[$attemptsKey])) {
        $a = $_SESSION[$attemptsKey];
        if ($a['count'] >= PLATFORM_MAX_LOGIN_ATTEMPTS) {
            $timeLeft = PLATFORM_LOGIN_LOCKOUT_TIME - (time() - $a['time']);
            if ($timeLeft > 0) {
                return ['success' => false, 'error' => 'Demasiados intentos. Esperá ' . ceil($timeLeft / 60) . ' minuto(s).'];
            }
            unset($_SESSION[$attemptsKey]);
        }
    }

    $email = strtolower(trim($email));
    $user = platformFetchOne(
        'SELECT * FROM platform_users WHERE email = :email LIMIT 1',
        ['email' => $email]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        if (!isset($_SESSION[$attemptsKey])) {
            $_SESSION[$attemptsKey] = ['count' => 0, 'time' => time()];
        }
        $_SESSION[$attemptsKey]['count']++;
        $_SESSION[$attemptsKey]['time'] = time();
        return ['success' => false, 'error' => 'Email o contraseña incorrectos'];
    }

    unset($_SESSION[$attemptsKey]);
    platformQuery('UPDATE platform_users SET last_login = NOW() WHERE id = :id', ['id' => $user['id']]);
    platformSetSession($user['id'], $user['name'], $user['email']);

    return ['success' => true, 'user_id' => $user['id']];
}

function platformSetSession($userId, $name, $email) {
    platformStartSession();
    session_regenerate_id(true);
    $_SESSION['platform_logged_in'] = true;
    $_SESSION['platform_user_id'] = (int) $userId;
    $_SESSION['platform_user_name'] = $name;
    $_SESSION['platform_user_email'] = $email;
    $_SESSION['platform_last_activity'] = time();
}

function platformLogout() {
    platformStartSession();
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

function platformGetCurrentUser() {
    if (!platformIsAuthenticated()) return false;
    return platformFetchOne(
        'SELECT id, name, email, phone, email_verified, created_at, last_login FROM platform_users WHERE id = :id',
        ['id' => $_SESSION['platform_user_id']]
    );
}

function platformGetUserStores($userId) {
    return platformFetchAll(
        'SELECT s.*, sm.role FROM stores s
         INNER JOIN store_members sm ON sm.store_id = s.id
         WHERE sm.user_id = :uid ORDER BY s.created_at DESC',
        ['uid' => $userId]
    );
}

function platformSanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
