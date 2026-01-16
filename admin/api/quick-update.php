<?php
/**
 * API para actualizaciones rápidas de productos desde list.php
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
$productId = $input['id'] ?? null;
$field = $input['field'] ?? null;
$value = $input['value'] ?? null;

// Validar
if (!$productId || !$field) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Campos permitidos
$allowedFields = ['price', 'visible', 'stock', 'destacado'];
if (!in_array($field, $allowedFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Campo no permitido']);
    exit;
}

// Validar y preparar valor según el campo
$validatedValue = null;
switch ($field) {
    case 'price':
        // Permitir formato de precio con $ y números
        $validatedValue = trim($value);
        break;
    case 'visible':
    case 'destacado':
        $validatedValue = (int)(bool)$value;
        break;
    case 'stock':
        // Para stock: si está marcado (true) = NULL (ilimitado), si está desmarcado (false) = 0 (sin stock)
        $validatedValue = $value ? null : 0;
        break;
}

// Verificar que el producto existe
$product = fetchOne("SELECT id FROM products WHERE id = :id", ['id' => $productId]);
if (!$product) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
    exit;
}

// Actualizar
// Para stock NULL, necesitamos usar IS NULL en lugar de = NULL
if ($field === 'stock' && $validatedValue === null) {
    $sql = "UPDATE products SET `{$field}` = NULL WHERE id = :id";
    $params = ['id' => $productId];
} else {
    $sql = "UPDATE products SET `{$field}` = :value WHERE id = :id";
    $params = [
        'value' => $validatedValue,
        'id' => $productId
    ];
}

if (executeQuery($sql, $params)) {
    echo json_encode([
        'success' => true,
        'message' => 'Actualizado correctamente',
        'value' => $validatedValue
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al actualizar']);
}

