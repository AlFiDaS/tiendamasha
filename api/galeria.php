<?php
/**
 * ============================================
 * API REST: Galería
 * ============================================
 * Endpoint para obtener imágenes de la galería
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
require_once '../helpers/cache-bust.php';
require_once '../helpers/auth.php'; // Para función sanitize()

try {
    // Rate limiting para prevenir abuso
    require_once '../helpers/security.php';
    $rateLimit = checkRateLimit('api_galeria', 120, 60); // 120 requests por minuto
    
    if (!$rateLimit['allowed']) {
        http_response_code(429); // Too Many Requests
        header('Retry-After: ' . ($rateLimit['reset_at'] - time()));
        echo json_encode([
            'error' => 'Demasiadas solicitudes. Por favor, intenta más tarde.',
            'retry_after' => $rateLimit['reset_at'] - time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Construir consulta con filtros
    $sql = "SELECT id, nombre, imagen, alt, orden 
            FROM galeria 
            WHERE visible = 1";
    
    $params = [];
    
    // Ordenamiento
    $sql .= " ORDER BY orden ASC, id ASC";
    
    // Ejecutar consulta
    $items = fetchAll($sql, $params);
    
    if ($items === false) {
        throw new Exception('Error al consultar la base de datos');
    }
    
    // Formatear respuesta para compatibilidad con el frontend
    $galeria = [];
    foreach ($items as $item) {
        $galeria[] = [
            'src' => addCacheBust($item['imagen']),
            'alt' => $item['alt'] ?? $item['nombre']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'galeria' => $galeria
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar la galería: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

