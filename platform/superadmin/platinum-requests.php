<?php
/**
 * Super Admin - Solicitudes de plan Platinum
 */
$pageTitle = 'Solicitudes Platinum';
require_once __DIR__ . '/../../platform-config.php';

require_once __DIR__ . '/../../helpers/superadmin.php';
require_once __DIR__ . '/../../helpers/plans.php';
requireSuperAdmin();

$filter = $_GET['filter'] ?? 'pending';
$validFilters = ['pending', 'contacted', 'approved', 'rejected', 'all'];
if (!in_array($filter, $validFilters)) {
    $filter = 'pending';
}

$where = $filter === 'all' ? '1=1' : "pt.status = :status";
$params = $filter === 'all' ? [] : ['status' => $filter];

$requests = platformFetchAll(
    "SELECT pt.*, s.slug as store_slug, s.plan as store_current_plan,
            pu.name as owner_name, pu.email as owner_email
     FROM platinum_requests pt
     JOIN stores s ON s.id = pt.store_id
     LEFT JOIN platform_users pu ON pu.id = s.owner_id
     WHERE $where
     ORDER BY pt.created_at DESC",
    $params
);

$statusLabels = [
    'pending'   => 'Pendiente',
    'contacted' => 'Contactado',
    'approved'  => 'Aprobado',
    'rejected'  => 'Rechazado',
];

$statusBadgeClass = [
    'pending'   => 'sa-badge-platinum',
    'contacted' => 'sa-badge-setup',
    'approved'  => 'sa-badge-pro',
    'rejected'  => 'sa-badge-free',
];

$platCheck = isPlatinumAvailable(0);

require_once __DIR__ . '/_inc/header.php';
?>

<div class="sa-page-header">
    <h1>Solicitudes Platinum</h1>
    <p>Solicitudes de tiendas que quieren el plan Platinum. Cupos: <strong><?= $platCheck['current'] ?>/<?= $platCheck['max'] ?></strong></p>
</div>

<div class="sa-filter-tabs" style="margin-bottom:1.5rem;">
    <a href="?filter=pending" class="sa-filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pendientes</a>
    <a href="?filter=contacted" class="sa-filter-tab <?= $filter === 'contacted' ? 'active' : '' ?>">Contactados</a>
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
                    <th>Plan actual</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Tienda solicitada</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Notas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="10" style="text-align:center; padding:3rem; color:var(--sa-muted);">
                        No hay solicitudes <?= $filter === 'all' ? '' : 'en este estado' ?>.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($requests as $r): ?>
                <tr data-id="<?= (int)$r['id'] ?>">
                    <td style="color:var(--sa-muted);">#<?= (int)$r['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['store_slug']) ?></strong>
                        <div style="font-size:0.78rem; color:var(--sa-muted);"><?= htmlspecialchars($r['owner_name'] ?? $r['owner_email'] ?? '-') ?></div>
                    </td>
                    <td><span class="sa-badge sa-badge-<?= htmlspecialchars($r['store_current_plan']) ?>"><?= ucfirst(htmlspecialchars($r['store_current_plan'])) ?></span></td>
                    <td><strong><?= htmlspecialchars($r['contact_name']) ?></strong></td>
                    <td>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $r['phone']) ?>" target="_blank" class="sa-whatsapp-link">
                            <?= htmlspecialchars($r['phone']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($r['shop_name']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td><span class="sa-badge <?= $statusBadgeClass[$r['status']] ?? '' ?>"><?= $statusLabels[$r['status']] ?? $r['status'] ?></span></td>
                    <td>
                        <?php if ($r['admin_notes']): ?>
                        <span style="font-size:0.8rem; color:var(--sa-muted);"><?= htmlspecialchars($r['admin_notes']) ?></span>
                        <?php else: ?>
                        <span style="font-size:0.8rem; color:var(--sa-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="sa-pt-actions">
                            <?php if ($r['status'] === 'pending'): ?>
                            <button type="button" class="sa-btn sa-btn-sm btn-pt-contacted" data-id="<?= (int)$r['id'] ?>" style="background:#0ea5e9; color:#fff;">Contactado</button>
                            <button type="button" class="sa-btn sa-btn-primary sa-btn-sm btn-pt-approve" data-id="<?= (int)$r['id'] ?>">Aprobar</button>
                            <button type="button" class="sa-btn sa-btn-secondary sa-btn-sm btn-pt-reject" data-id="<?= (int)$r['id'] ?>">Rechazar</button>
                            <?php elseif ($r['status'] === 'contacted'): ?>
                            <button type="button" class="sa-btn sa-btn-primary sa-btn-sm btn-pt-approve" data-id="<?= (int)$r['id'] ?>">Aprobar</button>
                            <button type="button" class="sa-btn sa-btn-secondary sa-btn-sm btn-pt-reject" data-id="<?= (int)$r['id'] ?>">Rechazar</button>
                            <?php else: ?>
                            <span style="font-size:0.8rem; color:var(--sa-muted);">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="sa-info-box" style="margin-top:1.5rem;">
    <h3>Flujo de solicitudes Platinum</h3>
    <ul>
        <li><strong>Pendiente:</strong> La tienda envió la solicitud. Revisá los datos y contactá por WhatsApp.</li>
        <li><strong>Contactado:</strong> Ya te comunicaste con el cliente. Marcalo cuando hayas hablado.</li>
        <li><strong>Aprobar:</strong> Se activará el plan Platinum en la tienda (12 meses). Solo hay <?= $platCheck['max'] ?> cupos disponibles.</li>
        <li><strong>Rechazar:</strong> Se rechaza la solicitud. Podés agregar un motivo.</li>
    </ul>
</div>

<style>
.sa-filter-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.sa-filter-tab { padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; color: var(--sa-muted); font-size: 0.9rem; background: #f1f5f9; }
.sa-filter-tab:hover { background: #e2e8f0; color: #334155; }
.sa-filter-tab.active { background: var(--sa-primary, #6366f1); color: #fff; }
.sa-pt-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.sa-whatsapp-link { color: #25d366; font-weight: 600; text-decoration: none; }
.sa-whatsapp-link:hover { text-decoration: underline; }
.sa-info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; }
.sa-info-box h3 { font-size: 1rem; margin-bottom: 0.75rem; }
.sa-info-box ul { margin: 0; padding-left: 1.25rem; font-size: 0.9rem; color: #475569; line-height: 1.7; }
</style>

<script>
(function(){
    var baseUrl = <?= json_encode(PLATFORM_PAGES_URL) ?>;
    var apiUrl = baseUrl + '/superadmin/api/platinum-request-action.php';

    function doAction(id, action, btn, notes) {
        var row = btn.closest('tr');
        var actionsCell = row.querySelector('.sa-pt-actions');
        if (actionsCell) actionsCell.innerHTML = '<span style="color:var(--sa-muted);">Procesando...</span>';

        fetch(apiUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ request_id: id, action: action, notes: notes || '' })
        }).then(function(r){ return r.json(); }).then(function(data){
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Error al procesar');
                window.location.reload();
            }
        }).catch(function(){
            alert('Error de conexión');
            window.location.reload();
        });
    }

    document.querySelectorAll('.btn-pt-contacted').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = parseInt(this.dataset.id, 10);
            if (!confirm('¿Marcar como contactado?')) return;
            doAction(id, 'contacted', btn);
        });
    });

    document.querySelectorAll('.btn-pt-approve').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = parseInt(this.dataset.id, 10);
            if (!confirm('¿Aprobar solicitud y activar plan Platinum (12 meses) en esta tienda?')) return;
            doAction(id, 'approve', btn);
        });
    });

    document.querySelectorAll('.btn-pt-reject').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = parseInt(this.dataset.id, 10);
            var reason = prompt('Motivo del rechazo (opcional):');
            if (reason === null) return;
            doAction(id, 'reject', btn, reason);
        });
    });
})();
</script>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
