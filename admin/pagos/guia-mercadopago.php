<?php
/**
 * Guía completa de configuración de MercadoPago
 */
$pageTitle = 'Guía MercadoPago';
require_once '../../config.php';
if (!defined('LUME_ADMIN')) define('LUME_ADMIN', true);
require_once '../../helpers/auth.php';
startSecureSession();
requireAuth();

require_once '../_inc/header.php';
?>

<div class="admin-content">
    <p style="margin-bottom: 1.5rem;"><a href="<?= ADMIN_URL ?>/pagos.php">← Volver a Configurar pagos</a></p>
    
    <div class="content-card" style="max-width: 800px;">
        <h2>Guía de Configuración de MercadoPago</h2>
        
        <h3 style="margin-top: 1.5rem;">1. Crear cuenta</h3>
        <p>Si no tenés cuenta: <a href="https://www.mercadopago.com.ar/" target="_blank" rel="noopener">mercadopago.com.ar</a> → Registrarte.</p>
        
        <h3>2. Panel de desarrolladores</h3>
        <p><a href="https://www.mercadopago.com.ar/developers/panel" target="_blank" rel="noopener">mercadopago.com.ar/developers/panel</a> → Tus integraciones.</p>
        
        <h3>3. Crear aplicación</h3>
        <p>Crear aplicación → Nombre: tu tienda → Producto: <strong>Checkout Pro</strong> → Plataforma: Web.</p>
        
        <h3>4. Credenciales de prueba</h3>
        <p>En tu app, sección <strong>Credenciales de prueba</strong>. Copiá el <strong>Access Token</strong> (empieza con TEST-).</p>
        
        <h3>5. Configurar en el admin</h3>
        <p>Ir a <a href="<?= ADMIN_URL ?>/pagos.php">Configurar pagos</a> → Pegar el Access Token → Guardar.</p>
        
        <h3>6. Tarjetas de prueba</h3>
        <table class="data-table" style="margin: 1rem 0;">
            <thead><tr><th>Tarjeta</th><th>Número</th><th>CVV</th><th>Resultado</th></tr></thead>
            <tbody>
                <tr><td>Visa</td><td>4509 9535 6623 3704</td><td>123</td><td>✅ Aprobado</td></tr>
                <tr><td>Mastercard</td><td>5031 7557 3453 0604</td><td>123</td><td>✅ Aprobado</td></tr>
                <tr><td>Visa</td><td>4013 5406 8274 6260</td><td>123</td><td>❌ Rechazado</td></tr>
            </tbody>
        </table>
        <p><a href="https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/testing" target="_blank" rel="noopener">Más tarjetas de prueba</a></p>
        
        <h3>7. Webhook (notificaciones)</h3>
        <p>En tu app de MercadoPago → Webhooks/Notificaciones → Agregar URL:</p>
        <p><code><?= htmlspecialchars(BASE_URL) ?>/api/mercadopago/webhook.php</code></p>
        <p>Eventos: <strong>Pagos</strong></p>
        
        <h3>8. Pasar a producción</h3>
        <p>Credenciales de producción → Copiar Access Token (APP_USR-...) → Pegar en Configurar pagos → Desmarcar "Modo prueba".</p>
        
        <h3>Cuotas sin interés</h3>
        <p>MercadoPago → Tu negocio → Costos y cuotas → Cuotas sin interés → Ofrecer 3 cuotas.</p>
        
        <p style="margin-top: 2rem;"><a href="https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/overview" target="_blank" rel="noopener">Documentación oficial de MercadoPago</a></p>
    </div>
</div>

<?php require_once '../_inc/footer.php'; ?>
