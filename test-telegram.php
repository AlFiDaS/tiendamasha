<?php
/**
 * ============================================
 * TEST: Notificaciones de Telegram
 * ============================================
 * Archivo de prueba para verificar que las notificaciones de Telegram funcionan
 * ============================================
 */

// Asegurar que LUME_ADMIN está definido
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Cargar configuración
require_once __DIR__ . '/config.php';

// Cargar helper de Telegram
require_once __DIR__ . '/helpers/telegram.php';

// Mensaje de prueba simple primero
$messageSimple = "🧪 Prueba de Notificación\n\n¡Hola Gisela! Si recibes este mensaje, la configuración está correcta. 🎉";

// Mensaje de prueba con HTML
$message = "🧪 <b>Prueba de Notificación</b>\n\n";
$message .= "¡Hola Gisela! 👋\n\n";
$message .= "Si recibes este mensaje, significa que:\n";
$message .= "✅ El bot está configurado correctamente\n";
$message .= "✅ Las notificaciones funcionan\n";
$message .= "✅ Recibirás alertas cuando haya nuevas compras\n\n";
$message .= "🎉 ¡Todo está listo!";

// Enviar notificación
echo "<h1>🧪 Test de Telegram</h1>";
echo "<p>Enviando mensaje de prueba simple primero...</p>";

// Probar primero con mensaje simple sin HTML
$resultSimple = sendTelegramNotification($messageSimple);

if ($resultSimple) {
    echo "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>";
    echo "✅ <strong>¡Éxito con mensaje simple!</strong> Ahora probando con formato HTML...<br>";
    echo "</div>";
    
    // Si el simple funciona, probar con HTML
    echo "<p>Enviando mensaje con formato HTML...</p>";
    $result = sendTelegramNotification($message);
} else {
    // Si el simple falla, no probar el HTML
    $result = false;
    echo "<p>El mensaje simple falló, no se probará el formato HTML.</p>";
}

if ($result) {
    echo "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>";
    echo "✅ <strong>¡Éxito!</strong> El mensaje se envió correctamente.<br>";
    echo "Revisa tu Telegram para ver el mensaje.";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>";
    echo "❌ <strong>Error:</strong> No se pudo enviar el mensaje.<br>";
    echo "Revisa la configuración en config.php y los logs del servidor.<br><br>";
    
    // Mostrar información de debug
    $botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
    $chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';
    
    echo "<strong>Debug:</strong><br>";
    echo "Bot Token: " . (empty($botToken) ? '❌ Vacío' : '✅ Configurado (' . substr($botToken, 0, 10) . '...)') . "<br>";
    echo "Chat ID: " . (empty($chatId) ? '❌ Vacío' : '✅ ' . $chatId) . "<br>";
    
    // Probar conexión con Telegram
    if (!empty($botToken)) {
        $testUrl = "https://api.telegram.org/bot{$botToken}/getMe";
        $ch = curl_init($testUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $testResponse = curl_exec($ch);
        $testHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
    if ($testHttpCode === 200) {
        $testData = json_decode($testResponse, true);
        if (isset($testData['ok']) && $testData['ok'] === true) {
            echo "Conexión con Telegram API: ✅ OK<br>";
            echo "Bot: " . ($testData['result']['username'] ?? 'N/A') . "<br>";
            
            // Intentar enviar un mensaje de prueba y mostrar el error exacto
            $testMessageUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $testMessageData = [
                'chat_id' => $chatId,
                'text' => 'Test'
            ];
            $ch2 = curl_init($testMessageUrl);
            curl_setopt_array($ch2, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($testMessageData),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $testMsgResponse = curl_exec($ch2);
            $testMsgHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            
            if ($testMsgHttpCode === 200) {
                $testMsgData = json_decode($testMsgResponse, true);
                if (isset($testMsgData['ok']) && $testMsgData['ok'] === true) {
                    echo "Envío de mensaje de prueba: ✅ OK<br>";
                } else {
                    echo "Envío de mensaje de prueba: ❌ Error<br>";
                    echo "Respuesta: " . htmlspecialchars($testMsgResponse) . "<br>";
                }
            } else {
                $testMsgData = json_decode($testMsgResponse, true);
                echo "Envío de mensaje de prueba: ❌ Error HTTP {$testMsgHttpCode}<br>";
                echo "Error: " . ($testMsgData['description'] ?? $testMsgResponse) . "<br>";
                if (isset($testMsgData['error_code'])) {
                    echo "Código de error: " . $testMsgData['error_code'] . "<br>";
                    if ($testMsgData['error_code'] === 403) {
                        echo "<strong>⚠️ El bot no puede enviar mensajes. Asegúrate de haber iniciado el bot primero.</strong><br>";
                        echo "Para iniciar el bot, busca @LumectesBot en Telegram y envía /start<br>";
                    }
                }
            }
        } else {
            echo "Conexión con Telegram API: ❌ Error en respuesta<br>";
        }
    } else {
        echo "Conexión con Telegram API: ❌ Error HTTP {$testHttpCode}<br>";
    }
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<h2>Configuración actual:</h2>";
echo "<ul>";
echo "<li><strong>Bot Token:</strong> " . (defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN !== 'TU_BOT_TOKEN_AQUI' ? '✅ Configurado' : '❌ No configurado') . "</li>";
echo "<li><strong>Chat ID:</strong> " . (defined('TELEGRAM_CHAT_ID') && TELEGRAM_CHAT_ID !== 'TU_CHAT_ID_AQUI' ? '✅ ' . TELEGRAM_CHAT_ID : '❌ No configurado') . "</li>";
echo "</ul>";

echo "<p><a href='/'>← Volver al inicio</a></p>";

