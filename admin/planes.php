<?php
/**
 * Plan y Suscripción - Ver planes, solicitar cambio, pagar
 */
$pageTitle = 'Plan y Suscripción';
require_once __DIR__ . '/../config.php';
if (!defined('LUME_ADMIN')) define('LUME_ADMIN', true);
require_once __DIR__ . '/../helpers/auth.php';
startSecureSession();
requireAuth();

require_once __DIR__ . '/../helpers/subscription.php';
require_once __DIR__ . '/../helpers/shop-settings.php';

$storeId = defined('CURRENT_STORE_ID') ? CURRENT_STORE_ID : 0;
$subscription = getStoreSubscription();
$shopSettings = getShopSettings();
$whatsapp = $shopSettings['whatsapp_number'] ?? '';

// Planes disponibles (para mostrar)
$availablePlans = [
    'free'     => getPlanLimits('free'),
    'basic'    => getPlanLimits('basic'),
    'pro'      => getPlanLimits('pro'),
    'platinum' => getPlanLimits('platinum'),
];

// Promo anual: 2 meses gratis (10 meses por precio de 12)
$annualDiscountPercent = 17; // ~2 meses de 12

// Procesar solicitud de cambio (por ahora redirige a WhatsApp)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['solicitar_plan'])) {
    $requestedPlan = preg_replace('/[^a-z]/', '', strtolower($_POST['solicitar_plan'] ?? ''));
    if (in_array($requestedPlan, ['basic', 'pro', 'platinum'])) {
        $limits = getPlanLimits($requestedPlan);
        $planName = $limits['name'] ?? ucfirst($requestedPlan);
        $msg = rawurlencode("Hola, quiero solicitar el cambio a *Plan {$planName}* para mi tienda.");
        if ($whatsapp) {
            $wa = preg_replace('/\D/', '', $whatsapp);
            if (substr($wa, 0, 2) === '54') $wa = $wa; else $wa = '54' . $wa;
            header('Location: https://wa.me/' . $wa . '?text=' . $msg);
            exit;
        }
        $_SESSION['success_message'] = 'Solicitud registrada. Te contactaremos pronto.';
    }
    header('Location: ' . ADMIN_URL . '/planes.php');
    exit;
}

require_once __DIR__ . '/_inc/header.php';
?>

<div class="page-header">
    <h1>Plan y Suscripción</h1>
    <p>Administrá tu plan, renová tu membresía o solicitá un upgrade.</p>
</div>

<?php if ($subscription['status'] === 'grace'): ?>
<div class="alert alert-warning">
    <strong>Período de gracia:</strong> Tu suscripción venció el <?= date('d/m/Y', strtotime($subscription['subscription_ends_at'])) ?>.
    Tenés <?= SUBSCRIPTION_GRACE_DAYS ?> días para renovar sin perder tus productos. Pasado ese plazo volverás al Plan Gratuito.
</div>
<?php endif; ?>

<div class="plan-current-card">
    <h2>Tu plan actual</h2>
    <div class="plan-current-info">
        <span class="plan-current-label"><?= htmlspecialchars($subscription['label']) ?></span>
        <?php if ($subscription['subscription_ends_at']): ?>
        <span class="plan-current-ends">Vence: <?= date('d/m/Y', strtotime($subscription['subscription_ends_at'])) ?></span>
        <?php endif; ?>
    </div>
    <?php if ($subscription['plan'] !== 'free' && in_array($subscription['status'], ['active', 'grace']) && $subscription['subscription_ends_at']): ?>
    <p class="plan-renew-note">Si pagás el <?= date('d/m', strtotime($subscription['subscription_ends_at'])) ?>, tu próximo mes será del <?= date('d/m', strtotime($subscription['subscription_ends_at'])) ?> al <?= date('d/m', strtotime($subscription['subscription_ends_at'] . ' +1 month')) ?>.</p>
    <?php if ($whatsapp): ?>
    <?php $wa = preg_replace('/\D/', '', $whatsapp); if (substr($wa, 0, 2) !== '54') $wa = '54' . $wa; ?>
    <a href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode('Hola, quiero pagar/renovar mi plan ' . $subscription['label']) ?>" class="btn btn-primary" style="margin-top:0.75rem;" target="_blank" rel="noopener">Pagar / Renovar este mes</a>
    <?php endif; ?>
    <?php endif; ?>
</div>

<div class="plans-grid">
    <?php foreach (['free', 'basic', 'pro', 'platinum'] as $key): ?>
    <?php $p = $availablePlans[$key]; $isCurrent = ($subscription['plan'] === $key); ?>
    <div class="plan-card <?= $isCurrent ? 'plan-card-current' : '' ?>">
        <div class="plan-card-header">
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <div class="plan-card-price"><?= htmlspecialchars($p['price_label']) ?></div>
        </div>
        <ul class="plan-card-features">
            <li><?= $p['max_products'] === null ? 'Productos ilimitados' : 'Máx. ' . $p['max_products'] . ' productos' ?></li>
            <?php if ($p['templates']): ?><li>Múltiples templates</li><?php endif; ?>
            <?php if ($p['custom_domain']): ?><li>Dominio personalizado</li><?php endif; ?>
            <?php if ($p['custom_design']): ?><li>Diseño 100% personalizado</li><?php endif; ?>
        </ul>
        <div class="plan-card-actions">
            <?php if ($isCurrent): ?>
            <span class="btn btn-secondary" disabled>Plan actual</span>
            <?php elseif ($key === 'free'): ?>
            <span class="btn btn-secondary" disabled>Plan base</span>
            <?php else: ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="solicitar_plan" value="<?= htmlspecialchars($key) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                <button type="submit" class="btn btn-primary">Solicitar Plan <?= htmlspecialchars($p['name']) ?></button>
            </form>
            <?php endif; ?>
        </div>
        <?php if ($key !== 'free' && $p['price'] > 0): ?>
        <div class="plan-card-annual">
            <small>Promo anual: <?= $annualDiscountPercent ?>% off ($<?= number_format($p['price'] * 10) ?> por 12 meses)</small>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<div class="plan-info-box">
    <h3>¿Cómo funciona?</h3>
    <ul>
        <li><strong>Mensual:</strong> Si empezás el 7 de febrero, tu membresía termina el 7 de marzo.</li>
        <li><strong>Tolerancia:</strong> Tenés 3 días después del vencimiento para pagar sin perder el plan.</li>
        <li><strong>Renovación:</strong> Si pagás el 10 de marzo (dentro de la tolerancia), tu 2º mes va del 7 de marzo al 7 de abril.</li>
        <li><strong>Plan Gratuito:</strong> Si no renovás en tiempo, volvés al plan gratuito. Los productos que excedan el límite se ocultan; al volver a pagar se restauran.</li>
    </ul>
</div>

<style>
.page-header { margin-bottom: 1.5rem; }
.page-header h1 { font-size: 1.5rem; color: #1f2937; }
.page-header p { color: #6b7280; font-size: 0.95rem; margin-top: 0.25rem; }
.plan-current-card { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; }
.plan-current-card h2 { font-size: 1rem; color: #0369a1; margin-bottom: 0.5rem; }
.plan-current-info { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.plan-current-label { font-weight: 700; font-size: 1.1rem; color: #0c4a6e; }
.plan-current-ends { font-size: 0.9rem; color: #0369a1; }
.plan-renew-note { font-size: 0.85rem; color: #0c4a6e; margin-top: 0.5rem; }
.plans-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.plan-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem; }
.plan-card-current { border-color: var(--primary-color, #5672E1); box-shadow: 0 0 0 2px rgba(86,114,225,0.2); }
.plan-card-header h3 { font-size: 1.1rem; margin-bottom: 0.25rem; }
.plan-card-price { font-weight: 700; color: var(--primary-color, #5672E1); font-size: 1.1rem; }
.plan-card-features { list-style: none; margin: 1rem 0; font-size: 0.9rem; color: #4b5563; }
.plan-card-features li { padding: 0.25rem 0; }
.plan-card-actions { margin-top: 1rem; }
.plan-card-annual { margin-top: 0.75rem; font-size: 0.8rem; color: #6b7280; }
.plan-info-box { background: #f9fafb; border-radius: 12px; padding: 1.25rem; }
.plan-info-box h3 { font-size: 1rem; margin-bottom: 0.75rem; }
.plan-info-box ul { margin: 0; padding-left: 1.25rem; font-size: 0.9rem; color: #4b5563; line-height: 1.7; }
</style>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
