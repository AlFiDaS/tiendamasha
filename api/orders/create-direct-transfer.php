<?php
/**
 * ============================================
 * API: Crear Orden con Transferencia Directa
 * ============================================
 * Crea una orden con comprobante de pago para transferencia directa
 * ============================================
 */

// Limpiar cualquier output anterior y comenzar output buffering
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Desactivar display de errores ANTES de cargar cualquier cosa
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Headers CORS y JSON (debe ser lo primero)
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Asegurar que LUME_ADMIN está definido ANTES de cargar config
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Cargar configuración (esto también carga db.php automáticamente)
require_once '../../config.php';

// Desactivar display_errors después de cargar config (config.php lo activa)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Cargar helpers
require_once '../../helpers/orders.php';
require_once '../../helpers/upload.php';

try {
    // Solo aceptar POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Validar que se subió un archivo
    if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Debes subir un comprobante de pago');
    }
    
    // Obtener datos del formulario (viene como FormData, no JSON)
    $formData = $_POST;
    
    // Si items viene como string JSON, decodificarlo
    if (isset($formData['items']) && is_string($formData['items'])) {
        $formData['items'] = json_decode($formData['items'], true);
    }
    
    // Si shipping viene como string JSON, decodificarlo
    if (isset($formData['shipping']) && is_string($formData['shipping'])) {
        $formData['shipping'] = json_decode($formData['shipping'], true);
    }
    
    // Validar datos requeridos
    $nombre = $formData['nombre'] ?? '';
    $telefono = $formData['telefono'] ?? '';
    $items = $formData['items'] ?? [];
    $totalAmount = floatval($formData['total_amount'] ?? 0);
    
    if (empty($nombre)) {
        throw new Exception('El nombre es requerido');
    }
    
    if (empty($telefono)) {
        throw new Exception('El teléfono es requerido');
    }
    
    if (empty($items) || !is_array($items)) {
        throw new Exception('No hay items en el carrito');
    }
    
    if ($totalAmount <= 0) {
        throw new Exception('El monto total debe ser mayor a 0');
    }
    
    // Generar referencia externa única
    $externalReference = uniqid('lume_direct_', true);
    
    // Obtener cupón si existe
    $couponCode = $formData['coupon_code'] ?? null;
    $discountAmount = isset($formData['discount_amount']) ? floatval($formData['discount_amount']) : 0;
    
    // Preparar datos de la orden (primero sin proof_image)
    $orderData = [
        'preference_id' => null,
        'external_reference' => $externalReference,
        'status' => 'a_confirmar', // Estado inicial para transferencias directas
        'status_detail' => 'Esperando confirmación de transferencia',
        'payer_name' => $nombre,
        'payer_email' => $formData['email'] ?? '',
        'payer_phone' => $telefono,
        'payer_document' => null,
        'items' => $items,
        'total_amount' => $totalAmount,
        'payment_method' => 'transferencia_directa',
        'payment_type' => null,
        'shipping_type' => (is_array($formData['shipping']) ? ($formData['shipping']['type'] ?? '') : ''),
        'shipping_address' => (is_array($formData['shipping']) ? ($formData['shipping']['address'] ?? '') : ''),
        'notes' => $formData['notes'] ?? '',
        'coupon_code' => $couponCode,
        'discount_amount' => $discountAmount,
        'metadata' => $formData
    ];
    
    // Guardar orden primero para obtener el ID
    $orderId = saveOrder($orderData);
    
    if (!$orderId) {
        throw new Exception('Error al guardar la orden');
    }
    
    // Subir comprobante de pago
    $uploadResult = uploadProofImage($_FILES['proof_image'], $orderId);
    
    if (!$uploadResult['success']) {
        // Si falla la subida, eliminar la orden creada
        executeQuery("DELETE FROM orders WHERE id = :id", ['id' => $orderId]);
        throw new Exception('Error al subir el comprobante: ' . $uploadResult['error']);
    }
    
    // Actualizar orden con la ruta del comprobante
    updateOrder($orderId, ['proof_image' => $uploadResult['path']]);
    
    // Limpiar output buffer antes de enviar JSON
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Retornar éxito
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Pedido creado correctamente. Está pendiente de confirmación.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    // Limpiar output buffer antes de enviar JSON de error
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(400);
    error_log('Error en create-direct-transfer.php: ' . $e->getMessage());
    error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Error $e) {
    // Limpiar output buffer antes de enviar JSON de error
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    error_log('Fatal error en create-direct-transfer.php: ' . $e->getMessage());
    error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error fatal: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

