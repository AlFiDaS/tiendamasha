<?php
/**
 * Endpoint de diagnóstico de MercadoPago
 * Solo accesible para administradores autenticados
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

require_once '../../config.php';
require_once '../../helpers/auth.php';

startSecureSession();

if (!isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

try {
    $hasToken = defined('MERCADOPAGO_ACCESS_TOKEN') && MERCADOPAGO_ACCESS_TOKEN !== '';
    
    echo json_encode([
        'success' => true,
        'mercadopago_configured' => $hasToken,
        'test_mode' => MERCADOPAGO_TEST_MODE,
        'db_functions' => [
            'executeQuery' => function_exists('executeQuery'),
            'fetchOne' => function_exists('fetchOne'),
            'lastInsertId' => function_exists('lastInsertId')
        ],
        'order_functions' => [
            'saveOrder' => function_exists('saveOrder'),
            'updateOrder' => function_exists('updateOrder')
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
