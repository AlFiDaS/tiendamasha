<?php
/**
 * ============================================
 * HELPER: Conexión a Base de Datos
 * ============================================
 * Soporta store_id para multi-tenancy (una BD, datos por tienda)
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/** Tablas de tienda que requieren store_id */
$GLOBALS['_store_tables'] = [
    'products', 'categories', 'admin_users', 'orders',
    'galeria', 'coupons', 'reviews', 'wishlist', 'customers',
    'stock_movements', 'shop_settings', 'gallery_info',
    'landing_page_settings',
];

/**
 * Inyecta store_id en SQL y params para consultas de tablas de tienda.
 */
function injectStoreIdIntoQuery($sql, $params) {
    $storeId = defined('CURRENT_STORE_ID') ? (int) CURRENT_STORE_ID : 0;
    if ($storeId < 1) {
        return [$sql, $params];
    }
    if (preg_match('/^\s*(SHOW|DESCRIBE|EXPLAIN)\b/i', $sql)) {
        return [$sql, $params];
    }

    $tables = $GLOBALS['_store_tables'];
    $touchesStoreTable = false;
    foreach ($tables as $t) {
        if (preg_match('/\b' . preg_quote($t, '/') . '\b/i', $sql)) {
            $touchesStoreTable = true;
            break;
        }
    }
    if (!$touchesStoreTable) {
        return [$sql, $params];
    }

    $params = $params ?: [];
    $params['__store_id'] = $storeId;

    if (preg_match('/^\s*SELECT\b/i', $sql)) {
        if (preg_match('/\b(shop_settings|gallery_info|landing_page_settings)\b.*?WHERE\s+id\s*=\s*1\b/i', $sql)) {
            $sql = preg_replace('/WHERE\s+id\s*=\s*1\b/i', 'WHERE store_id = :__store_id', $sql);
        } elseif (preg_match('/\bWHERE\b/i', $sql)) {
            $sql = preg_replace('/(\bWHERE\b)(.+?)(\bORDER\s+BY\b|\bGROUP\s+BY\b|\bLIMIT\b|\bHAVING\b|$)/is', '$1 $2 AND store_id = :__store_id $3', $sql, 1);
        } else {
            $sql = preg_replace('/(\bFROM\s+[\w`]+(?:\s+[\w`]+)?)\s+(\b(?:ORDER|GROUP|LIMIT|HAVING)\b|$)/is', '$1 WHERE store_id = :__store_id $2', $sql, 1);
        }
    } elseif (preg_match('/^\s*INSERT\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $sql, $m)) {
        $table = strtolower($m[1]);
        if (in_array($table, $tables)) {
            $cols = $m[2];
            $vals = $m[3];
            if (in_array($table, ['shop_settings', 'gallery_info', 'landing_page_settings']) &&
                preg_match('/^\s*id\s*(?:,|$)/i', trim($cols)) && preg_match('/^\s*1\s*(?:,|$)/i', trim($vals))) {
                $cols = preg_replace('/^\s*id\s*,?\s*/i', '', $cols);
                $vals = preg_replace('/^\s*1\s*,?\s*/i', '', $vals);
                $cols = trim($cols) ? "store_id, $cols" : 'store_id';
                $vals = trim($vals) ? ":__store_id, $vals" : ':__store_id';
                $sql = "INSERT INTO $table ($cols) VALUES ($vals)";
            } else {
                $sql = preg_replace('/INSERT\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(/i', 'INSERT INTO $1 (store_id, $2) VALUES (:__store_id, ', $sql, 1);
            }
        }
    } elseif (preg_match('/^\s*UPDATE\b/i', $sql) && preg_match('/\bWHERE\b/i', $sql)) {
        if (preg_match('/\b(shop_settings|gallery_info|landing_page_settings)\b.*?WHERE\s+(?:id\s*=\s*1|id\s*=\s*:id)\b/i', $sql)) {
            $sql = preg_replace('/WHERE\s+(?:id\s*=\s*1|id\s*=\s*:id)\b/i', 'WHERE store_id = :__store_id', $sql);
            unset($params['id']);
        } else {
            $sql = preg_replace('/(\bWHERE\b)(.+?)(\bORDER\s+BY\b|\bLIMIT\b|$)/is', '$1 $2 AND store_id = :__store_id $3', $sql, 1);
        }
    } elseif (preg_match('/^\s*DELETE\b/i', $sql) && preg_match('/\bWHERE\b/i', $sql)) {
        if (preg_match('/\b(shop_settings|gallery_info|landing_page_settings)\b.*?WHERE\s+(?:id\s*=\s*1|id\s*=\s*:id)\b/i', $sql)) {
            $sql = preg_replace('/WHERE\s+(?:id\s*=\s*1|id\s*=\s*:id)\b/i', 'WHERE store_id = :__store_id', $sql);
            unset($params['id']);
        } else {
            $sql = preg_replace('/(\bWHERE\b)(.+?)(\bORDER\s+BY\b|\bLIMIT\b|$)/is', '$1 $2 AND store_id = :__store_id $3', $sql, 1);
        }
    }

    return [$sql, $params];
}

/**
 * Obtener conexión PDO a la base de datos (sin prefijos, BD única)
 */
function getDB() {
    static $pdo = null;
    static $currentDbName = null;

    if ($pdo !== null && $currentDbName !== DB_NAME) {
        $pdo = null;
    }

    if ($pdo === null) {
        $currentDbName = DB_NAME;
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Error de conexión a BD: ' . $e->getMessage());
            return null;
        }
    }

    return $pdo;
}

/**
 * Ejecutar consulta preparada (con inyección automática de store_id)
 */
function executeQuery($sql, $params = []) {
    $pdo = getDB();
    if (!$pdo) {
        return false;
    }

    list($sql, $params) = injectStoreIdIntoQuery($sql, $params);

    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log('Error en consulta SQL: ' . ($errorInfo[2] ?? 'Error desconocido'));
            error_log('SQL: ' . $sql);
            return false;
        }
        return $stmt;
    } catch (PDOException $e) {
        error_log('Error en consulta SQL: ' . $e->getMessage());
        error_log('SQL: ' . $sql);
        return false;
    }
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

function lastInsertId() {
    $pdo = getDB();
    return $pdo ? $pdo->lastInsertId() : false;
}

function beginTransaction() {
    $pdo = getDB();
    return $pdo ? $pdo->beginTransaction() : false;
}

function commit() {
    $pdo = getDB();
    return $pdo ? $pdo->commit() : false;
}

function rollback() {
    $pdo = getDB();
    return $pdo ? $pdo->rollBack() : false;
}
