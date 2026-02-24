<?php
/**
 * Configurar Notificaciones (Bot de Telegram)
 * Recibe alertas de nuevas órdenes y pagos aprobados
 */
$pageTitle = 'Configurar Notificaciones';
require_once '../config.php';
require_once '../helpers/shop-settings.php';
require_once '../helpers/telegram.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';
startSecureSession();
requireAuth();

$error = '';
$success = '';
$testResult = '';

$settings = getShopSettings();

// Solo usar valores de la tienda; nuevas tiendas deben configurar vacío (no heredar .env)
$botToken = $settings['telegram_bot_token'] ?? '';
$chatId = $settings['telegram_chat_id'] ?? '';
$telegramEnabled = isset($settings['telegram_enabled']) ? (int)$settings['telegram_enabled'] : 0;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        // Probar envío (AJAX o form action)
        if (isset($_POST['probar_telegram'])) {
            $testMsg = "🧪 <b>Prueba de Notificación</b>\n\nSi recibís este mensaje, ¡la configuración está correcta!";
            $token = trim(sanitize($_POST['telegram_bot_token'] ?? ''));
            $cid = trim(sanitize($_POST['telegram_chat_id'] ?? ''));
            if (empty($token) || empty($cid)) {
                $testResult = 'error';
                $error = 'Completá el Bot Token y el Chat ID antes de probar.';
            } else {
                // Enviar con los valores del form (temporal)
                $url = "https://api.telegram.org/bot{$token}/sendMessage";
                $data = ['chat_id' => $cid, 'text' => $testMsg, 'parse_mode' => 'HTML'];
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_TIMEOUT => 10
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode === 200) {
                    $testResult = 'ok';
                    $success = '¡Mensaje de prueba enviado! Revisá tu Telegram.';
                } else {
                    $testResult = 'error';
                    $resp = json_decode($response, true);
                    $error = 'Error al enviar: ' . ($resp['description'] ?? "HTTP $httpCode");
                }
            }
        } else {
            $formData = [
                'telegram_bot_token' => trim(sanitize($_POST['telegram_bot_token'] ?? '')),
                'telegram_chat_id' => trim(sanitize($_POST['telegram_chat_id'] ?? '')),
                'telegram_enabled' => isset($_POST['telegram_enabled']) ? 1 : 0
            ];

            if (updateShopSettings($formData)) {
                $success = 'Configuración de notificaciones guardada correctamente';
                $settings = getShopSettings();
                $botToken = $settings['telegram_bot_token'] ?? '';
                $chatId = $settings['telegram_chat_id'] ?? '';
                $telegramEnabled = (int)($settings['telegram_enabled'] ?? 0);
            } else {
                $error = 'No se pudo actualizar. ¿Ejecutaste database-add-telegram-fields.sql?';
            }
        }
    }
}

require_once '_inc/header.php';
?>

<div class="admin-content">
    <h2>Configurar Notificaciones</h2>
    <p style="color: #666; margin-bottom: 2rem;">Recibí alertas por Telegram cuando haya nuevas órdenes o pagos aprobados.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= icon('x', 18) ?> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= icon('check', 18) ?> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div class="content-card" style="margin-bottom: 2rem;">
            <h3 class="section-title">Bot de Telegram</h3>
            <p style="color: #666; margin-bottom: 1rem;">Configurá tu bot para recibir notificaciones de pedidos en tiempo real.</p>
            
            <div class="form-group checkbox-group" style="margin-bottom: 1.5rem;">
                <input type="checkbox" id="telegram_enabled" name="telegram_enabled" value="1" 
                    <?= $telegramEnabled ? 'checked' : '' ?>>
                <label for="telegram_enabled"><strong>Activar notificaciones por Telegram</strong></label>
                <small style="display: block; margin-top: 0.25rem;">Cuando está desactivado, no se envían mensajes aunque los datos estén configurados.</small>
            </div>
            
            <div class="form-group">
                <label for="telegram_bot_token">Bot Token <span style="color: red;">*</span></label>
                <input type="password" id="telegram_bot_token" name="telegram_bot_token" 
                    value="<?= htmlspecialchars($botToken) ?>" 
                    placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                <small>Obtenelo de @BotFather en Telegram (ver guía abajo)</small>
            </div>
            
            <div class="form-group">
                <label for="telegram_chat_id">Chat ID <span style="color: red;">*</span></label>
                <input type="text" id="telegram_chat_id" name="telegram_chat_id" 
                    value="<?= htmlspecialchars($chatId) ?>" 
                    placeholder="Ej: 8228149072">
                <small>Obtenelo de @userinfobot en Telegram (ver guía abajo)</small>
            </div>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">Guardar configuración</button>
                <button type="submit" name="probar_telegram" value="1" class="btn btn-secondary"><?= icon('smartphone', 18) ?> Enviar mensaje de prueba</button>
            </div>
        </div>
    </form>
    
    <div class="content-card" style="margin-top: 2rem;">
        <h3 id="guia-telegram-header" class="section-title" style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
            <span id="guia-telegram-icon">▼</span> Guía paso a paso para configurar Telegram
        </h3>
        <div id="guia-telegram" class="guia-telegram collapsed">
            <div class="guia-paso">
                <h4>1. Crear tu bot</h4>
                <p>Abri Telegram y buscá <strong>@BotFather</strong>. Enviá el comando <code>/newbot</code>.</p>
                <p>Te pedirá un nombre (ej: "Notificaciones Mi Tienda") y un username que termine en "bot" (ej: <code>mitienda_notif_bot</code>).</p>
                <p><strong>Guardá el token</strong> que te da BotFather. Se ve así: <code>123456789:ABCdefGHIjklMNOpqrsTUVwxyz</code></p>
            </div>
            <div class="guia-paso">
                <h4>2. Obtener tu Chat ID</h4>
                <p>Buscá <strong>@userinfobot</strong> en Telegram y enviá cualquier mensaje (ej: "hola").</p>
                <p>Te responderá con tu información. El <strong>Chat ID</strong> es un número como <code>8228149072</code>.</p>
            </div>
            <div class="guia-paso">
                <h4>3. Iniciar el bot</h4>
                <p>Antes de recibir mensajes, tenés que <strong>iniciar una conversación</strong> con tu bot. Buscá el username de tu bot (ej: @mitienda_notif_bot) y enviá <code>/start</code>.</p>
            </div>
            <div class="guia-paso">
                <h4>4. Completar los datos</h4>
                <p>Pegá el Bot Token y el Chat ID en los campos de arriba, activá las notificaciones y guardá.</p>
            </div>
            <div class="guia-paso">
                <h4>5. Probar</h4>
                <p>Hacé clic en <strong>"Enviar mensaje de prueba"</strong>. Si recibís el mensaje en Telegram, ¡está todo listo!</p>
            </div>
            <p style="margin-top: 1rem;"><strong>¿Cuándo recibirás notificaciones?</strong></p>
            <ul style="margin-left: 1.5rem; color: #444;">
                <li>Cuando se crea una nueva orden (cualquier método de pago)</li>
                <li>Cuando MercadoPago confirma un pago aprobado</li>
            </ul>
        </div>
    </div>
</div>

<style>
.guia-telegram.collapsed { display: none; }
.guia-telegram { padding-top: 1rem; }
.guia-telegram .guia-paso { margin-bottom: 1.25rem; }
.guia-telegram .guia-paso h4 { margin: 0 0 0.5rem 0; color: #333; }
.guia-telegram .guia-paso p { margin: 0.25rem 0; color: #444; line-height: 1.6; }
.guia-telegram code { background: #f0f0f0; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.9rem; }
</style>
<script>
document.getElementById('guia-telegram-header').addEventListener('click', function() {
    const guia = document.getElementById('guia-telegram');
    const icon = document.getElementById('guia-telegram-icon');
    guia.classList.toggle('collapsed');
    icon.textContent = guia.classList.contains('collapsed') ? '▼' : '▲';
});
</script>

<?php require_once '_inc/footer.php'; ?>
