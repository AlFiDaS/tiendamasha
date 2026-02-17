<?php
/**
 * Configurar Pagos
 * Transferencia directa (alias, CBU, titular) y MercadoPago (tokens)
 */
$pageTitle = 'Configurar Pagos';
require_once '../config.php';
require_once '../helpers/shop-settings.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';
startSecureSession();
requireAuth();

$error = '';
$success = '';

$settings = getShopSettings();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $formData = [
            'transfer_alias' => trim(sanitize($_POST['transfer_alias'] ?? '')),
            'transfer_cbu' => preg_replace('/\D/', '', sanitize($_POST['transfer_cbu'] ?? '')),
            'transfer_titular' => trim(sanitize($_POST['transfer_titular'] ?? '')),
            'mercadopago_access_token' => trim(sanitize($_POST['mercadopago_access_token'] ?? '')),
            'mercadopago_public_key' => trim(sanitize($_POST['mercadopago_public_key'] ?? '')),
            'mercadopago_test_mode' => isset($_POST['mercadopago_test_mode']) ? 1 : 0
        ];

        if (updateShopSettings($formData)) {
            $success = 'Configuración de pagos guardada correctamente';
            $settings = getShopSettings();
        } else {
            $error = 'No se pudo actualizar la configuración. ¿Ejecutaste database-add-payment-fields.sql?';
        }
    }
}

require_once '_inc/header.php';
?>

<div class="admin-content">
    <h2>Configurar Pagos</h2>
    <p style="color: #666; margin-bottom: 2rem;">Configura los datos para transferencia directa y las credenciales de MercadoPago.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div class="content-card" style="margin-bottom: 2rem;">
            <h3 class="section-title">Transferencia directa</h3>
            <p style="color: #666; margin-bottom: 1rem;">Estos datos se mostrarán en el carrito cuando el cliente elija pagar por transferencia.</p>
            
            <div class="form-group">
                <label for="transfer_alias">Alias</label>
                <input type="text" id="transfer_alias" name="transfer_alias" 
                    value="<?= htmlspecialchars($settings['transfer_alias'] ?? '') ?>" 
                    placeholder="Ej: lume.co.mp">
                <small>Alias al que las personas van a transferir (ej: miempresa.mp, nombre.cbu)</small>
            </div>
            
            <div class="form-group">
                <label for="transfer_cbu">CBU</label>
                <input type="text" id="transfer_cbu" name="transfer_cbu" 
                    value="<?= htmlspecialchars($settings['transfer_cbu'] ?? '') ?>" 
                    placeholder="Ej: 0000000000000000000000" maxlength="22">
                <small>CBU de 22 dígitos (solo números)</small>
            </div>
            
            <div class="form-group">
                <label for="transfer_titular">Titular</label>
                <input type="text" id="transfer_titular" name="transfer_titular" 
                    value="<?= htmlspecialchars($settings['transfer_titular'] ?? '') ?>" 
                    placeholder="Ej: Juan Pérez">
                <small>Nombre del titular de la cuenta</small>
            </div>
        </div>
        
        <div class="content-card" style="margin-bottom: 2rem;">
            <h3 class="section-title">MercadoPago</h3>
            <p style="color: #666; margin-bottom: 1rem;">Credenciales para pagos con tarjeta y transferencia vía MercadoPago. Obtén las credenciales en <a href="https://www.mercadopago.com.ar/developers/panel" target="_blank" rel="noopener">el panel de desarrolladores</a>.</p>
            
            <div class="form-group">
                <label for="mercadopago_access_token">Access Token <span style="color: red;">*</span></label>
                <input type="password" id="mercadopago_access_token" name="mercadopago_access_token" 
                    value="<?= htmlspecialchars($settings['mercadopago_access_token'] ?? '') ?>" 
                    placeholder="TEST-... o APP_USR-...">
                <small>Token de acceso. En pruebas usa credenciales que empiecen con TEST-. En producción usa APP_USR-.</small>
            </div>
            
            <div class="form-group">
                <label for="mercadopago_public_key">Public Key (opcional)</label>
                <input type="text" id="mercadopago_public_key" name="mercadopago_public_key" 
                    value="<?= htmlspecialchars($settings['mercadopago_public_key'] ?? '') ?>" 
                    placeholder="APP_USR-...">
                <small>Clave pública. Solo necesaria si usas Checkout Brick u otros componentes del frontend.</small>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="mercadopago_test_mode" name="mercadopago_test_mode" value="1" 
                    <?= (!empty($settings['mercadopago_test_mode']) || !isset($settings['mercadopago_test_mode'])) ? 'checked' : '' ?>>
                <label for="mercadopago_test_mode">Modo prueba (los pagos no serán reales)</label>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Guardar configuración</button>
    </form>
    
    <div class="content-card" style="margin-top: 2rem;">
        <h3 id="guia-header" class="section-title" style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
            <span id="guia-toggle-icon">▼</span> Guía de configuración de MercadoPago
        </h3>
        <div id="guia-mercadopago" class="guia-mercadopago collapsed">
            <p><strong>1. Crear cuenta</strong> en <a href="https://www.mercadopago.com.ar/" target="_blank" rel="noopener">MercadoPago</a> si no tenés una.</p>
            <p><strong>2. Ir al panel de desarrolladores:</strong> <a href="https://www.mercadopago.com.ar/developers/panel" target="_blank" rel="noopener">mercadopago.com.ar/developers/panel</a></p>
            <p><strong>3. Crear una aplicación</strong> con producto "Checkout Pro" y plataforma Web.</p>
            <p><strong>4. Obtener credenciales de prueba:</strong> En tu app, sección "Credenciales de prueba". Copiá el <strong>Access Token</strong> (empieza con TEST-).</p>
            <p><strong>5. Pegar el Access Token</strong> en el campo de arriba y guardar.</p>
            <p><strong>6. Configurar Webhook:</strong> En tu app de MercadoPago, en Webhooks/Notificaciones, agregá la URL:<br>
            <code><?= htmlspecialchars(BASE_URL) ?>/api/mercadopago/webhook.php</code></p>
            <p><strong>7. Tarjetas de prueba:</strong> Visa 4509 9535 6623 3704, CVV 123, cualquier fecha futura → pago aprobado.</p>
            <p><strong>8. Pasar a producción:</strong> Cuando estés listo, usá las credenciales de producción (APP_USR-...) y desmarcá "Modo prueba".</p>
            <p><a href="<?= ADMIN_URL ?>/pagos/guia-mercadopago.php">Ver guía completa →</a></p>
        </div>
    </div>
</div>

<style>
.guia-mercadopago.collapsed { display: none; }
.guia-mercadopago { padding-top: 1rem; }
.guia-mercadopago p { margin-bottom: 0.75rem; color: #444; line-height: 1.6; }
.guia-mercadopago code { background: #f0f0f0; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.9rem; }
.guia-mercadopago a { color: var(--primary-color); }
#guia-mercadopago:not(.collapsed) ~ .section-title #guia-toggle-icon,
#guia-mercadopago.collapsed ~ * { }
</style>
<script>
document.getElementById('guia-header').addEventListener('click', function() {
    const guia = document.getElementById('guia-mercadopago');
    const icon = document.getElementById('guia-toggle-icon');
    guia.classList.toggle('collapsed');
    icon.textContent = guia.classList.contains('collapsed') ? '▼' : '▲';
});
</script>

<?php require_once '_inc/footer.php'; ?>
