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
 * Crea tablas prefijadas para una nueva tienda dentro de la BD compartida.
 * Ej: prefix "test1_" -> test1_products, test1_categories, etc.
 *
 * @param string $prefix  Prefijo de tablas (ej: "test1_")
 * @return array ['success' => bool, 'error' => string|null]
 */
function createStoreTables(string $prefix): array {
    $safePrefix = preg_replace('/[^a-z0-9_]/', '', $prefix);
    if (empty($safePrefix)) {
        return ['success' => false, 'error' => 'Prefijo de tabla vacío o inválido'];
    }

    $storeTables = [
        'products', 'categories', 'admin_users', 'orders',
        'galeria', 'coupons', 'reviews', 'wishlist', 'customers',
        'stock_movements', 'shop_settings', 'gallery_info',
        'landing_page_settings',
    ];

    $mainSchema = PLATFORM_BASE_PATH . '/database.sql';
    if (!file_exists($mainSchema)) {
        return ['success' => false, 'error' => 'Archivo database.sql no encontrado en ' . PLATFORM_BASE_PATH];
    }

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', PLATFORM_DB_HOST, PLATFORM_DB_NAME),
            PLATFORM_DB_USER,
            PLATFORM_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $schemaFiles = [
            $mainSchema,
            PLATFORM_BASE_PATH . '/database-gallery-info.sql',
            PLATFORM_BASE_PATH . '/database-landing-page.sql',
        ];

        $tablesCreated = 0;
        $errors = [];

        foreach ($schemaFiles as $file) {
            if (!file_exists($file)) continue;

            $sql = file_get_contents($file);
            $sql = preg_replace('/^--.*$/m', '', $sql);
            $sql = preg_replace('/^CREATE DATABASE.*$/mi', '', $sql);
            $sql = preg_replace('/^USE\s+.*$/mi', '', $sql);

            foreach ($storeTables as $t) {
                $sql = preg_replace('/\b' . preg_quote($t, '/') . '\b/', $safePrefix . $t, $sql);
            }

            $sql = preg_replace('/,?\s*FOREIGN KEY[^\n;]*/i', '', $sql);

            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($s) => strlen($s) > 5
            );

            foreach ($statements as $statement) {
                try {
                    $pdo->exec($statement);
                    if (stripos($statement, 'CREATE TABLE') !== false) {
                        $tablesCreated++;
                    }
                } catch (PDOException $stmtErr) {
                    $errMsg = $stmtErr->getMessage();
                    $errors[] = $errMsg;
                    error_log('[CreateStoreTables] Statement error: ' . $errMsg . ' | SQL: ' . substr($statement, 0, 200));
                }
            }
        }

        if ($tablesCreated === 0) {
            $detail = !empty($errors) ? implode('; ', array_slice($errors, 0, 3)) : 'No se ejecutaron CREATE TABLE';
            return ['success' => false, 'error' => 'No se crearon tablas. ' . $detail];
        }

        $p = $safePrefix;
        $cleanupQueries = [
            "DELETE FROM `{$p}categories` WHERE slug NOT IN ('productos')",
            "UPDATE `{$p}categories` SET name = 'Productos', visible = 1, orden = 1 WHERE slug = 'productos'",
            "UPDATE `{$p}shop_settings` SET shop_name = 'Mi Tienda', shop_logo = NULL, whatsapp_number = NULL, whatsapp_message = NULL, address = NULL, instagram = NULL, facebook = NULL, email = NULL, phone = NULL, description = NULL, primary_color = '#6366f1' WHERE id = 1",
            "UPDATE `{$p}gallery_info` SET name = 'Galeria', slug = 'galeria', title = 'Galeria' WHERE id = 1",
            "UPDATE `{$p}landing_page_settings` SET carousel_images = NULL, sobre_title = 'Sobre nosotros', sobre_text_1 = NULL, sobre_text_2 = NULL, sobre_image = NULL, sobre_stat_1_number = '0', sobre_stat_1_label = 'Clientes', sobre_stat_2_number = '0', sobre_stat_2_label = 'Productos', sobre_stat_3_number = '1', sobre_stat_3_label = 'Año', testimonials = NULL, testimonials_visible = 0, galeria_title = 'Galeria', galeria_description = NULL, galeria_image = NULL, galeria_link = '/galeria', galeria_visible = 0 WHERE id = 1",
        ];
        foreach ($cleanupQueries as $q) {
            try { $pdo->exec($q); } catch (PDOException $e) {
                error_log('[CreateStoreTables] Cleanup: ' . $e->getMessage());
            }
        }

        return ['success' => true, 'tables_created' => $tablesCreated];
    } catch (PDOException $e) {
        error_log('[Platform DB] Create store tables error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
