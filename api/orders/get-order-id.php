<?php
/**
 * ============================================
 * API: Obtener Order ID por Preference ID
 * ============================================
 * Obtiene el ID de una orden basado en preference_id
 * ============================================
 */

ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit();
}

// Asegurar que LUME_ADMIN está definido
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

require_once '../../config.php';
require_once '../../helpers/db.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    $preferenceId = $_GET['preference_id'] ?? null;

    if (empty($preferenceId)) {
        throw new Exception('Se requiere preference_id');
    }

    // Buscar orden por preference_id
    $order = fetchOne("SELECT id FROM orders WHERE preference_id = :pref_id LIMIT 1", ['pref_id' => $preferenceId]);

    if (!$order) {
        // Si no se encuentra, retornar null (no es un error crítico)
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode([
            'success' => true,
            'order_id' => null
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    if (ob_get_level()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => true,
        'order_id' => $order['id']
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(400);
    error_log('Error en get-order-id.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Error $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    error_log('Fatal error en get-order-id.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error fatal: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

