<?php
/**
 * ============================================
 * API REST: Validar Cupón
 * ============================================
 * Endpoint para validar y calcular descuento de un cupón
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
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Asegurar que LUME_ADMIN está definido
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Cargar configuración
require_once '../../config.php';

// Temporalmente desactivar display_errors
ini_set('display_errors', 0);

// Cargar helpers
require_once '../../helpers/db.php';
require_once '../../helpers/coupons.php';

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $code = trim($input['code'] ?? '');
    $totalAmount = (float)($input['total_amount'] ?? 0);
    $items = $input['items'] ?? [];
    
    if (empty($code)) {
        echo json_encode([
            'success' => false,
            'error' => 'Código de cupón requerido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($totalAmount <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'El monto total debe ser mayor a 0'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validar cupón
    $result = validateCoupon($code, $totalAmount, $items);
    
    if ($result['valid']) {
        echo json_encode([
            'success' => true,
            'valid' => true,
            'discount' => $result['discount'],
            'discount_formatted' => '$' . number_format($result['discount'], 2, ',', '.'),
            'message' => $result['message'],
            'coupon' => [
                'code' => $result['coupon']['code'],
                'type' => $result['coupon']['type'],
                'value' => $result['coupon']['value']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'valid' => false,
            'discount' => 0,
            'message' => $result['message']
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}


