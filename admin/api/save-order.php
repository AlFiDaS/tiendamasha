<?php
/**
 * API para guardar el orden de los productos
 */
define('LUME_ADMIN', true);
require_once '../../config.php';
require_once '../../helpers/db.php';
require_once '../../helpers/auth.php';

// Requerir autenticación
requireAuth();

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);
$order = $input['order'] ?? null;

// Validar
if (!is_array($order) || empty($order)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Verificar si la columna 'orden' existe, si no, crearla
try {
    $checkColumn = fetchOne("SHOW COLUMNS FROM products LIKE 'orden'");
    if (!$checkColumn) {
        // Agregar columna orden
        $alterSql = "ALTER TABLE products ADD COLUMN orden INT DEFAULT NULL AFTER visible";
        executeQuery($alterSql, []);
    }
} catch (Exception $e) {
    // Ignorar si ya existe
}

// Iniciar transacción
beginTransaction();

try {
    // Actualizar el orden de cada producto
    foreach ($order as $item) {
        if (empty($item['id']) || !isset($item['orden'])) {
            continue;
        }
        
        $sql = "UPDATE products SET orden = :orden WHERE id = :id";
        $params = [
            'orden' => (int)$item['orden'],
            'id' => $item['id']
        ];
        
        if (!executeQuery($sql, $params)) {
            throw new Exception('Error al actualizar el orden del producto: ' . $item['id']);
        }
    }
    
    // Confirmar transacción
    commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Orden guardado correctamente',
        'updated' => count($order)
    ]);
    
} catch (Exception $e) {
    // Revertir transacción
    rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

