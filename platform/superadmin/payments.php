<?php
/**
 * Super Admin - Solicitudes de pago de membresía
 * Lista pagos recibidos (status=paid) para aprobar o rechazar
 */
$pageTitle = 'Solicitudes de pago';
require_once __DIR__ . '/../../platform-config.php';

require_once __DIR__ . '/../../helpers/superadmin.php';
require_once __DIR__ . '/../../helpers/subscription-pricing.php';
requireSuperAdmin();

$filter = $_GET['filter'] ?? 'paid'; // paid, approved, rejected, all
$validFilters = ['paid', 'approved', 'rejected', 'all'];
if (!in_array($filter, $validFilters)) {
    $filter = 'paid';
}

$where = $filter === 'all' ? '1=1' : "pr.status = :status";
$params = $filter === 'all' ? [] : ['status' => $filter];

$requests = platformFetchAll(
    "SELECT pr.*, s.slug as store_slug, s.plan as store_current_plan, s.subscription_ends_at,
            pu.name as owner_name, pu.email as owner_email
     FROM payment_requests pr
     JOIN stores s ON s.id = pr.store_id
     LEFT JOIN platform_users pu ON pu.id = s.owner_id
     WHERE $where
     ORDER BY pr.paid_at DESC, pr.created_at DESC",
    $params
);

$statusLabels = [
    'pending_payment' => 'Pendiente pago',
    'paid'            => 'Pagado (pendiente aprobar)',
    'approved'        => 'Aprobado',
    'rejected'        => 'Rechazado',
];
$planLabels = ['basic' => 'Basic', 'pro' => 'Pro', 'platinum' => 'Platinum'];
$durationLabels = [1 => '1 mes', 6 => '6 meses', 12 => '1 año', 36 => '3 años'];

require_once __DIR__ . '/_inc/header.php';
?>

<div class="sa-page-header">
    <h1>Solicitudes de pago</h1>
    <p>Revisá los pagos de membresía recibidos y aprobalos para activar el plan en cada tienda.</p>
</div>

<div class="sa-filter-tabs" style="margin-bottom:1.5rem;">
    <a href="?filter=paid" class="sa-filter-tab <?= $filter === 'paid' ? 'active' : '' ?>">Pendientes de aprobar</a>
    <a href="?filter=approved" class="sa-filter-tab <?= $filter === 'approved' ? 'active' : '' ?>">Aprobados</a>
    <a href="?filter=rejected" class="sa-filter-tab <?= $filter === 'rejected' ? 'active' : '' ?>">Rechazados</a>
    <a href="?filter=all" class="sa-filter-tab <?= $filter === 'all' ? 'active' : '' ?>">Todos</a>
</div>

<div class="sa-table-wrap sa-has-mobile-cards">
    <div class="sa-table-scroll">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tienda</th>
                    <th>Plan</th>
                    <th>Duración</th>
                    <th>Monto</th>
                    <th>Método</th>
                    <th>Pagador</th>
                    <th>Pagado</th>
                    <th>Estado</th>
                    <?php if ($filter === 'paid'): ?>
                    <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="<?= $filter === 'paid' ? 10 : 9 ?>" style="text-align:center; padding:3rem; color:var(--sa-muted);">
                        No hay solicitudes <?= $filter === 'paid' ? 'pendientes de aprobar' : ($filter === 'all' ? '' : 'en este estado') ?>.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td style="color:var(--sa-muted);">#<?= (int)$r['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['store_slug']) ?></strong>
                        <div style="font-size:0.78rem; color:var(--sa-muted);"><?= htmlspecialchars($r['owner_name'] ?? $r['owner_email'] ?? '-') ?></div>
                    </td>
                    <td><span class="sa-badge sa-badge-<?= $r['plan'] ?>"><?= $planLabels[$r['plan']] ?? $r['plan'] ?></span></td>
                    <td><?= $durationLabels[$r['duration_months']] ?? $r['duration_months'] . ' meses' ?></td>
                    <td style="font-weight:600;">$<?= number_format($r['amount'], 0, ',', '.') ?></td>
                    <td>
                        <?php $isTransfer = ($r['payment_method'] ?? 'mercadopago') === 'transferencia'; ?>
                        <span class="sa-badge <?= $isTransfer ? 'sa-badge-setup' : 'sa-badge-active' ?>">
                            <?= $isTransfer ? 'Transferencia' : 'MercadoPago' ?>
                        </span>
                        <?php if ($isTransfer && !empty($r['proof_image'])): ?>
                        <div class="sa-proof-actions">
                            <a href="<?= htmlspecialchars(PLATFORM_URL . $r['proof_image']) ?>" target="_blank" class="sa-proof-link">Ver</a>
                            <a href="<?= htmlspecialchars(PLATFORM_URL . $r['proof_image']) ?>" download class="sa-proof-link sa-proof-download">Descargar</a>
                        </div>
                        <?php elseif ($isTransfer): ?>
                        <span style="font-size:0.75rem; color:var(--sa-muted);">Sin comprobante</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($r['payer_name'] ?? '-') ?></div>
                        <div style="font-size:0.78rem; color:var(--sa-muted);"><?= htmlspecialchars($r['payer_email'] ?? '') ?></div>
                    </td>
                    <td><?= $r['paid_at'] ? date('d/m/Y H:i', strtotime($r['paid_at'])) : '-' ?></td>
                    <td><span class="sa-badge sa-badge-<?= $r['status'] === 'approved' ? 'pro' : ($r['status'] === 'rejected' ? 'free' : 'platinum') ?>"><?= $statusLabels[$r['status']] ?? $r['status'] ?></span></td>
                    <?php if ($filter === 'paid'): ?>
                    <td>
                        <div class="sa-payment-actions">
                            <button type="button" class="sa-btn sa-btn-primary sa-btn-sm btn-approve" data-id="<?= (int)$r['id'] ?>">Aprobar</button>
                            <button type="button" class="sa-btn sa-btn-secondary sa-btn-sm btn-reject" data-id="<?= (int)$r['id'] ?>">Rechazar</button>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="sa-info-box" style="margin-top:1.5rem;">
    <h3>¿Cómo funciona?</h3>
    <ul>
        <li>Cuando un cliente paga con <strong>MercadoPago</strong> o envía un <strong>comprobante de transferencia</strong>, la solicitud aparece como "Pagado".</li>
        <li>Para transferencias, podés ver el comprobante haciendo clic en "Ver comprobante".</li>
        <li>Revisá que el pago haya llegado a tu cuenta (Alias: wemasha) y luego hacé clic en <strong>Aprobar</strong>.</li>
        <li>Al aprobar, se activa el plan en la tienda por la duración indicada (1 mes, 6 meses, 1 año o 3 años).</li>
        <li>Si la tienda ya tenía suscripción activa, el nuevo período se suma a partir de la fecha de vencimiento actual.</li>
        <li><strong>Importante:</strong> Al aprobar o rechazar, el comprobante se elimina automáticamente del servidor para ahorrar espacio. Descargalo antes si lo necesitás.</li>
    </ul>
</div>

<style>
.sa-filter-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.sa-filter-tab { padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; color: var(--sa-muted); font-size: 0.9rem; background: #f1f5f9; }
.sa-filter-tab:hover { background: #e2e8f0; color: #334155; }
.sa-filter-tab.active { background: var(--sa-primary, #6366f1); color: #fff; }
.sa-payment-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.sa-proof-actions { display: flex; gap: 0.5rem; margin-top: 0.25rem; }
.sa-proof-link { font-size: 0.78rem; color: var(--sa-primary, #6366f1); text-decoration: underline; }
.sa-proof-download { color: #059669; }
.sa-info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; }
.sa-info-box h3 { font-size: 1rem; margin-bottom: 0.75rem; }
.sa-info-box ul { margin: 0; padding-left: 1.25rem; font-size: 0.9rem; color: #475569; line-height: 1.7; }
</style>

<script>
(function(){
    var baseUrl = <?= json_encode(PLATFORM_PAGES_URL) ?>;
    var apiUrl = baseUrl + '/superadmin/api/approve-payment.php';

    document.querySelectorAll('.btn-approve').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = parseInt(this.dataset.id, 10);
            if (!id) return;
            if (!confirm('¿Aprobar esta solicitud y activar el plan?\n\nEl comprobante de transferencia se eliminará del servidor. Descargalo antes si lo necesitás.')) return;
            doAction(id, 'approve', btn);
        });
    });
    document.querySelectorAll('.btn-reject').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = parseInt(this.dataset.id, 10);
            if (!id) return;
            var reason = prompt('Motivo del rechazo (opcional):');
            if (reason === null) return; // cancel
            doAction(id, 'reject', btn, reason);
        });
    });

    function doAction(id, action, btn, notes){
        var row = btn.closest('tr');
        var actionsCell = row.querySelector('.sa-payment-actions');
        if (actionsCell) actionsCell.innerHTML = '<span style="color:var(--sa-muted);">Procesando...</span>';
        fetch(apiUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ payment_request_id: id, action: action, notes: notes || '' })
        }).then(function(r){ return r.json(); }).then(function(data){
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Error al procesar');
                if (actionsCell) actionsCell.innerHTML = '<button type="button" class="sa-btn sa-btn-primary sa-btn-sm btn-approve" data-id="' + id + '">Aprobar</button> <button type="button" class="sa-btn sa-btn-secondary sa-btn-sm btn-reject" data-id="' + id + '">Rechazar</button>';
            }
        }).catch(function(){
            alert('Error de conexión');
            if (actionsCell) actionsCell.innerHTML = '<button type="button" class="sa-btn sa-btn-primary sa-btn-sm btn-approve" data-id="' + id + '">Aprobar</button> <button type="button" class="sa-btn sa-btn-secondary sa-btn-sm btn-reject" data-id="' + id + '">Rechazar</button>';
        });
    }
})();
</script>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
