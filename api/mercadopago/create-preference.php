<?php
/**
 * ============================================
 * API: Crear Preferencia de MercadoPago
 * ============================================
 * Crea una preferencia de pago para Checkout Pro
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

// Cargar helpers de órdenes (db.php ya está cargado por config.php)
require_once '../../helpers/orders.php';

try {
    // Solo aceptar POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Leer datos JSON del body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Datos inválidos');
    }

    // Validar datos requeridos
    $items = $data['items'] ?? [];
    $payer = $data['payer'] ?? [];
    
    if (empty($items)) {
        throw new Exception('No se encontraron items en el carrito');
    }

    // Obtener credenciales de MercadoPago desde config
    $accessToken = defined('MERCADOPAGO_ACCESS_TOKEN') ? MERCADOPAGO_ACCESS_TOKEN : '';
    
    if (empty($accessToken)) {
        throw new Exception('MercadoPago no está configurado. Por favor, configura MERCADOPAGO_ACCESS_TOKEN en config.php');
    }

    // Construir URL de retorno
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host;

    // Determinar método de pago
    $paymentMethod = $data['payment_method'] ?? 'mercadopago_transferencia';
    $isTarjeta = ($paymentMethod === 'tarjeta');
    $isMercadoPagoTransferencia = ($paymentMethod === 'mercadopago_transferencia');
    
    // Preparar items para MercadoPago
    $preferenceItems = [];
    foreach ($items as $item) {
        // Extraer precio numérico (remover $ y convertir a número)
        $price = floatval(str_replace(['$', ',', '.'], '', $item['price']));
        
        // Si es tarjeta, aplicar 25% adicional
        if ($isTarjeta) {
            $price = round($price * 1.25);
        }
        
        // En Argentina, MercadoPago espera el precio en pesos como número decimal
        $preferenceItems[] = [
            'title' => $item['name'],
            'quantity' => intval($item['cantidad']),
            'unit_price' => $price,
            'currency_id' => 'ARS'
        ];
    }

    // Generar referencia externa única
    $externalReference = uniqid('lume_', true);
    
    // Calcular total
    $totalAmount = 0;
    foreach ($preferenceItems as $item) {
        $totalAmount += $item['unit_price'] * $item['quantity'];
    }
    
    // Agregar costo de envío si aplica
    $envioCosto = 0;
    $shippingType = $data['shipping']['type'] ?? '';
    if (strpos($shippingType, 'Correo Argentino - Sucursal') !== false) {
        $envioCosto = 6500;
    } elseif (strpos($shippingType, 'Correo Argentino - Domicilio') !== false) {
        $envioCosto = 8500;
    }
    
    // Aplicar 25% al envío si es tarjeta
    if ($isTarjeta && $envioCosto > 0) {
        $envioCosto = round($envioCosto * 1.25);
    }
    
    // Agregar envío como item adicional si tiene costo
    if ($envioCosto > 0) {
        $preferenceItems[] = [
            'title' => 'Envío',
            'quantity' => 1,
            'unit_price' => $envioCosto,
            'currency_id' => 'ARS'
        ];
        $totalAmount += $envioCosto;
    }
    
    // Aplicar descuento de cupón si existe
    $couponCode = $data['coupon_code'] ?? null;
    $discountAmount = (float)($data['discount_amount'] ?? 0);
    
    if ($couponCode && $discountAmount > 0) {
        // Agregar descuento como item negativo (MercadoPago permite esto)
        $preferenceItems[] = [
            'title' => 'Descuento (Cupón: ' . $couponCode . ')',
            'quantity' => 1,
            'unit_price' => -$discountAmount, // Negativo para descuento
            'currency_id' => 'ARS'
        ];
        $totalAmount -= $discountAmount;
        
        // El total no puede ser negativo
        if ($totalAmount < 0) {
            $totalAmount = 0;
        }
    }
    
    // Preparar datos de envío (ya calculado arriba)
    $shippingType = $data['shipping']['type'] ?? '';
    $shippingAddress = '';
    if (!empty($data['shipping'])) {
        $addressParts = array_filter([
            $data['shipping']['address'] ?? '',
            $data['shipping']['locality'] ?? '',
            $data['shipping']['province'] ?? '',
            $data['shipping']['postal_code'] ?? ''
        ]);
        $shippingAddress = implode(', ', $addressParts);
    }
    
    // Guardar orden en BD antes de crear la preferencia (opcional, puede fallar si la tabla no existe aún)
    $orderId = null;
    if (function_exists('saveOrder')) {
        try {
            $orderData = [
                'preference_id' => null, // Se actualizará después
                'external_reference' => $externalReference,
                'status' => 'pending',
                'payer_name' => $payer['name'] ?? '',
                'payer_email' => $payer['email'] ?? '',
                'payer_phone' => $payer['phone'] ?? '',
                'items' => $items,
                'total_amount' => $totalAmount,
                'payment_method' => $data['payment_method'] ?? 'transferencia',
                'shipping_type' => $shippingType,
                'shipping_address' => $shippingAddress,
                'notes' => $data['notes'] ?? '',
                'metadata' => $data
            ];
            
            $orderId = saveOrder($orderData);
        } catch (Exception $e) {
            // Si falla guardar la orden, continuar de todas formas
            error_log('No se pudo guardar la orden en BD: ' . $e->getMessage());
            $orderId = null;
        }
    }
    
    // Preparar preferencia
    $preferenceData = [
        'items' => $preferenceItems,
        'payer' => [
            'name' => $payer['name'] ?? '',
            'email' => $payer['email'] ?? '',
            'phone' => [
                'number' => $payer['phone'] ?? ''
            ]
        ],
        'back_urls' => [
            'success' => $baseUrl . '/carrito/exito',
            'failure' => $baseUrl . '/carrito/fallo',
            'pending' => $baseUrl . '/carrito/pendiente'
        ],
        'auto_return' => 'approved',
        'notification_url' => $baseUrl . '/api/mercadopago/webhook.php',
        'statement_descriptor' => 'LUME VELAS',
        'external_reference' => $externalReference,
        'metadata' => [
            'order_id' => $orderId,
            'cart_data' => json_encode($data)
        ],
        'payment_methods' => [
            'installments' => $isTarjeta ? 3 : 1, // Máximo de cuotas (3 si es tarjeta, 1 si es MercadoPago transferencia)
            'excluded_payment_methods' => [],
            'excluded_payment_types' => $isTarjeta ? [
                ['id' => 'bank_transfer'] // Si es tarjeta, excluir transferencia bancaria
            ] : ($isMercadoPagoTransferencia ? [
                ['id' => 'credit_card'], // Si es MercadoPago transferencia, excluir tarjetas
                ['id' => 'debit_card']
            ] : []) // Permitir todo (no debería llegar aquí)
        ]
    ];
    
    // Si es tarjeta, configurar cuotas sin interés (hasta 3 cuotas)
    // Nota: Las cuotas sin interés deben estar habilitadas en el panel de MercadoPago
    // Esto solo configura el máximo de cuotas permitidas

    // Crear preferencia usando cURL (sin necesidad de SDK)
    $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ],
        CURLOPT_POSTFIELDS => json_encode($preferenceData)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('Error de conexión: ' . $error);
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['message'] ?? 'Error desconocido';
        throw new Exception('Error de MercadoPago (' . $httpCode . '): ' . $errorMessage);
    }

    $preference = json_decode($response, true);

    if (!isset($preference['init_point'])) {
        throw new Exception('Respuesta inválida de MercadoPago');
    }

    // Actualizar orden con preference_id si se guardó antes
    if ($orderId && isset($preference['id']) && function_exists('updateOrder')) {
        try {
            updateOrder($orderId, [
                'preference_id' => $preference['id']
            ]);
        } catch (Exception $e) {
            // Si falla actualizar, no es crítico
            error_log('No se pudo actualizar la orden: ' . $e->getMessage());
        }
    }

    // Limpiar output buffer antes de enviar JSON
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Retornar URL de checkout
    echo json_encode([
        'success' => true,
        'checkout_url' => $preference['init_point'],
        'preference_id' => $preference['id'] ?? null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // Limpiar output buffer antes de enviar JSON de error
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    error_log('Error en create-preference.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
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
    error_log('Fatal error en create-preference.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error fatal: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    // Limpiar output buffer antes de enviar JSON de error
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    error_log('Throwable error en create-preference.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

