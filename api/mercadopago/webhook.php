<?php
/**
 * ============================================
 * API: Webhook de MercadoPago
 * ============================================
 * Recibe notificaciones de MercadoPago sobre el estado de los pagos
 * ============================================
 */

// Asegurar que LUME_ADMIN está definido
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Cargar configuración
require_once '../../config.php';

// Cargar helpers
require_once '../../helpers/db.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/orders.php';

// Log para debugging (opcional)
function logWebhook($message) {
    $logFile = BASE_PATH . '/logs/mercadopago-webhook.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Función para obtener todos los datos de la solicitud (para debugging)
function logRequestData() {
    $data = [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        'get' => $_GET,
        'post' => $_POST,
        'body' => file_get_contents('php://input'),
        'headers' => getallheaders()
    ];
    logWebhook("Request completa: " . json_encode($data, JSON_PRETTY_PRINT));
}

try {
    // Log de la solicitud completa para debugging
    logRequestData();
    
    // MercadoPago puede enviar notificaciones de diferentes formas:
    // 1. Como parámetros GET: ?type=payment&data.id=123456
    // 2. Como datos POST en el body (JSON)
    // 3. Como headers específicos
    
    $type = '';
    $dataId = '';
    
    // Intentar obtener desde GET (método tradicional de MercadoPago IPN)
    if (isset($_GET['type']) && isset($_GET['data.id'])) {
        $type = $_GET['type'];
        $dataId = $_GET['data.id'];
    }
    // Intentar obtener desde POST (método moderno)
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $postData = json_decode($input, true);
        
        if ($postData) {
            $type = $postData['type'] ?? $postData['action'] ?? '';
            $dataId = $postData['data']['id'] ?? $postData['data_id'] ?? $postData['id'] ?? '';
        } else {
            // Intentar desde $_POST
            $type = $_POST['type'] ?? '';
            $dataId = $_POST['data.id'] ?? $_POST['data_id'] ?? '';
        }
    }

    logWebhook("Webhook recibido - Type: $type, Data ID: $dataId, Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));

    // Si no hay parámetros, puede ser una prueba manual o acceso directo
    if (empty($type) || empty($dataId)) {
        // Si es un GET sin parámetros, responder con información útil
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'webhook_activo',
                'message' => 'El webhook está funcionando. MercadoPago enviará notificaciones aquí cuando haya cambios en los pagos.',
                'endpoint' => 'api/mercadopago/webhook.php',
                'nota' => 'Esta es una respuesta de prueba. Las notificaciones reales de MercadoPago incluirán parámetros type y data.id'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
        
        // Si es POST pero sin datos válidos, registrar y responder
        logWebhook("Advertencia: Solicitud sin parámetros válidos");
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'Parámetros inválidos',
            'message' => 'Se esperan parámetros type y data.id',
            'received' => [
                'get' => $_GET,
                'post' => $_POST,
                'body' => file_get_contents('php://input')
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // Obtener credenciales
    $accessToken = defined('MERCADOPAGO_ACCESS_TOKEN') ? MERCADOPAGO_ACCESS_TOKEN : '';
    
    if (empty($accessToken)) {
        throw new Exception('MercadoPago no está configurado');
    }

    // Si es un pago, obtener información del pago
    if ($type === 'payment') {
        $ch = curl_init("https://api.mercadopago.com/v1/payments/$dataId");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Error de cURL: ' . $curlError);
        }

        if ($httpCode === 200) {
            $payment = json_decode($response, true);
            
            if (!$payment) {
                throw new Exception('Respuesta inválida de MercadoPago API');
            }
            
            logWebhook("Pago procesado - ID: " . ($payment['id'] ?? 'N/A') . ", Status: " . ($payment['status'] ?? 'N/A'));
            
            // Buscar orden por external_reference
            $externalRef = $payment['external_reference'] ?? null;
            
            // Si tenemos external_reference, buscar la orden
            if ($externalRef) {
                $order = fetchOne("SELECT * FROM orders WHERE external_reference = :ref", ['ref' => $externalRef]);
                
                if ($order) {
                    // Actualizar orden con información del pago
                    $updateData = [
                        'mercadopago_id' => $payment['id'] ?? null,
                        'status' => $payment['status'] ?? 'pending',
                        'status_detail' => $payment['status_detail'] ?? null,
                        'payment_method' => $payment['payment_method_id'] ?? null,
                        'payment_type' => $payment['payment_type_id'] ?? null,
                        'total_amount' => $payment['transaction_amount'] ?? $order['total_amount']
                    ];
                    
                    // Datos del pagador si están disponibles
                    if (isset($payment['payer'])) {
                        $updateData['payer_email'] = $payment['payer']['email'] ?? $order['payer_email'];
                        $updateData['payer_phone'] = $payment['payer']['phone']['number'] ?? $order['payer_phone'];
                        if (isset($payment['payer']['identification'])) {
                            $updateData['payer_document'] = $payment['payer']['identification']['number'] ?? $order['payer_document'];
                        }
                    }
                    
                    // Verificar si el estado cambió a 'approved' (pago aprobado)
                    $wasApproved = ($updateData['status'] ?? '') === 'approved' && ($order['status'] ?? '') !== 'approved';
                    
                    updateOrder($order['id'], $updateData);
                    logWebhook("Orden #" . $order['id'] . " actualizada con status: " . $updateData['status']);
                    
                    // Enviar notificación a Telegram si el pago fue aprobado
                    if ($wasApproved) {
                        try {
                            require_once '../../helpers/telegram.php';
                            if (function_exists('sendTelegramNotification') && function_exists('formatOrderNotification')) {
                                $orderData = array_merge($order, $updateData);
                                $message = formatOrderNotification($orderData, $order['id']);
                                sendTelegramNotification($message);
                                logWebhook("Notificación de Telegram enviada para orden #" . $order['id']);
                            }
                        } catch (Exception $e) {
                            logWebhook("Error al enviar notificación de Telegram: " . $e->getMessage());
                        }
                    }
                } else {
                    logWebhook("Orden no encontrada para external_reference: " . $externalRef);
                }
            } else {
                logWebhook("Pago sin external_reference: " . ($payment['id'] ?? 'N/A'));
            }
        } else {
            logWebhook("Error al obtener pago de MercadoPago API - HTTP Code: $httpCode, Response: $response");
            throw new Exception("Error al obtener información del pago (HTTP $httpCode)");
        }
    } else {
        logWebhook("Tipo de notificación no manejado: $type");
    }

    // Responder a MercadoPago
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    logWebhook("Error: " . $e->getMessage());
    logWebhook("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'error'
    ], JSON_UNESCAPED_UNICODE);
}

