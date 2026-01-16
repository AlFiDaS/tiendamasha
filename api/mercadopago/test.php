<?php
/**
 * Script de prueba simple para verificar que el archivo se ejecuta correctamente
 */

// Headers
header('Content-Type: application/json; charset=utf-8');

// Verificar que LUME_ADMIN esté definido
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Intentar cargar config
try {
    require_once '../../config.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'Config cargado correctamente',
        'mercadopago_token' => defined('MERCADOPAGO_ACCESS_TOKEN') ? (substr(MERCADOPAGO_ACCESS_TOKEN, 0, 20) . '...') : 'NO DEFINIDO',
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
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

