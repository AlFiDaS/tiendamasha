<?php
/**
 * Cancelar creación de tienda - Elimina la tienda cuando el usuario no completa la configuración rápida
 */
$pageTitle = 'Cancelar creación';
require_once __DIR__ . '/../platform-config.php';
platformRequireAuth();

require_once dirname(__DIR__) . '/helpers/superadmin.php';

$userId = $_SESSION['platform_user_id'];
$storeSlug = trim($_GET['store'] ?? '');

if (empty($storeSlug)) {
    header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php');
    exit;
}

// Verificar que el usuario es miembro de la tienda
$store = platformFetchOne(
    'SELECT s.id, s.slug FROM stores s
     INNER JOIN store_members sm ON sm.store_id = s.id
     WHERE s.slug = :slug AND sm.user_id = :uid',
    ['slug' => $storeSlug, 'uid' => $userId]
);

if (!$store) {
    $_SESSION['platform_flash_error'] = 'No tenés acceso a esta tienda';
    header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php');
    exit;
}

// Verificar que la configuración rápida no está completada (solo cancelar tiendas recién creadas)
$settings = platformFetchOne(
    'SELECT configuracion_rapida_completada FROM shop_settings WHERE store_id = :sid',
    ['sid' => $store['id']]
);
if ($settings && !empty($settings['configuracion_rapida_completada'])) {
    $_SESSION['platform_flash_error'] = 'No se puede cancelar: la tienda ya está configurada.';
    header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php');
    exit;
}

$result = deleteStore($store['id']);

if ($result['success']) {
    $_SESSION['platform_flash_success'] = 'La tienda fue cancelada correctamente.';
} else {
    $_SESSION['platform_flash_error'] = $result['error'] ?? 'No se pudo cancelar la tienda.';
}

header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php');
exit;
