<?php
/**
 * Super Admin - Acceso directo al admin de una tienda (sin login)
 * Genera un token y redirige al admin para auto-login
 */
require_once __DIR__ . '/../../platform-config.php';

requireSuperAdmin();

$storeId = (int) ($_GET['store_id'] ?? 0);
if (!$storeId) {
    header('Location: ' . PLATFORM_PAGES_URL . '/superadmin/stores.php');
    exit;
}

$store = platformFetchOne('SELECT id, slug FROM stores WHERE id = :id', ['id' => $storeId]);
if (!$store) {
    header('Location: ' . PLATFORM_PAGES_URL . '/superadmin/stores.php');
    exit;
}

$admins = getStoreAdminUsers($storeId);
if (empty($admins)) {
    header('Location: ' . PLATFORM_PAGES_URL . '/superadmin/store-detail.php?id=' . $storeId . '&error=no_admin');
    exit;
}

$admin = $admins[0];
$token = createImpersonateToken($storeId, $admin['id']);
if (!$token) {
    header('Location: ' . PLATFORM_PAGES_URL . '/superadmin/store-detail.php?id=' . $storeId . '&error=token_failed');
    exit;
}

$adminUrl = '/' . $store['slug'] . '/admin/impersonate.php?token=' . $token;
header('Location: ' . $adminUrl);
exit;
