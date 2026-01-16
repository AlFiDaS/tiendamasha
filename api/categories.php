<?php
/**
 * ============================================
 * API REST: Categorías
 * ============================================
 * Endpoint para obtener categorías visibles
 * Compatible: PHP 7.4+
 * ============================================
 */

// Desactivar display de errores ANTES de cargar config
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Headers CORS y JSON (deben ir antes de cualquier output)
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
    // Headers para evitar caché en desarrollo
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Asegurar que LUME_ADMIN está definido antes de cargar helpers
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Cargar configuración
require_once '../config.php';

// Temporalmente desactivar display_errors (config.php lo puede activar)
ini_set('display_errors', 0);

// Cargar helpers
require_once '../helpers/categories.php';

try {
    // Rate limiting para prevenir abuso
    require_once '../helpers/security.php';
    $rateLimit = checkRateLimit('api_categories', 120, 60); // 120 requests por minuto
    
    if (!$rateLimit['allowed']) {
        http_response_code(429); // Too Many Requests
        header('Retry-After: ' . ($rateLimit['reset_at'] - time()));
        echo json_encode([
            'error' => 'Demasiadas solicitudes. Por favor, intenta más tarde.',
            'retry_after' => $rateLimit['reset_at'] - time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Obtener solo categorías visibles (para el navbar público)
    $onlyVisible = isset($_GET['visible']) && $_GET['visible'] === 'false' ? false : true;
    $categorias = getAllCategories($onlyVisible);
    
    if ($categorias === false) {
        throw new Exception('Error al consultar la base de datos');
    }
    
    // Formatear respuesta
    $response = [
        'success' => true,
        'categories' => $categorias
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

