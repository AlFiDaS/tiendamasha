<?php
/**
 * Funciones del Super Admin de Somos Tiendi
 * Permite consultar datos de todas las tiendas desde el panel global
 */

if (!defined('TIENDI_PLATFORM')) {
    die('Acceso directo no permitido');
}

function isSuperAdmin() {
    if (!platformIsAuthenticated()) return false;

    $emails = array_map('trim', explode(',', PLATFORM_SUPERADMIN_EMAILS));
    $userEmail = $_SESSION['platform_user_email'] ?? '';

    return in_array(strtolower($userEmail), array_map('strtolower', $emails));
}

function requireSuperAdmin() {
    if (!isSuperAdmin()) {
        header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php');
        exit;
    }
}

/**
 * Conecta a la BD compartida (misma para todas las tiendas)
 */
function getStoreConnection($dbName = null) {
    $pdo = getPlatformDB();
    return $pdo;
}

/**
 * Obtiene estadísticas de una tienda por store_id.
 */
function getStoreStats($dbName, $storeId = null) {
    $pdo = getStoreConnection($dbName);
    if (!$pdo) {
        return [
            'products_count'  => '?',
            'orders_count'    => '?',
            'revenue'         => '?',
            'categories_count'=> '?',
            'db_error'        => true,
        ];
    }

    $sid = (int) $storeId;
    if ($sid < 1) {
        return [
            'products_count'  => '?',
            'orders_count'    => '?',
            'revenue'         => '?',
            'categories_count'=> '?',
            'db_error'        => true,
        ];
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE store_id = ?");
        $stmt->execute([$sid]);
        $products = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE store_id = ?");
        $stmt->execute([$sid]);
        $orders = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE store_id = ? AND status = 'approved'");
        $stmt->execute([$sid]);
        $revenue = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE store_id = ?");
        $stmt->execute([$sid]);
        $categories = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT shop_name FROM shop_settings WHERE store_id = ? LIMIT 1");
        $stmt->execute([$sid]);
        $shopName = $stmt->fetchColumn();

        return [
            'products_count'   => (int) $products,
            'orders_count'     => (int) $orders,
            'revenue'          => (float) $revenue,
            'categories_count' => (int) $categories,
            'shop_name'        => $shopName ?: null,
            'db_error'         => false,
        ];
    } catch (PDOException $e) {
        error_log('[SuperAdmin] Store stats error (store_id=' . $sid . '): ' . $e->getMessage());
        return [
            'products_count'  => '?',
            'orders_count'    => '?',
            'revenue'         => '?',
            'categories_count'=> '?',
            'db_error'        => true,
        ];
    }
}

/**
 * Devuelve todas las tiendas con datos del owner
 */
function getAllStoresWithOwner() {
    return platformFetchAll(
        'SELECT s.*, pu.name as owner_name, pu.email as owner_email
         FROM stores s
         LEFT JOIN platform_users pu ON pu.id = s.owner_id
         ORDER BY s.created_at DESC'
    );
}

/**
 * Devuelve stats globales de la plataforma
 */
function getPlatformGlobalStats() {
    $totalUsers = platformFetchOne('SELECT COUNT(*) as c FROM platform_users')['c'] ?? 0;
    $totalStores = platformFetchOne('SELECT COUNT(*) as c FROM stores')['c'] ?? 0;
    $activeStores = platformFetchOne("SELECT COUNT(*) as c FROM stores WHERE status = 'active'")['c'] ?? 0;
    $setupStores = platformFetchOne("SELECT COUNT(*) as c FROM stores WHERE status = 'setup'")['c'] ?? 0;

    $planCounts = platformFetchAll(
        "SELECT plan, COUNT(*) as c FROM stores GROUP BY plan"
    );
    $plans = [];
    foreach ($planCounts as $row) {
        $plans[$row['plan']] = (int) $row['c'];
    }

    $totalProducts = 0;
    $totalOrders = 0;
    $totalRevenue = 0;

    $stores = platformFetchAll('SELECT id, db_name FROM stores');
    foreach ($stores as $store) {
        $stats = getStoreStats($store['db_name'], $store['id']);
        if (!$stats['db_error']) {
            $totalProducts += $stats['products_count'];
            $totalOrders += $stats['orders_count'];
            $totalRevenue += $stats['revenue'];
        }
    }

    return [
        'total_users'     => (int) $totalUsers,
        'total_stores'    => (int) $totalStores,
        'active_stores'   => (int) $activeStores,
        'setup_stores'    => (int) $setupStores,
        'plans'           => $plans,
        'total_products'  => $totalProducts,
        'total_orders'    => $totalOrders,
        'total_revenue'   => $totalRevenue,
    ];
}

/**
 * Obtiene los usuarios admin de una tienda (para superadmin)
 */
function getStoreAdminUsers($storeId) {
    $pdo = getPlatformDB();
    if (!$pdo) return [];
    try {
        $stmt = $pdo->prepare('SELECT id, username, email FROM admin_users WHERE store_id = :sid ORDER BY id ASC');
        $stmt->execute(['sid' => (int) $storeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('[SuperAdmin] getStoreAdminUsers: ' . $e->getMessage());
        return [];
    }
}

/**
 * Crea un token de impersonación (válido 5 min, un solo uso)
 */
function createImpersonateToken($storeId, $adminId) {
    $token = bin2hex(random_bytes(24));
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/impersonate_' . $token;
    $data = json_encode([
        'store_id' => (int) $storeId,
        'admin_id' => (int) $adminId,
        'expires' => time() + 300,
    ]);
    return @file_put_contents($file, $data) ? $token : null;
}

/**
 * Consume un token de impersonación (devuelve datos o null)
 */
function consumeImpersonateToken($token) {
    $dir = dirname(__DIR__) . '/logs';
    $file = $dir . '/impersonate_' . preg_replace('/[^a-f0-9]/', '', $token);
    if (!file_exists($file)) return null;
    $data = json_decode(file_get_contents($file), true);
    @unlink($file);
    if (!$data || empty($data['store_id']) || empty($data['admin_id']) || (int) $data['expires'] < time()) {
        return null;
    }
    return $data;
}

/**
 * Obtiene datos detallados de una tienda para el panel superadmin
 */
function getStoreDetail($storeId) {
    $store = platformFetchOne(
        'SELECT s.*, pu.name as owner_name, pu.email as owner_email, pu.phone as owner_phone
         FROM stores s
         LEFT JOIN platform_users pu ON pu.id = s.owner_id
         WHERE s.id = :id',
        ['id' => $storeId]
    );

    if (!$store) return null;

    $store['stats'] = getStoreStats($store['db_name'], $store['id']);
    $store['members'] = platformFetchAll(
        'SELECT sm.role, pu.name, pu.email FROM store_members sm
         LEFT JOIN platform_users pu ON pu.id = sm.user_id
         WHERE sm.store_id = :sid',
        ['sid' => $storeId]
    );
    $store['admin_users'] = getStoreAdminUsers($storeId);

    return $store;
}

/**
 * Elimina una tienda: borra sus datos de las tablas compartidas y los registros de la plataforma.
 */
function deleteStore($storeId) {
    $store = platformFetchOne('SELECT * FROM stores WHERE id = :id', ['id' => $storeId]);
    if (!$store) {
        return ['success' => false, 'error' => 'Tienda no encontrada'];
    }

    $sid = (int) $storeId;
    $pdo = getStoreConnection($store['db_name']);

    if ($pdo && $sid > 0) {
        $storeTables = [
            'products', 'categories', 'admin_users', 'orders', 'galeria',
            'coupons', 'reviews', 'wishlist', 'customers', 'stock_movements',
            'shop_settings', 'gallery_info', 'landing_page_settings',
        ];
        foreach ($storeTables as $t) {
            try {
                $pdo->prepare("DELETE FROM `{$t}` WHERE store_id = ?")->execute([$sid]);
            } catch (PDOException $e) {
                error_log('[DeleteStore] Delete error (' . $t . '): ' . $e->getMessage());
            }
        }
    }

    $db = getPlatformDB();
    try {
        $db->beginTransaction();
        platformQuery('DELETE FROM store_members WHERE store_id = :sid', ['sid' => $storeId]);
        platformQuery('DELETE FROM stores WHERE id = :id', ['id' => $storeId]);
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('[DeleteStore] Error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Obtiene los últimos usuarios registrados
 */
function getRecentPlatformUsers($limit = 10) {
    return platformFetchAll(
        'SELECT pu.id, pu.name, pu.email, pu.phone, pu.email_verified, pu.created_at, pu.last_login,
                (SELECT COUNT(*) FROM store_members sm WHERE sm.user_id = pu.id) as stores_count
         FROM platform_users pu ORDER BY pu.created_at DESC LIMIT ' . (int)$limit
    );
}
