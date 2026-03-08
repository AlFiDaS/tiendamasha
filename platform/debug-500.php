<?php
/**
 * DEBUG: Captura el error real que causa el 500
 * Accedé a /platform/debug-500.php para ver el mensaje
 * ELIMINAR después de resolver
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<pre style='background:#1e1e1e;color:#d4d4d4;padding:1.5rem;font-family:monospace;'>";
echo "=== DEBUG 500 - Capturando errores ===\n\n";

try {
    echo "1. Cargando platform-config...\n";
    require_once __DIR__ . '/../platform-config.php';
    echo "   OK\n\n";

    echo "2. Iniciando sesión...\n";
    platformStartSession();
    echo "   OK\n\n";

    echo "3. PLATFORM_DB_NAME: " . (defined('PLATFORM_DB_NAME') ? PLATFORM_DB_NAME : 'NO') . "\n\n";

    echo "4. Probando conexión BD...\n";
    $pdo = getPlatformDB();
    echo "   " . ($pdo ? "Conectado OK" : "FALLO - getPlatformDB() retornó null") . "\n\n";

    if ($pdo) {
        echo "5. Probando query platformGetUserStores(1)...\n";
        $stores = platformGetUserStores(1);
        echo "   Tiendas: " . count($stores) . "\n\n";
    }

    echo "6. Simulando dashboard (platformRequireAuth + header)...\n";
    if (!platformIsAuthenticated()) {
        echo "   No autenticado - ir a login primero\n";
    } else {
        echo "   Usuario: " . ($_SESSION['platform_user_id'] ?? '?') . "\n";
        $userId = $_SESSION['platform_user_id'];
        $stores = platformGetUserStores($userId);
        echo "   Tiendas del usuario: " . count($stores) . "\n";
    }

    echo "\n7. Probando canUserCreateStore(1)...\n";
    $canCreate = canUserCreateStore(1);
    echo "   " . ($canCreate ? "Puede crear" : "No puede crear (límite)") . "\n\n";

    echo "8. Cargando header.php...\n";
    $pageTitle = 'Test';
    ob_start();
    require __DIR__ . '/_inc/header.php';
    $headerOutput = ob_get_clean();
    echo "   OK (" . strlen($headerOutput) . " bytes)\n\n";

    echo "9. Simulando cuerpo del dashboard (icon, statusLabels, foreach stores)...\n";
    $statusLabels = ['active' => ['Activa','active'], 'setup' => ['En configuración','setup'], 'suspended' => ['Suspendida','suspended']];
    foreach ($stores as $store) {
        $status = $statusLabels[$store['status']] ?? ['Desconocido', 'setup'];
        $x = icon('store', 32);
        $y = htmlspecialchars($store['slug']);
    }
    echo "   OK\n\n";

    echo "10. Cargando footer.php...\n";
    ob_start();
    require __DIR__ . '/_inc/footer.php';
    $footerOutput = ob_get_clean();
    echo "   OK (" . strlen($footerOutput) . " bytes)\n\n";

    echo "=== TODO OK - Si dashboard.php sigue dando 500, puede ser output buffering o orden de carga ===\n";
} catch (Throwable $e) {
    echo "\n*** ERROR CAPTURADO ***\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n</pre>";
