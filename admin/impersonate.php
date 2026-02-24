<?php
/**
 * Auto-login para superadmin (acceso con token de impersonación)
 * Solo funciona con un token válido generado por el panel superadmin
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/auth.php';

// Verificar que estamos en contexto de tienda
if (!defined('CURRENT_STORE_ID') || CURRENT_STORE_ID < 1) {
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

// Consumir token (lógica inline para no depender de platform-config)
$dir = dirname(__DIR__) . '/logs';
$file = $dir . '/impersonate_' . preg_replace('/[^a-f0-9]/', '', $token);
$data = null;
if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
    @unlink($file);
}
if (!$data || empty($data['store_id']) || empty($data['admin_id']) || (int) ($data['expires'] ?? 0) < time()) {
    header('Location: ' . ADMIN_URL . '/login.php?error=token_invalido');
    exit;
}

// Verificar que el token corresponde a esta tienda
if ((int) $data['store_id'] !== (int) CURRENT_STORE_ID) {
    header('Location: ' . ADMIN_URL . '/login.php?error=token_invalido');
    exit;
}

// Obtener usuario admin (fetchOneRaw evita inyección de store_id ya que lo pasamos explícito)
$user = fetchOneRaw(
    'SELECT id, username, email FROM admin_users WHERE id = :id AND store_id = :sid LIMIT 1',
    ['id' => (int) $data['admin_id'], 'sid' => (int) CURRENT_STORE_ID]
);

if (!$user) {
    header('Location: ' . ADMIN_URL . '/login.php?error=usuario_no_encontrado');
    exit;
}

// Iniciar sesión como este admin
startSecureSession();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user_id'] = $user['id'];
$_SESSION['admin_username'] = $user['username'];
$_SESSION['last_activity'] = time();

header('Location: ' . ADMIN_URL . '/index.php');
exit;
