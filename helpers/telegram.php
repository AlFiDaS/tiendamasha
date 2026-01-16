<?php
/**
 * ============================================
 * HELPER: Notificaciones de Telegram
 * ============================================
 * Envía notificaciones a Telegram cuando hay nuevas órdenes
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Enviar mensaje a Telegram
 * @param string $message Mensaje a enviar
 * @return bool True si se envió correctamente, false en caso contrario
 */
function sendTelegramNotification($message) {
    // Obtener configuración
    $botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
    $chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';
    
    // Si no está configurado, no hacer nada
    if (empty($botToken) || empty($chatId)) {
        error_log('Telegram no está configurado. Bot Token o Chat ID faltante.');
        return false;
    }
    
    // Asegurar que chat_id sea numérico (Telegram puede requerirlo)
    $chatId = is_numeric($chatId) ? (int)$chatId : $chatId;
    
    // URL de la API de Telegram
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    // Preparar datos - usar JSON en lugar de form-data
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML' // Permite formato HTML básico
    ];
    
    // Enviar usando cURL con JSON
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 10, // Aumentar timeout a 10 segundos
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('Error cURL al enviar notificación de Telegram: ' . $error);
        return false;
    }
    
    if ($httpCode !== 200) {
        $responseData = json_decode($response, true);
        $errorMsg = $responseData['description'] ?? $response;
        $errorCode = $responseData['error_code'] ?? '';
        error_log('Error HTTP al enviar notificación de Telegram: ' . $httpCode . ' (Código: ' . $errorCode . ') - ' . $errorMsg);
        
        // Si es error 403, el bot probablemente no ha sido iniciado
        if ($httpCode === 403 || $errorCode === 403) {
            error_log('IMPORTANTE: El bot no puede enviar mensajes. El usuario debe iniciar el bot primero enviando /start a @LumectesBot');
        }
        
        return false;
    }
    
    $responseData = json_decode($response, true);
    if (isset($responseData['ok']) && $responseData['ok'] === true) {
        return true;
    }
    
    error_log('Respuesta inesperada de Telegram: ' . $response);
    return false;
}

/**
 * Formatear mensaje de nueva orden para Telegram
 * @param array $orderData Datos de la orden
 * @param int $orderId ID de la orden
 * @return string Mensaje formateado
 */
function formatOrderNotification($orderData, $orderId) {
    $items = is_array($orderData['items']) ? $orderData['items'] : json_decode($orderData['items'] ?? '[]', true);
    $itemsList = '';
    
    if ($items && is_array($items)) {
        foreach ($items as $item) {
            $itemsList .= "• {$item['name']} x{$item['cantidad']} - {$item['price']}\n";
        }
    }
    
    $paymentMethod = $orderData['payment_method'] ?? 'No especificado';
    $paymentMethodText = '';
    switch ($paymentMethod) {
        case 'tarjeta':
            $paymentMethodText = '💳 Tarjeta de crédito';
            break;
        case 'mercadopago_transferencia':
            $paymentMethodText = '🏦 Transferencia (MercadoPago)';
            break;
        case 'transferencia_directa':
            $paymentMethodText = '🏦 Transferencia directa';
            break;
        default:
            $paymentMethodText = $paymentMethod;
    }
    
    $status = $orderData['status'] ?? 'pending';
    $statusEmoji = '';
    switch ($status) {
        case 'approved':
            $statusEmoji = '✅';
            break;
        case 'pending':
            $statusEmoji = '⏳';
            break;
        case 'rejected':
        case 'cancelled':
            $statusEmoji = '❌';
            break;
        default:
            $statusEmoji = '📋';
    }
    
    $total = number_format($orderData['total_amount'] ?? 0, 0, ',', '.');
    
    $message = "🛒 <b>NUEVA ORDEN #{$orderId}</b>\n\n";
    $message .= "{$statusEmoji} <b>Estado:</b> " . strtoupper($status) . "\n";
    $message .= "👤 <b>Cliente:</b> " . ($orderData['payer_name'] ?? 'No especificado') . "\n";
    $message .= "📧 <b>Email:</b> " . ($orderData['payer_email'] ?? 'No especificado') . "\n";
    $message .= "📱 <b>Teléfono:</b> " . ($orderData['payer_phone'] ?? 'No especificado') . "\n";
    $message .= "💳 <b>Método de pago:</b> {$paymentMethodText}\n";
    $message .= "📦 <b>Envío:</b> " . ($orderData['shipping_type'] ?? 'No especificado') . "\n";
    $message .= "\n<b>Productos:</b>\n{$itemsList}\n";
    $message .= "💰 <b>Total: \${$total}</b>\n";
    
    if (!empty($orderData['shipping_address'])) {
        $message .= "\n📍 <b>Dirección:</b> " . $orderData['shipping_address'] . "\n";
    }
    
    if (!empty($orderData['notes'])) {
        $message .= "\n📝 <b>Notas:</b> " . $orderData['notes'] . "\n";
    }
    
    $adminUrl = defined('ADMIN_URL') ? ADMIN_URL : '';
    if ($adminUrl) {
        $message .= "\n🔗 Ver en admin: {$adminUrl}/ordenes/detail.php?id={$orderId}";
    }
    
    return $message;
}

