<?php
/**
 * Conexión a la base de datos de la plataforma Somos Tiendi
 * Tablas: platform_users, stores, store_members
 */

if (!defined('TIENDI_PLATFORM')) {
    die('Acceso directo no permitido');
}

function getPlatformDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                PLATFORM_DB_HOST,
                PLATFORM_DB_NAME,
                PLATFORM_DB_CHARSET
            );
            $pdo = new PDO($dsn, PLATFORM_DB_USER, PLATFORM_DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . PLATFORM_DB_CHARSET
            ]);
        } catch (PDOException $e) {
            error_log('[Platform DB] Connection error: ' . $e->getMessage());
            return null;
        }
    }

    return $pdo;
}

function platformQuery($sql, $params = []) {
    $pdo = getPlatformDB();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        return $result ? $stmt : false;
    } catch (PDOException $e) {
        error_log('[Platform DB] Query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
        return false;
    }
}

function platformFetchOne($sql, $params = []) {
    $stmt = platformQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

function platformFetchAll($sql, $params = []) {
    $stmt = platformQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function platformLastInsertId() {
    $pdo = getPlatformDB();
    return $pdo ? $pdo->lastInsertId() : false;
}

/**
 * Crea los datos iniciales para una nueva tienda (store_id).
 * No crea tablas; asume que ya existen con store_id.
 */
function createStoreData($storeId, $storeName, $adminUsername, $adminPassword, $adminEmail, $whatsapp = '', $instagram = '', $description = '') {
    $pdo = getPlatformDB();
    if (!$pdo) {
        return ['success' => false, 'error' => 'No se pudo conectar a la base de datos'];
    }

    $storeId = (int) $storeId;
    if ($storeId < 1) {
        return ['success' => false, 'error' => 'store_id inválido'];
    }

    try {
        $hashedPass = password_hash($adminPassword, PASSWORD_DEFAULT);

        $pdo->prepare('INSERT INTO admin_users (store_id, username, password, email) VALUES (:sid, :u, :p, :e)')
            ->execute(['sid' => $storeId, 'u' => $adminUsername, 'p' => $hashedPass, 'e' => $adminEmail]);

        $pdo->prepare('INSERT INTO shop_settings (store_id, shop_name, whatsapp_number, instagram, description, primary_color) VALUES (:sid, :name, :wa, :ig, :desc, :color)')
            ->execute(['sid' => $storeId, 'name' => $storeName, 'wa' => $whatsapp, 'ig' => $instagram, 'desc' => $description, 'color' => '#6366f1']);

        $pdo->prepare('INSERT INTO categories (store_id, slug, name, catalog_title, visible, orden) VALUES (:sid, :slug, :name, :cat_title, 1, 1)')
            ->execute(['sid' => $storeId, 'slug' => 'productos', 'name' => 'Productos', 'cat_title' => 'Catálogo de productos']);

        $pdo->prepare('INSERT INTO gallery_info (store_id, name, slug, title) VALUES (:sid, :name, :slug, :title)')
            ->execute(['sid' => $storeId, 'name' => 'Galeria', 'slug' => 'galeria', 'title' => 'Galeria']);

        $sobreTitle = 'Sobre ' . $storeName;
        $pdo->prepare('INSERT INTO landing_page_settings (store_id, sobre_title) VALUES (:sid, :sobre)')
            ->execute(['sid' => $storeId, 'sobre' => $sobreTitle]);

        return ['success' => true];
    } catch (PDOException $e) {
        error_log('[Platform DB] createStoreData error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
