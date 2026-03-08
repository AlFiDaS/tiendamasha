<?php
/**
 * DEBUG TEMPORAL - Diagnóstico del dashboard
 * Eliminá este archivo después de resolver el problema
 */
require_once __DIR__ . '/../platform-config.php';
platformStartSession();

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="background:#1e1e1e; color:#d4d4d4; padding:1.5rem; font-family:monospace; font-size:13px;">';

echo "=== DIAGNÓSTICO DASHBOARD ===\n\n";

// 1. Sesión
echo "1. SESIÓN:\n";
echo "   - platform_user_id: " . (isset($_SESSION['platform_user_id']) ? $_SESSION['platform_user_id'] : 'NO DEFINIDO') . "\n";
echo "   - platform_user_email: " . (isset($_SESSION['platform_user_email']) ? $_SESSION['platform_user_email'] : 'NO DEFINIDO') . "\n";
echo "   - Autenticado: " . (platformIsAuthenticated() ? 'SÍ' : 'NO') . "\n\n";

if (!platformIsAuthenticated()) {
    echo "⚠️ NO ESTÁS LOGUEADO. Iniciá sesión en /platform/login.php primero.\n";
    echo "\n</pre>";
    exit;
}

$userId = (int) $_SESSION['platform_user_id'];

// 2. Usuario en BD
echo "2. USUARIO EN BD:\n";
$user = platformFetchOne('SELECT id, email, name FROM platform_users WHERE id = :id', ['id' => $userId]);
if ($user) {
    echo "   - ID: " . $user['id'] . "\n";
    echo "   - Email: " . $user['email'] . "\n";
    echo "   - Nombre: " . $user['name'] . "\n";
} else {
    echo "   ⚠️ Usuario NO encontrado en platform_users con id=$userId\n";
}
echo "\n";

// 3. Tiendas donde es owner
echo "3. TIENDAS (owner_id = $userId):\n";
$storesByOwner = platformFetchAll('SELECT id, slug, owner_id FROM stores WHERE owner_id = :uid', ['uid' => $userId]);
echo "   Cantidad: " . count($storesByOwner) . "\n";
foreach ($storesByOwner as $s) {
    echo "   - #{$s['id']} slug={$s['slug']} owner_id={$s['owner_id']}\n";
}
echo "\n";

// 4. store_members
echo "4. STORE_MEMBERS (user_id = $userId):\n";
$memberships = platformFetchAll('SELECT store_id, user_id, role FROM store_members WHERE user_id = :uid', ['uid' => $userId]);
echo "   Cantidad: " . count($memberships) . "\n";
foreach ($memberships as $m) {
    echo "   - store_id={$m['store_id']} role={$m['role']}\n";
}
echo "\n";

// 5. Resultado de platformGetUserStores
echo "5. platformGetUserStores($userId):\n";
$stores = platformGetUserStores($userId);
echo "   Cantidad: " . count($stores) . "\n";
foreach ($stores as $s) {
    echo "   - {$s['slug']} (plan: {$s['plan']}, role: {$s['role']})\n";
}
echo "\n";

// 6. Base de datos
echo "6. CONEXIÓN BD:\n";
echo "   - PLATFORM_DB_NAME: " . (defined('PLATFORM_DB_NAME') ? PLATFORM_DB_NAME : 'no definido') . "\n";
echo "\n";

echo "=== FIN DIAGNÓSTICO ===\n";
echo "\nSi ves tiendas en punto 3 o 4 pero 0 en punto 5, hay un bug en la consulta.\n";
echo "Si punto 1 muestra 'NO DEFINIDO', no estás logueado correctamente.\n";
echo "</pre>";
