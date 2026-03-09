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
require_once __DIR__ . '/../helpers/shop-settings.php';

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
$platinumRequestUrl = $_baseUrl . '/platform/api/platinum-request.php';

$shopSettings = getShopSettings();
$currentShopName = $shopSettings['shop_name'] ?? (defined('SITE_NAME') ? SITE_NAME : '');

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
    'platinum_requested' => '¡Solicitud enviada! Nos comunicaremos por WhatsApp para coordinar tu plan Platinum.',
];
$msg = $msgs[$pm] ?? 'Estado del pago: ' . htmlspecialchars($pm);
$cls = in_array($pm, ['success', 'transfer_sent', 'platinum_requested']) ? 'alert-success' : ($pm === 'failure' ? 'alert-error' : 'alert-warning');
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
    <?php
        $p = $availablePlans[$key];
        $isCurrent = ($subscription['plan'] === $key);
        $isPopular = ($key === 'pro');
        $isPlatinum = ($key === 'platinum');
    ?>
    <div class="plan-card <?= $isCurrent ? 'plan-card-current' : '' ?> <?= $isPopular ? 'plan-card-popular' : '' ?> <?= $isPlatinum ? 'plan-card-platinum' : '' ?>" data-plan="<?= htmlspecialchars($key) ?>">
        <?php if ($isPopular): ?>
        <div class="plan-card-badge">Más popular</div>
        <?php elseif ($isPlatinum): ?>
        <div class="plan-card-badge plan-card-badge-platinum">Premium</div>
        <?php endif; ?>

        <div class="plan-card-header">
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <div class="plan-card-price"><?= htmlspecialchars($p['price_label']) ?></div>
        </div>

        <div class="plan-card-divider"></div>

        <ul class="plan-card-features">
            <li><span class="plan-feat-icon">&#10003;</span> <?= $p['max_products'] === null ? 'Productos ilimitados' : 'Hasta ' . $p['max_products'] . ' productos' ?></li>
            <?php if ($p['templates']): ?><li><span class="plan-feat-icon">&#10003;</span> Múltiples templates</li><?php endif; ?>
            <?php if ($p['custom_domain']): ?><li><span class="plan-feat-icon">&#10003;</span> Dominio personalizado</li><?php endif; ?>
            <?php if ($p['custom_design']): ?><li><span class="plan-feat-icon">&#10003;</span> Diseño 100% personalizado</li><?php endif; ?>
            <?php if ($key === 'free'): ?>
            <li><span class="plan-feat-icon plan-feat-x">&times;</span> <span class="plan-feat-disabled">Sin templates premium</span></li>
            <li><span class="plan-feat-icon plan-feat-x">&times;</span> <span class="plan-feat-disabled">Sin dominio propio</span></li>
            <?php elseif ($key === 'basic'): ?>
            <li><span class="plan-feat-icon plan-feat-x">&times;</span> <span class="plan-feat-disabled">Sin templates premium</span></li>
            <li><span class="plan-feat-icon plan-feat-x">&times;</span> <span class="plan-feat-disabled">Sin dominio propio</span></li>
            <?php elseif ($key === 'pro'): ?>
            <li><span class="plan-feat-icon plan-feat-x">&times;</span> <span class="plan-feat-disabled">Sin dominio propio</span></li>
            <?php endif; ?>
        </ul>

        <div class="plan-card-footer">
            <?php if ($isCurrent): ?>
            <button class="plan-btn plan-btn-current" disabled>Tu plan actual</button>
            <?php elseif ($key === 'free'): ?>
            <button class="plan-btn plan-btn-disabled" disabled>Plan gratuito</button>
            <?php elseif ($isPlatinum): ?>
                <?php $isPlatinumFull = !$platinumCheck['available']; ?>
                <?php if ($isPlatinumFull): ?>
                <div class="plan-card-sold-out">
                    <span class="sold-out-badge">Cupo agotado</span>
                    <p>Solo <?= $platinumCheck['max'] ?> cupos disponibles.</p>
                </div>
                <?php else: ?>
                <div class="plan-card-notice">
                    Contrato anual personalizado.<br>
                    Cupos limitados (<?= $platinumCheck['current'] ?>/<?= $platinumCheck['max'] ?>).<br>
                    También a 3 años con <strong>25% off</strong>.
                </div>
                <button type="button" class="plan-btn plan-btn-platinum btn-request-platinum" data-store-id="<?= (int)$storeId ?>">
                    Solicitar plan Platinum
                </button>
                <?php endif; ?>
            <?php else: ?>
                <?php $planDurations = getValidDurationsForPlan($key); ?>
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
                <button type="button" class="plan-btn plan-btn-primary btn-pay-mp" data-store-id="<?= (int)$storeId ?>" data-plan="<?= htmlspecialchars($key) ?>">
                    Pagar con MercadoPago
                </button>
                <button type="button" class="plan-btn plan-btn-secondary btn-pay-transfer" data-store-id="<?= (int)$storeId ?>" data-plan="<?= htmlspecialchars($key) ?>">
                    Pagar por transferencia
                </button>
            <?php endif; ?>
        </div>
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
            <button type="submit" class="plan-btn plan-btn-primary" id="tfSubmitBtn" style="margin-top:0.5rem;">
                Enviar comprobante
            </button>
        </form>
    </div>
</div>

<!-- Modal solicitud Platinum -->
<div id="platinumModal" class="transfer-modal-overlay" style="display:none;">
    <div class="transfer-modal">
        <button type="button" class="transfer-modal-close" id="platinumModalClose">&times;</button>
        <h3>Solicitar plan Platinum</h3>
        <p class="transfer-modal-subtitle">Completá tus datos y nos comunicaremos por WhatsApp para coordinar tu plan personalizado.</p>

        <form id="platinumForm">
            <input type="hidden" name="store_id" id="ptStoreId">
            <div class="transfer-form-group">
                <label for="ptContactName">Nombre completo</label>
                <input type="text" id="ptContactName" name="contact_name" required placeholder="Tu nombre y apellido">
            </div>
            <div class="transfer-form-group">
                <label for="ptPhone">Teléfono / WhatsApp</label>
                <input type="tel" id="ptPhone" name="phone" required placeholder="Ej: +54 9 379 4123456">
            </div>
            <div class="transfer-form-group">
                <label for="ptShopName">Nombre de la tienda</label>
                <input type="text" id="ptShopName" name="shop_name" required value="<?= htmlspecialchars($currentShopName) ?>">
            </div>
            <button type="submit" class="plan-btn plan-btn-platinum" id="ptSubmitBtn" style="margin-top:0.5rem;">
                Enviar solicitud
            </button>
        </form>
    </div>
</div>

<script>
(function(){
    var mpApiUrl = <?= json_encode($platformApiUrl) ?>;
    var tfApiUrl = <?= json_encode($transferApiUrl) ?>;
    var ptApiUrl = <?= json_encode($platinumRequestUrl) ?>;

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

    // Platinum - abrir modal
    var ptModal = document.getElementById('platinumModal');
    document.querySelectorAll('.btn-request-platinum').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.getElementById('ptStoreId').value = this.dataset.storeId;
            document.getElementById('ptSubmitBtn').disabled = false;
            document.getElementById('ptSubmitBtn').textContent = 'Enviar solicitud';
            ptModal.style.display = 'flex';
        });
    });

    document.getElementById('platinumModalClose').addEventListener('click', function(){ ptModal.style.display = 'none'; });
    ptModal.addEventListener('click', function(e){ if (e.target === ptModal) ptModal.style.display = 'none'; });

    // Enviar solicitud Platinum
    document.getElementById('platinumForm').addEventListener('submit', function(e){
        e.preventDefault();
        var submitBtn = document.getElementById('ptSubmitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        var payload = {
            store_id: parseInt(document.getElementById('ptStoreId').value, 10),
            contact_name: document.getElementById('ptContactName').value.trim(),
            phone: document.getElementById('ptPhone').value.trim(),
            shop_name: document.getElementById('ptShopName').value.trim()
        };

        fetch(ptApiUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(function(r){ return r.json(); }).then(function(data){
            if (data.success) {
                ptModal.style.display = 'none';
                window.location.href = window.location.pathname + '?payment=platinum_requested';
            } else {
                alert(data.error || 'Error al enviar');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar solicitud';
            }
        }).catch(function(){
            alert('Error de conexión');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar solicitud';
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
/* Page header */
.page-header { margin-bottom: 2rem; text-align: center; }
.page-header h1 { font-size: 1.75rem; color: #0f172a; font-weight: 800; letter-spacing: -0.025em; }
.page-header p { color: #64748b; font-size: 1rem; margin-top: 0.35rem; }

/* Current plan banner */
.plan-current-card { background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border: 1px solid #bfdbfe; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; }
.plan-current-card h2 { font-size: 0.85rem; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 0.25rem; }
.plan-current-info { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.plan-current-label { font-weight: 800; font-size: 1.15rem; color: #1e3a5f; }
.plan-current-ends { font-size: 0.88rem; color: #3b82f6; background: #dbeafe; padding: 0.2rem 0.75rem; border-radius: 20px; font-weight: 500; }
.plan-renew-note { font-size: 0.82rem; color: #1e40af; margin-top: 0.25rem; width: 100%; }

/* Plans grid */
.plans-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 2rem; align-items: stretch; }
@media (max-width: 1100px) { .plans-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 540px) { .plans-grid { grid-template-columns: 1fr; max-width: 380px; margin-left: auto; margin-right: auto; } }

/* Plan card */
.plan-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.75rem 1.5rem 1.5rem;
    display: flex;
    flex-direction: column;
    position: relative;
    transition: box-shadow 0.2s, border-color 0.2s, transform 0.2s;
}
.plan-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.08); transform: translateY(-2px); }
.plan-card-current { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.18); }
.plan-card-popular { border-color: #6366f1; }
.plan-card-platinum { border-color: #f59e0b; background: linear-gradient(180deg, #fffdf5 0%, #fff 40%); }

/* Badge */
.plan-card-badge {
    position: absolute; top: -11px; left: 50%; transform: translateX(-50%);
    background: linear-gradient(135deg, #6366f1, #818cf8); color: #fff;
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
    padding: 0.25rem 1rem; border-radius: 20px; white-space: nowrap;
}
.plan-card-badge-platinum { background: linear-gradient(135deg, #f59e0b, #d97706); }

/* Card header */
.plan-card-header { text-align: center; margin-bottom: 0.25rem; }
.plan-card-header h3 { font-size: 1.15rem; font-weight: 700; color: #1e293b; margin: 0 0 0.5rem; }
.plan-card-price { font-weight: 800; color: #0f172a; font-size: 1.5rem; letter-spacing: -0.02em; }
.plan-card-platinum .plan-card-price { color: #b45309; }

/* Divider */
.plan-card-divider { height: 1px; background: #f1f5f9; margin: 1rem 0; }

/* Features */
.plan-card-features { list-style: none; margin: 0 0 auto; padding: 0; font-size: 0.88rem; color: #475569; }
.plan-card-features li { padding: 0.35rem 0; display: flex; align-items: center; gap: 0.5rem; }
.plan-feat-icon { color: #22c55e; font-weight: 700; font-size: 0.95rem; flex-shrink: 0; width: 18px; text-align: center; }
.plan-feat-x { color: #cbd5e1; font-size: 1.1rem; }
.plan-feat-disabled { color: #94a3b8; }

/* Card footer / actions */
.plan-card-footer { margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; }

/* Buttons */
.plan-btn {
    display: block; width: 100%; padding: 0.75rem 1rem;
    border: none; border-radius: 10px; cursor: pointer;
    font-size: 0.92rem; font-weight: 600; text-align: center;
    transition: background 0.15s, box-shadow 0.15s, opacity 0.15s;
    line-height: 1.4;
}
.plan-btn-primary { background: #6366f1; color: #fff; }
.plan-btn-primary:hover { background: #4f46e5; box-shadow: 0 4px 14px rgba(99,102,241,0.35); }
.plan-btn-secondary { background: #f1f5f9; color: #475569; }
.plan-btn-secondary:hover { background: #e2e8f0; }
.plan-btn-platinum { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
.plan-btn-platinum:hover { background: linear-gradient(135deg, #d97706, #b45309); box-shadow: 0 4px 14px rgba(245,158,11,0.35); }
.plan-btn-current { background: #ede9fe; color: #6366f1; cursor: default; font-weight: 700; }
.plan-btn-disabled { background: #f8fafc; color: #94a3b8; cursor: default; border: 1px solid #e2e8f0; }

/* Duration selector */
.plan-card-duration { margin-bottom: 0.25rem; }
.plan-card-duration label { font-size: 0.8rem; color: #64748b; display: block; margin-bottom: 0.35rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.plan-duration-select {
    width: 100%; padding: 0.6rem 0.75rem; border-radius: 10px;
    border: 1px solid #e2e8f0; font-size: 0.88rem; color: #1e293b;
    background: #f8fafc; appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 0.75rem center;
    cursor: pointer; transition: border-color 0.15s;
}
.plan-duration-select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }

/* Sold out */
.plan-card-sold-out { text-align: center; padding: 0.75rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; }
.sold-out-badge { display: inline-block; background: #ef4444; color: #fff; font-size: 0.78rem; font-weight: 700; padding: 0.25rem 0.85rem; border-radius: 20px; margin-bottom: 0.3rem; }
.plan-card-sold-out p { font-size: 0.8rem; color: #991b1b; margin: 0.25rem 0 0; }

/* Notice */
.plan-card-notice {
    font-size: 0.8rem; color: #92400e; background: #fffbeb; border: 1px solid #fde68a;
    border-radius: 10px; padding: 0.6rem 0.85rem; text-align: center; line-height: 1.5;
    margin-bottom: 0.25rem;
}

/* Info box */
.plan-info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.5rem; }
.plan-info-box h3 { font-size: 1.05rem; margin-bottom: 0.75rem; color: #1e293b; font-weight: 700; }
.plan-info-box ul { margin: 0; padding-left: 1.25rem; font-size: 0.88rem; color: #475569; line-height: 1.8; }

/* Modals */
.transfer-modal-overlay { position: fixed; inset: 0; z-index: 9999; background: rgba(15,23,42,0.55); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1rem; }
.transfer-modal { background: #fff; border-radius: 20px; padding: 2rem 1.75rem; max-width: 460px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 25px 60px rgba(0,0,0,0.2); }
.transfer-modal-close { position: absolute; top: 1rem; right: 1.25rem; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; color: #64748b; display: flex; align-items: center; justify-content: center; transition: background 0.15s; line-height: 1; }
.transfer-modal-close:hover { background: #e2e8f0; }
.transfer-modal h3 { font-size: 1.2rem; margin: 0 0 0.25rem; color: #0f172a; font-weight: 700; }
.transfer-modal-subtitle { font-size: 0.88rem; color: #64748b; margin: 0 0 1.25rem; }
.transfer-bank-info { background: linear-gradient(135deg, #eff6ff, #f0f9ff); border: 1px solid #bfdbfe; border-radius: 12px; padding: 1rem 1.15rem; margin-bottom: 1.25rem; }
.transfer-bank-info h4 { font-size: 0.88rem; color: #2563eb; margin: 0 0 0.75rem; font-weight: 700; }
.transfer-bank-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0; flex-wrap: wrap; }
.transfer-bank-label { font-size: 0.78rem; color: #64748b; min-width: 55px; }
.transfer-bank-value { font-size: 0.9rem; font-weight: 600; color: #1e3a5f; word-break: break-all; }
.transfer-bank-amount { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #bfdbfe; }
.transfer-copy-btn { background: #dbeafe; border: none; cursor: pointer; font-size: 0.82rem; padding: 0.2rem 0.4rem; border-radius: 6px; transition: background 0.15s; }
.transfer-copy-btn:hover { background: #bfdbfe; }
.transfer-form-group { margin-bottom: 1rem; }
.transfer-form-group label { display: block; font-size: 0.85rem; color: #334155; margin-bottom: 0.35rem; font-weight: 600; }
.transfer-form-group input[type="text"],
.transfer-form-group input[type="tel"],
.transfer-form-group input[type="file"] {
    width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #e2e8f0; border-radius: 10px;
    font-size: 0.9rem; box-sizing: border-box; background: #f8fafc;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.transfer-form-group input:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); background: #fff; }
.transfer-form-group small { font-size: 0.78rem; color: #94a3b8; margin-top: 0.25rem; display: block; }
</style>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
