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
 * Conecta a la BD de una tienda específica y devuelve un PDO
 */
function getStoreConnection($dbName) {
    $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
    try {
        return new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', STORE_DB_HOST, $safeName),
            STORE_DB_USER,
            STORE_DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        error_log('[SuperAdmin] Store DB connection error (' . $safeName . '): ' . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene estadísticas de una tienda conectándose a su BD.
 * Soporta tablas con prefijo (table_prefix).
 */
function getStoreStats($dbName, $tablePrefix = '') {
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

    $p = preg_replace('/[^a-z0-9_]/', '', $tablePrefix ?? '');

    try {
        $products = $pdo->query("SELECT COUNT(*) FROM `{$p}products`")->fetchColumn();
        $orders = $pdo->query("SELECT COUNT(*) FROM `{$p}orders`")->fetchColumn();
        $revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM `{$p}orders` WHERE status = 'approved'")->fetchColumn();
        $categories = $pdo->query("SELECT COUNT(*) FROM `{$p}categories`")->fetchColumn();
        $shopName = $pdo->query("SELECT shop_name FROM `{$p}shop_settings` WHERE id = 1 LIMIT 1")->fetchColumn();

        return [
            'products_count'   => (int) $products,
            'orders_count'     => (int) $orders,
            'revenue'          => (float) $revenue,
            'categories_count' => (int) $categories,
            'shop_name'        => $shopName ?: null,
            'db_error'         => false,
        ];
    } catch (PDOException $e) {
        error_log('[SuperAdmin] Store stats error (' . $dbName . '/' . $p . '): ' . $e->getMessage());
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

    $stores = platformFetchAll('SELECT db_name, table_prefix FROM stores');
    foreach ($stores as $store) {
        $stats = getStoreStats($store['db_name'], $store['table_prefix'] ?? '');
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

    $store['stats'] = getStoreStats($store['db_name'], $store['table_prefix'] ?? '');
    $store['members'] = platformFetchAll(
        'SELECT sm.role, pu.name, pu.email FROM store_members sm
         LEFT JOIN platform_users pu ON pu.id = sm.user_id
         WHERE sm.store_id = :sid',
        ['sid' => $storeId]
    );

    return $store;
}

/**
 * Elimina una tienda: borra sus tablas prefijadas y los registros de la plataforma.
 */
function deleteStore($storeId) {
    $store = platformFetchOne('SELECT * FROM stores WHERE id = :id', ['id' => $storeId]);
    if (!$store) {
        return ['success' => false, 'error' => 'Tienda no encontrada'];
    }

    $prefix = $store['table_prefix'] ?? '';
    $pdo = getStoreConnection($store['db_name']);

    if ($pdo && $prefix) {
        $storeTables = [
            'products', 'categories', 'admin_users', 'orders', 'galeria',
            'coupons', 'reviews', 'wishlist', 'customers', 'stock_movements',
            'shop_settings', 'gallery_info', 'landing_page_settings',
        ];
        foreach ($storeTables as $t) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS `{$prefix}{$t}`");
            } catch (PDOException $e) {
                error_log('[DeleteStore] Drop table error: ' . $e->getMessage());
            }
        }
    } elseif ($pdo && !$prefix) {
        error_log('[DeleteStore] Tienda sin prefijo (slug=' . $store['slug'] . '), tablas no borradas por seguridad.');
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
        'SELECT id, name, email, created_at, last_login FROM platform_users ORDER BY created_at DESC LIMIT ' . (int)$limit
    );
}
