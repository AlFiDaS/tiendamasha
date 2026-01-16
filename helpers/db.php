<?php
/**
 * ============================================
 * HELPER: Conexión a Base de Datos
 * ============================================
 * Manejo de conexión PDO a MySQL
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Obtener conexión PDO a la base de datos
 * @return PDO|null
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
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
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
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
 * Ejecutar consulta preparada
 * @param string $sql
 * @param array $params
 * @return PDOStatement|false
 */
function executeQuery($sql, $params = []) {
    $pdo = getDB();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        // Si hay errores después de ejecutar, loguearlos
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log('Error en consulta SQL: ' . ($errorInfo[2] ?? 'Error desconocido'));
            error_log('SQL: ' . $sql);
            error_log('Params: ' . print_r($params, true));
        }
        
        return $result ? $stmt : false;
    } catch (PDOException $e) {
        error_log('Error en consulta SQL: ' . $e->getMessage());
        error_log('SQL: ' . $sql);
        error_log('Params: ' . print_r($params, true));
        return false;
    }
}

/**
 * Obtener un solo registro
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if (!$stmt) {
        return false;
    }
    return $stmt->fetch();
}

/**
 * Obtener múltiples registros
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if (!$stmt) {
        return false;
    }
    return $stmt->fetchAll();
}

/**
 * Obtener el último ID insertado
 * @return string|false
 */
function lastInsertId() {
    $pdo = getDB();
    if (!$pdo) {
        return false;
    }
    return $pdo->lastInsertId();
}

/**
 * Iniciar transacción
 * @return bool
 */
function beginTransaction() {
    $pdo = getDB();
    if (!$pdo) {
        return false;
    }
    return $pdo->beginTransaction();
}

/**
 * Confirmar transacción
 * @return bool
 */
function commit() {
    $pdo = getDB();
    if (!$pdo) {
        return false;
    }
    return $pdo->commit();
}

/**
 * Revertir transacción
 * @return bool
 */
function rollback() {
    $pdo = getDB();
    if (!$pdo) {
        return false;
    }
    return $pdo->rollBack();
}

