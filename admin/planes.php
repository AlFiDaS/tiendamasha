<?php
/**
 * Plan y Suscripción - Ver planes, pagar con MercadoPago
 */
$pageTitle = 'Plan y Suscripción';
require_once __DIR__ . '/../config.php';
if (!defined('LUME_ADMIN')) define('LUME_ADMIN', true);
require_once __DIR__ . '/../helpers/auth.php';
startSecureSession();
requireAuth();

require_once __DIR__ . '/../helpers/subscription.php';
require_once __DIR__ . '/../helpers/subscription-pricing.php';

$storeId = defined('CURRENT_STORE_ID') ? CURRENT_STORE_ID : 0;
$subscription = getStoreSubscription();

$availablePlans = [
    'free'     => getPlanLimits('free'),
    'basic'    => getPlanLimits('basic'),
    'pro'      => getPlanLimits('pro'),
    'platinum' => getPlanLimits('platinum'),
];

$durations = getSubscriptionDurations();
$platinumCheck = isPlatinumAvailable($storeId);
$_baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$platformApiUrl = $_baseUrl . '/platform/api/subscription-create-preference.php';
$transferApiUrl = $_baseUrl . '/platform/api/subscription-transfer.php';

require_once __DIR__ . '/_inc/header.php';
?>

<div class="page-header">
    <h1>Plan y Suscripción</h1>
    <p>Administrá tu plan y renová tu membresía.</p>
</div>

<?php if (!empty($_GET['payment'])): ?>
<?php
$pm = $_GET['payment'];
$msgs = [
    'success' => '¡Pago exitoso! El administrador revisará tu solicitud y activará el plan pronto.',
    'failure' => 'El pago fue rechazado o cancelado.',
    'pending' => 'El pago está pendiente. Cuando se acredite, el administrador activará tu plan.',
    'transfer_sent' => '¡Comprobante enviado! El administrador revisará tu transferencia y activará el plan pronto.',
];
$msg = $msgs[$pm] ?? 'Estado del pago: ' . htmlspecialchars($pm);
$cls = in_array($pm, ['success', 'transfer_sent']) ? 'alert-success' : ($pm === 'failure' ? 'alert-error' : 'alert-warning');
?>
<div class="alert <?= $cls ?>" style="margin-bottom:1.5rem;"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

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
    <p class="plan-renew-note">Si pagás antes del vencimiento, tu próximo período se suma desde el <?= date('d/m/Y', strtotime($subscription['subscription_ends_at'])) ?>.</p>
    <?php endif; ?>
</div>

<div class="plans-grid">
    <?php foreach (['free', 'basic', 'pro', 'platinum'] as $key): ?>
    <?php $p = $availablePlans[$key]; $isCurrent = ($subscription['plan'] === $key); ?>
    <div class="plan-card <?= $isCurrent ? 'plan-card-current' : '' ?>" data-plan="<?= htmlspecialchars($key) ?>">
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
        <?php if ($isCurrent): ?>
        <div class="plan-card-actions">
            <span class="btn btn-secondary" disabled>Plan actual</span>
        </div>
        <?php elseif ($key === 'free'): ?>
        <div class="plan-card-actions">
            <span class="btn btn-secondary" disabled>Plan base</span>
        </div>
        <?php else: ?>
        <?php
            $planDurations = getValidDurationsForPlan($key);
            $isPlatinumFull = ($key === 'platinum' && !$platinumCheck['available']);
        ?>
        <?php if ($isPlatinumFull): ?>
        <div class="plan-card-sold-out">
            <span class="sold-out-badge">Cupo agotado</span>
            <p>Solo <?= $platinumCheck['max'] ?> tiendas pueden tener este plan. Actualmente están ocupados todos los cupos.</p>
        </div>
        <?php else: ?>
        <?php if ($key === 'platinum'): ?>
        <div class="plan-card-notice">
            Solo disponible en contrato anual. Cupos limitados (<?= $platinumCheck['current'] ?>/<?= $platinumCheck['max'] ?>).
        </div>
        <?php endif; ?>
        <div class="plan-card-duration">
            <label>Duración:</label>
            <select class="plan-duration-select" data-plan="<?= htmlspecialchars($key) ?>" data-price="<?= (int)$p['price'] ?>">
                <?php foreach ($planDurations as $durMonths => $dur): ?>
                <?php $pr = getSubscriptionPrice($key, $durMonths); ?>
                <option value="<?= $durMonths ?>" data-total="<?= $pr['total'] ?>" data-label="<?= htmlspecialchars($pr['duration_label']) ?>">
                    <?= htmlspecialchars($dur['label']) ?> — $<?= number_format($pr['total']) ?>
                    <?php if ($pr['discount_percent'] > 0): ?>(<?= $pr['discount_percent'] ?>% off)<?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="plan-card-actions">
            <button type="button" class="btn btn-primary btn-pay-mp" data-store-id="<?= (int)$storeId ?>" data-plan="<?= htmlspecialchars($key) ?>">
                Pagar con MercadoPago
            </button>
            <button type="button" class="btn btn-secondary btn-pay-transfer" data-store-id="<?= (int)$storeId ?>" data-plan="<?= htmlspecialchars($key) ?>" style="margin-top:0.5rem;">
                Pagar por transferencia
            </button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal de transferencia -->
<div id="transferModal" class="transfer-modal-overlay" style="display:none;">
    <div class="transfer-modal">
        <button type="button" class="transfer-modal-close" id="transferModalClose">&times;</button>
        <h3>Pagar por transferencia</h3>
        <p class="transfer-modal-subtitle">Realizá la transferencia y subí el comprobante. El administrador revisará tu pago y activará el plan.</p>

        <div class="transfer-bank-info">
            <h4>Datos para transferir</h4>
            <div class="transfer-bank-row">
                <span class="transfer-bank-label">Alias</span>
                <span class="transfer-bank-value" id="bankAlias">wemasha</span>
                <button type="button" class="transfer-copy-btn" data-copy="wemasha" title="Copiar">📋</button>
            </div>
            <div class="transfer-bank-row">
                <span class="transfer-bank-label">CBU</span>
                <span class="transfer-bank-value" id="bankCBU">0000003100047210243700</span>
                <button type="button" class="transfer-copy-btn" data-copy="0000003100047210243700" title="Copiar">📋</button>
            </div>
            <div class="transfer-bank-row">
                <span class="transfer-bank-label">Titular</span>
                <span class="transfer-bank-value">Alessandro Hernan Fiorio D'Ascenzo</span>
            </div>
            <div class="transfer-bank-row transfer-bank-amount">
                <span class="transfer-bank-label">Monto a transferir</span>
                <span class="transfer-bank-value" id="transferAmount" style="font-weight:700; font-size:1.1rem;">$0</span>
            </div>
        </div>

        <form id="transferForm" enctype="multipart/form-data">
            <input type="hidden" name="store_id" id="tfStoreId">
            <input type="hidden" name="plan" id="tfPlan">
            <input type="hidden" name="duration_months" id="tfDuration">
            <div class="transfer-form-group">
                <label for="tfPayerName">Tu nombre</label>
                <input type="text" id="tfPayerName" name="payer_name" required placeholder="Nombre completo">
            </div>
            <div class="transfer-form-group">
                <label for="tfProofImage">Comprobante de transferencia</label>
                <input type="file" id="tfProofImage" name="proof_image" accept="image/jpeg,image/png,image/webp" required>
                <small>JPG, PNG o WebP. Máximo 5MB.</small>
            </div>
            <button type="submit" class="btn btn-primary" id="tfSubmitBtn" style="width:100%; margin-top:0.75rem;">
                Enviar comprobante
            </button>
        </form>
    </div>
</div>

<script>
(function(){
    var mpApiUrl = <?= json_encode($platformApiUrl) ?>;
    var tfApiUrl = <?= json_encode($transferApiUrl) ?>;

    // MercadoPago
    document.querySelectorAll('.btn-pay-mp').forEach(function(btn){
        btn.addEventListener('click', function(){
            var storeId = this.dataset.storeId;
            var plan = this.dataset.plan;
            var card = this.closest('.plan-card');
            var select = card.querySelector('.plan-duration-select');
            var duration = select ? parseInt(select.value, 10) : 1;
            if (!storeId || !plan) return;
            this.disabled = true;
            this.textContent = 'Procesando...';
            fetch(mpApiUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({store_id: parseInt(storeId,10), plan: plan, duration_months: duration})
            }).then(function(r){ return r.json(); }).then(function(data){
                if (data.success && data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    alert(data.error || 'Error al crear el pago');
                    btn.disabled = false;
                    btn.textContent = 'Pagar con MercadoPago';
                }
            }).catch(function(){
                alert('Error de conexión');
                btn.disabled = false;
                btn.textContent = 'Pagar con MercadoPago';
            });
        });
    });

    // Transferencia - abrir modal
    var modal = document.getElementById('transferModal');
    document.querySelectorAll('.btn-pay-transfer').forEach(function(btn){
        btn.addEventListener('click', function(){
            var storeId = this.dataset.storeId;
            var plan = this.dataset.plan;
            var card = this.closest('.plan-card');
            var select = card.querySelector('.plan-duration-select');
            var duration = select ? parseInt(select.value, 10) : 1;
            var opt = select ? select.options[select.selectedIndex] : null;
            var total = opt ? opt.dataset.total : '0';

            document.getElementById('tfStoreId').value = storeId;
            document.getElementById('tfPlan').value = plan;
            document.getElementById('tfDuration').value = duration;
            document.getElementById('transferAmount').textContent = '$' + parseInt(total).toLocaleString('es-AR');
            document.getElementById('tfProofImage').value = '';
            document.getElementById('tfSubmitBtn').disabled = false;
            document.getElementById('tfSubmitBtn').textContent = 'Enviar comprobante';
            modal.style.display = 'flex';
        });
    });

    document.getElementById('transferModalClose').addEventListener('click', function(){ modal.style.display = 'none'; });
    modal.addEventListener('click', function(e){ if (e.target === modal) modal.style.display = 'none'; });

    // Copiar datos bancarios
    document.querySelectorAll('.transfer-copy-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var text = this.dataset.copy;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function(){
                    btn.textContent = '✓';
                    setTimeout(function(){ btn.textContent = '📋'; }, 1500);
                });
            }
        });
    });

    // Enviar formulario de transferencia
    document.getElementById('transferForm').addEventListener('submit', function(e){
        e.preventDefault();
        var submitBtn = document.getElementById('tfSubmitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        var formData = new FormData(this);
        fetch(tfApiUrl, {
            method: 'POST',
            body: formData
        }).then(function(r){ return r.json(); }).then(function(data){
            if (data.success) {
                modal.style.display = 'none';
                window.location.href = window.location.pathname + '?payment=transfer_sent';
            } else {
                alert(data.error || 'Error al enviar');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar comprobante';
            }
        }).catch(function(){
            alert('Error de conexión');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar comprobante';
        });
    });
})();
</script>

<div class="plan-info-box">
    <h3>¿Cómo funciona?</h3>
    <ul>
        <li><strong>Duración:</strong> Podés pagar por 1 mes, 6 meses, 1 año o 3 años. A mayor duración, mayor descuento.</li>
        <li><strong>Mensual:</strong> Si empezás el 7 de febrero, tu membresía termina el 7 de marzo (o más adelante según la duración elegida).</li>
        <li><strong>Tolerancia:</strong> Tenés 3 días después del vencimiento para pagar sin perder el plan.</li>
        <li><strong>Renovación:</strong> Si pagás el 10 de marzo (dentro de la tolerancia), tu próximo período se suma desde la fecha de vencimiento.</li>
        <li><strong>Plan Gratuito:</strong> Si no renovás en tiempo, volvés al plan gratuito. Los productos que excedan el límite se ocultan; al volver a pagar se restauran.</li>
        <li><strong>Aprobación:</strong> Después de pagar con MercadoPago, el superadmin revisará tu solicitud y activará el plan en tu tienda.</li>
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
.plan-card-duration { margin-top: 0.75rem; }
.plan-card-duration label { font-size: 0.85rem; color: #6b7280; display: block; margin-bottom: 0.25rem; }
.plan-duration-select { width: 100%; padding: 0.5rem; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 0.9rem; }
.plan-card-annual { margin-top: 0.75rem; font-size: 0.8rem; color: #6b7280; }
.plan-card-sold-out { margin-top: 1rem; text-align: center; padding: 0.75rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; }
.sold-out-badge { display: inline-block; background: #ef4444; color: #fff; font-size: 0.8rem; font-weight: 600; padding: 0.2rem 0.75rem; border-radius: 20px; margin-bottom: 0.4rem; }
.plan-card-sold-out p { font-size: 0.8rem; color: #991b1b; margin: 0.3rem 0 0; }
.plan-card-notice { margin-top: 0.75rem; font-size: 0.8rem; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 0.5rem 0.75rem; }
.plan-info-box { background: #f9fafb; border-radius: 12px; padding: 1.25rem; }
.plan-info-box h3 { font-size: 1rem; margin-bottom: 0.75rem; }
.plan-info-box ul { margin: 0; padding-left: 1.25rem; font-size: 0.9rem; color: #4b5563; line-height: 1.7; }

.transfer-modal-overlay { position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; padding: 1rem; }
.transfer-modal { background: #fff; border-radius: 16px; padding: 1.5rem; max-width: 480px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 25px 50px rgba(0,0,0,0.25); }
.transfer-modal-close { position: absolute; top: 0.75rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af; line-height: 1; }
.transfer-modal h3 { font-size: 1.15rem; margin: 0 0 0.25rem; color: #1f2937; }
.transfer-modal-subtitle { font-size: 0.85rem; color: #6b7280; margin: 0 0 1rem; }
.transfer-bank-info { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; }
.transfer-bank-info h4 { font-size: 0.9rem; color: #0369a1; margin: 0 0 0.75rem; }
.transfer-bank-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0; flex-wrap: wrap; }
.transfer-bank-label { font-size: 0.8rem; color: #6b7280; min-width: 50px; }
.transfer-bank-value { font-size: 0.9rem; font-weight: 600; color: #0c4a6e; word-break: break-all; }
.transfer-bank-amount { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #bae6fd; }
.transfer-copy-btn { background: none; border: none; cursor: pointer; font-size: 0.9rem; padding: 0.15rem 0.3rem; border-radius: 4px; }
.transfer-copy-btn:hover { background: #e0f2fe; }
.transfer-form-group { margin-bottom: 0.75rem; }
.transfer-form-group label { display: block; font-size: 0.85rem; color: #374151; margin-bottom: 0.25rem; font-weight: 500; }
.transfer-form-group input[type="text"],
.transfer-form-group input[type="file"] { width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box; }
.transfer-form-group small { font-size: 0.78rem; color: #9ca3af; }
</style>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
