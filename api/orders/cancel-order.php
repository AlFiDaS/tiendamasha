<?php
/**
 * ============================================
 * API: Cancelar Orden
 * ============================================
 * Cancela una orden pendiente cuando el usuario no completa el pago
 * ============================================
 */

ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
require_once '../../helpers/orders.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    // Puede recibir preference_id o external_reference
    $preferenceId = $data['preference_id'] ?? null;
    $externalReference = $data['external_reference'] ?? null;

    if (empty($preferenceId) && empty($externalReference)) {
        throw new Exception('Se requiere preference_id o external_reference');
    }

    // Buscar la orden por preference_id o external_reference
    // MercadoPago puede pasar preference_id en la URL cuando redirige
    $order = null;
    if ($preferenceId) {
        // Buscar por preference_id (ID de MercadoPago)
        $order = fetchOne("SELECT * FROM orders WHERE preference_id = :pref_id AND status = 'pending'", ['pref_id' => $preferenceId]);
    }
    
    // Si no se encontró por preference_id y tenemos external_reference, buscar por ese
    if (!$order && $externalReference) {
        $order = fetchOne("SELECT * FROM orders WHERE external_reference = :ext_ref AND status = 'pending'", ['ext_ref' => $externalReference]);
    }

    if (!$order) {
        // Si no se encuentra, no es crítico, solo retornar éxito
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode([
            'success' => true,
            'message' => 'Orden no encontrada o ya procesada'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    // Actualizar orden a cancelled
    $updateResult = updateOrder($order['id'], [
        'status' => 'cancelled',
        'status_detail' => 'Cancelado por el usuario (no completó el pago)'
    ]);

    if (!$updateResult) {
        throw new Exception('Error al actualizar la orden');
    }

    if (ob_get_level()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Orden cancelada correctamente',
        'order_id' => $order['id']
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(400);
    error_log('Error en cancel-order.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Error $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    error_log('Fatal error en cancel-order.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error fatal: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

