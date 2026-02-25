<?php
/**
 * Super Admin Dashboard - Estadísticas globales de la plataforma
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../platform-config.php';

$stats = getPlatformGlobalStats();
$recentUsers = getRecentPlatformUsers(5);
$recentStores = platformFetchAll(
    "SELECT s.*, pu.name as owner_name FROM stores s
     LEFT JOIN platform_users pu ON pu.id = s.owner_id
     ORDER BY s.created_at DESC LIMIT 5"
);

require_once __DIR__ . '/_inc/header.php';
?>

<div class="sa-page-header">
    <h1>Dashboard</h1>
    <p>Vista global de la plataforma Somos Tiendi</p>
</div>

<!-- Stats principales -->
<div class="sa-stats">
    <div class="sa-stat-card">
        <div class="sa-stat-icon blue"><?= icon('users', 24) ?></div>
        <div class="sa-stat-info">
            <h3><?= number_format($stats['total_users']) ?></h3>
            <p>Usuarios registrados</p>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon green"><?= icon('store', 24) ?></div>
        <div class="sa-stat-info">
            <h3><?= number_format($stats['total_stores']) ?></h3>
            <p>Tiendas creadas (<?= $stats['active_stores'] ?> activas)</p>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon purple"><?= icon('package', 24) ?></div>
        <div class="sa-stat-info">
            <h3><?= number_format($stats['total_products']) ?></h3>
            <p>Productos en total</p>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon amber"><?= icon('dollar', 24) ?></div>
        <div class="sa-stat-info">
            <h3>$<?= number_format($stats['total_revenue'], 0, ',', '.') ?></h3>
            <p><?= number_format($stats['total_orders']) ?> órdenes aprobadas</p>
        </div>
    </div>
</div>

<!-- Distribución de planes -->
<div class="sa-stats" style="grid-template-columns: repeat(3, 1fr);">
    <div class="sa-stat-card">
        <div class="sa-stat-icon" style="background:#f1f5f9; color:#64748b;"><?= icon('tag', 24) ?></div>
        <div class="sa-stat-info">
            <h3><?= number_format($stats['plans']['free'] ?? 0) ?></h3>
            <p>Plan Free</p>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon" style="background:#dbeafe; color:#2563eb;"><?= icon('star', 24) ?></div>
        <div class="sa-stat-info">
            <h3><?= number_format($stats['plans']['basic'] ?? 0) ?></h3>
            <p>Plan Basic</p>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon" style="background:#f3e8ff; color:#7c3aed;"><?= icon('zap', 24) ?></div>
        <div class="sa-stat-info">
            <h3><?= number_format($stats['plans']['pro'] ?? 0) ?></h3>
            <p>Plan Pro</p>
        </div>
    </div>
</div>

<!-- Últimas tiendas -->
<div class="sa-table-wrap sa-has-mobile-cards">
    <div class="sa-table-header">
        <h2>Últimas tiendas creadas</h2>
        <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/stores.php" class="sa-btn sa-btn-secondary sa-btn-sm">Ver todas</a>
    </div>
    <div class="sa-table-scroll">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>Tienda</th>
                    <th>Owner</th>
                    <th>Plan</th>
                    <th>Estado</th>
                    <th>Creada</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentStores)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--sa-muted);">No hay tiendas aún</td></tr>
                <?php endif; ?>
                <?php foreach ($recentStores as $store): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($store['slug']) ?></strong>
                        <div style="font-size:0.8rem; color:var(--sa-muted);"><?= htmlspecialchars($store['db_name']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($store['owner_name'] ?? '-') ?></td>
                    <td><span class="sa-badge sa-badge-<?= $store['plan'] ?>"><?= strtoupper($store['plan']) ?></span></td>
                    <td><span class="sa-badge sa-badge-<?= $store['status'] ?>"><?= ucfirst($store['status']) ?></span></td>
                    <td style="font-size:0.85rem; color:var(--sa-muted);"><?= date('d/m/Y', strtotime($store['created_at'])) ?></td>
                    <td>
                        <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/store-detail.php?id=<?= (int)$store['id'] ?>" class="sa-btn sa-btn-secondary sa-btn-sm">Ver</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="sa-mobile-cards">
        <?php if (empty($recentStores)): ?>
            <p class="sa-mobile-cards-empty">No hay tiendas aún</p>
        <?php else: ?>
            <?php foreach ($recentStores as $store): ?>
            <div class="sa-store-card sa-store-card-full">
                <div class="sa-store-card-header">
                    <span class="sa-store-card-id">#<?= (int)$store['id'] ?></span>
                    <strong class="sa-store-card-name"><?= htmlspecialchars($store['slug']) ?></strong>
                    <span class="sa-store-card-stats-short"><?= htmlspecialchars($store['owner_name'] ?? '-') ?></span>
                    <button type="button" class="sa-store-card-toggle" aria-expanded="false">
                        <span class="sa-store-card-toggle-text">Ver detalles</span>
                        <span class="sa-store-card-toggle-icon">▼</span>
                    </button>
                </div>
                <div class="sa-store-card-details" hidden>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Owner</span><span><?= htmlspecialchars($store['owner_name'] ?? '-') ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Plan</span><span class="sa-badge sa-badge-<?= $store['plan'] ?>"><?= strtoupper($store['plan']) ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Estado</span><span class="sa-badge sa-badge-<?= $store['status'] ?>"><?= ucfirst($store['status']) ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Creada</span><span><?= date('d/m/Y', strtotime($store['created_at'])) ?></span></div>
                    <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/store-detail.php?id=<?= (int)$store['id'] ?>" class="sa-btn sa-btn-primary sa-btn-sm" style="margin-top:0.75rem; display:inline-flex;">Ver detalle</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Últimos usuarios -->
<div class="sa-table-wrap sa-has-mobile-cards">
    <div class="sa-table-header">
        <h2>Últimos usuarios registrados</h2>
        <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/users.php" class="sa-btn sa-btn-secondary sa-btn-sm">Ver todos</a>
    </div>
    <div class="sa-table-scroll">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Registrado</th>
                    <th>Último login</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentUsers)): ?>
                    <tr><td colspan="4" style="text-align:center; padding:2rem; color:var(--sa-muted);">No hay usuarios aún</td></tr>
                <?php endif; ?>
                <?php foreach ($recentUsers as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['name'] ?? '-') ?></strong></td>
                    <td style="color:var(--sa-muted);"><?= htmlspecialchars($u['email']) ?></td>
                    <td style="font-size:0.85rem;"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                    <td style="font-size:0.85rem; color:var(--sa-muted);">
                        <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Nunca' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="sa-mobile-cards">
        <?php if (empty($recentUsers)): ?>
            <p class="sa-mobile-cards-empty">No hay usuarios aún</p>
        <?php else: ?>
            <?php foreach ($recentUsers as $u): ?>
            <div class="sa-store-card sa-user-card">
                <div class="sa-user-card-header">
                    <span class="sa-user-card-id">#<?= (int)$u['id'] ?></span>
                    <strong class="sa-user-card-name"><?= htmlspecialchars($u['name'] ?? '-') ?></strong>
                    <span class="sa-user-card-stores"><?= (int)($u['stores_count'] ?? 0) ?> tienda<?= (int)($u['stores_count'] ?? 0) !== 1 ? 's' : '' ?></span>
                    <button type="button" class="sa-user-card-toggle" aria-expanded="false">
                        <span class="sa-user-card-toggle-text">Ver detalles</span>
                        <span class="sa-user-card-toggle-icon">▼</span>
                    </button>
                </div>
                <div class="sa-user-card-details" hidden>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Email</span><span><?= htmlspecialchars($u['email']) ?></span></div>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Teléfono</span><span><?= htmlspecialchars($u['phone'] ?? '-') ?></span></div>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Verificado</span><span class="sa-badge sa-badge-<?= !empty($u['email_verified']) ? 'active' : 'setup' ?>"><?= !empty($u['email_verified']) ? 'Sí' : 'No' ?></span></div>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Registrado</span><span><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></span></div>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Último login</span><span><?= !empty($u['last_login']) ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Nunca' ?></span></div>
                    <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/users.php" class="sa-btn sa-btn-primary sa-btn-sm" style="margin-top:0.75rem; display:inline-flex;">Ver usuarios</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.sa-user-card-toggle, .sa-store-card-toggle').forEach(function(btn){
    if (!btn.closest('.sa-mobile-cards')) return;
    btn.addEventListener('click', function(){
        var card = this.closest('.sa-store-card, .sa-user-card');
        var details = card.querySelector('.sa-user-card-details, .sa-store-card-details');
        var text = this.querySelector('.sa-user-card-toggle-text, .sa-store-card-toggle-text');
        var icon = this.querySelector('.sa-user-card-toggle-icon, .sa-store-card-toggle-icon');
        var isOpen = this.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            details.hidden = true;
            this.setAttribute('aria-expanded', 'false');
            text.textContent = 'Ver detalles';
            icon.textContent = '▼';
        } else {
            details.hidden = false;
            this.setAttribute('aria-expanded', 'true');
            text.textContent = 'Esconder detalles';
            icon.textContent = '▲';
        }
    });
});
</script>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
