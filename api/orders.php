<?php
/**
 * ============================================
 * API REST: Búsqueda de Pedidos
 * ============================================
 * Endpoint para buscar pedidos por número o email
 * Compatible: PHP 7.4+
 * ============================================
 */

// Desactivar display de errores ANTES de cargar config
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Headers CORS y JSON
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Asegurar que LUME_ADMIN está definido
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Cargar configuración
require_once '../config.php';

// Temporalmente desactivar display_errors
ini_set('display_errors', 0);

// Cargar helpers
require_once '../helpers/db.php';

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $search = trim($_GET['search'] ?? '');
    
    if (empty($search)) {
        echo json_encode([
            'success' => true,
            'orders' => [],
            'message' => 'Ingresa un número de pedido o email para buscar'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Detectar si es una búsqueda por ID (número o #número)
    $searchTrimmed = trim($search);
    $isIdSearch = false;
    $orderId = null;
    
    // Si empieza con #, quitar el # y verificar si es número
    if (strpos($searchTrimmed, '#') === 0) {
        $searchTrimmed = substr($searchTrimmed, 1);
    }
    
    // Verificar si es un número (búsqueda por ID)
    if (is_numeric($searchTrimmed)) {
        $isIdSearch = true;
        $orderId = (int)$searchTrimmed;
    }
    
    // Construir consulta según el tipo de búsqueda
    // Nota: La tabla orders usa payer_email, payer_phone, total_amount (no email, phone, total)
    $sql = "SELECT id, payer_email as email, payer_phone as phone, items, total_amount as total, status, created_at 
            FROM orders 
            WHERE ";
    
    $params = [];
    
    if ($isIdSearch) {
        // Búsqueda exacta por ID
        $sql .= "id = :order_id";
        $params['order_id'] = $orderId;
    } else {
        // Búsqueda por texto (email o teléfono)
        $searchTerm = '%' . $searchTrimmed . '%';
        $sql .= "(payer_email LIKE :search1 OR payer_phone LIKE :search2)";
        $params['search1'] = $searchTerm;
        $params['search2'] = $searchTerm;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 20";
    
    $orders = fetchAll($sql, $params);
    
    if ($orders) {
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'count' => count($orders)
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'orders' => [],
            'message' => 'No se encontraron pedidos'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

