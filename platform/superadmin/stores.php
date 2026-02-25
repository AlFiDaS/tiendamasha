<?php
/**
 * Super Admin - Lista completa de tiendas con estadísticas
 */
$pageTitle = 'Tiendas';
require_once __DIR__ . '/../../platform-config.php';

$allStores = getAllStoresWithOwner();

$storesWithStats = [];
foreach ($allStores as $store) {
    $store['stats'] = getStoreStats($store['db_name'], $store['id']);
    $storesWithStats[] = $store;
}

require_once __DIR__ . '/_inc/header.php';
?>

<div class="sa-page-header">
    <h1>Todas las tiendas</h1>
    <p><?= count($storesWithStats) ?> tienda(s) registrada(s) en la plataforma</p>
</div>

<div class="sa-table-wrap sa-has-mobile-cards">
    <div class="sa-table-header">
        <h2>Listado de tiendas</h2>
        <input type="search" id="saSearchStores" class="sa-search-input" placeholder="Buscar por nombre o owner..." autocomplete="off">
    </div>
    <div class="sa-table-scroll">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tienda</th>
                    <th>Owner</th>
                    <th>Plan</th>
                    <th>Estado</th>
                    <th>Productos</th>
                    <th>Ventas</th>
                    <th>Ingresos</th>
                    <th>Creada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($storesWithStats)): ?>
                    <tr data-search=""><td colspan="10" style="text-align:center; padding:3rem; color:var(--sa-muted);">
                        No hay tiendas creadas aún
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($storesWithStats as $store): $s = $store['stats']; 
                    $searchText = strtolower(($store['slug'] ?? '') . ' ' . ($store['owner_name'] ?? '') . ' ' . ($store['owner_email'] ?? '') . ' ' . ($s['shop_name'] ?? ''));
                ?>
                <tr data-search="<?= htmlspecialchars($searchText) ?>">
                    <td style="color:var(--sa-muted);">#<?= (int)$store['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($store['slug']) ?></strong>
                        <?php if ($s['shop_name'] ?? null): ?>
                            <div style="font-size:0.8rem; color:var(--sa-muted);"><?= htmlspecialchars($s['shop_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($store['owner_name'] ?? '-') ?></div>
                        <div style="font-size:0.78rem; color:var(--sa-muted);"><?= htmlspecialchars($store['owner_email'] ?? '') ?></div>
                    </td>
                    <td><span class="sa-badge sa-badge-<?= $store['plan'] ?>"><?= strtoupper($store['plan']) ?></span></td>
                    <td><span class="sa-badge sa-badge-<?= $store['status'] ?>"><?= ucfirst($store['status']) ?></span></td>
                    <td style="text-align:center; font-weight:600;">
                        <?= $s['db_error'] ? '<span style="color:var(--sa-danger)">?</span>' : $s['products_count'] ?>
                    </td>
                    <td style="text-align:center; font-weight:600;">
                        <?= $s['db_error'] ? '<span style="color:var(--sa-danger)">?</span>' : $s['orders_count'] ?>
                    </td>
                    <td style="font-weight:600;">
                        <?php if ($s['db_error']): ?>
                            <span style="color:var(--sa-danger)">?</span>
                        <?php else: ?>
                            $<?= number_format($s['revenue'], 0, ',', '.') ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.85rem; color:var(--sa-muted);">
                        <?= date('d/m/Y', strtotime($store['created_at'])) ?>
                    </td>
                    <td>
                        <div class="sa-table-actions">
                            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/store-detail.php?id=<?= (int)$store['id'] ?>" class="sa-btn sa-btn-primary sa-btn-sm">Detalle</a>
                            <?php if ($store['status'] === 'active'): ?>
                                <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/impersonate-store.php?store_id=<?= (int)$store['id'] ?>" class="sa-btn sa-btn-secondary sa-btn-sm" target="_blank">Admin</a>
                            <?php endif; ?>
                            <button type="button" class="sa-btn sa-btn-sm" style="background:#dc2626; color:#fff;" onclick="openDeleteModal(<?= (int)$store['id'] ?>, '<?= htmlspecialchars($store['slug'], ENT_QUOTES) ?>')">Eliminar</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="sa-mobile-cards">
        <?php if (empty($storesWithStats)): ?>
            <p class="sa-mobile-cards-empty">No hay tiendas creadas aún</p>
        <?php else: ?>
            <?php foreach ($storesWithStats as $store): $s = $store['stats']; 
                $searchText = strtolower(($store['slug'] ?? '') . ' ' . ($store['owner_name'] ?? '') . ' ' . ($store['owner_email'] ?? '') . ' ' . ($s['shop_name'] ?? ''));
            ?>
            <div class="sa-store-card sa-store-card-full" data-search="<?= htmlspecialchars($searchText) ?>">
                <div class="sa-store-card-header">
                    <span class="sa-store-card-id">#<?= (int)$store['id'] ?></span>
                    <strong class="sa-store-card-name"><?= htmlspecialchars($store['slug']) ?></strong>
                    <span class="sa-store-card-stats-short">
                        <?= $s['db_error'] ? '?' : $s['products_count'] ?> prod · <?= $s['db_error'] ? '?' : $s['orders_count'] ?> ventas
                    </span>
                    <button type="button" class="sa-store-card-toggle" aria-expanded="false">
                        <span class="sa-store-card-toggle-text">Ver detalles</span>
                        <span class="sa-store-card-toggle-icon">▼</span>
                    </button>
                </div>
                <div class="sa-store-card-details" hidden>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Owner</span><span><?= htmlspecialchars($store['owner_name'] ?? '-') ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Email</span><span><?= htmlspecialchars($store['owner_email'] ?? '-') ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Plan</span><span class="sa-badge sa-badge-<?= $store['plan'] ?>"><?= strtoupper($store['plan']) ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Estado</span><span class="sa-badge sa-badge-<?= $store['status'] ?>"><?= ucfirst($store['status']) ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Productos</span><span><?= $s['db_error'] ? '?' : $s['products_count'] ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Ventas</span><span><?= $s['db_error'] ? '?' : $s['orders_count'] ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Ingresos</span><span><?= $s['db_error'] ? '?' : '$' . number_format($s['revenue'], 0, ',', '.') ?></span></div>
                    <div class="sa-store-card-detail-row"><span class="sa-detail-label">Creada</span><span><?= date('d/m/Y', strtotime($store['created_at'])) ?></span></div>
                    <div class="sa-store-card-actions">
                        <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/store-detail.php?id=<?= (int)$store['id'] ?>" class="sa-btn sa-btn-primary sa-btn-sm">Detalle</a>
                        <?php if ($store['status'] === 'active'): ?>
                            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/impersonate-store.php?store_id=<?= (int)$store['id'] ?>" class="sa-btn sa-btn-secondary sa-btn-sm" target="_blank">Admin</a>
                        <?php endif; ?>
                        <button type="button" class="sa-btn sa-btn-sm" style="background:#dc2626; color:#fff;" onclick="openDeleteModal(<?= (int)$store['id'] ?>, '<?= htmlspecialchars($store['slug'], ENT_QUOTES) ?>')">Eliminar</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<p class="sa-search-no-results" id="saSearchNoResultsStores" style="display:none; padding:1.5rem; text-align:center; color:var(--sa-muted);">No se encontraron resultados</p>

<!-- Modal de confirmación para eliminar -->
<div id="deleteModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.5); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:440px; width:100%; box-shadow:0 25px 50px rgba(0,0,0,.25);">
        <h3 style="margin:0 0 0.5rem; color:#dc2626;">Eliminar tienda</h3>
        <p style="color:#666; font-size:0.9rem; margin:0 0 1.25rem;">
            Para confirmar, escribí el nombre de la tienda: <strong id="deleteSlugLabel" style="color:#111;"></strong>
        </p>
        <input type="text" id="deleteConfirmInput" autocomplete="off" placeholder="Nombre de la tienda"
               style="width:100%; padding:0.65rem 0.85rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem; box-sizing:border-box; margin-bottom:1rem;">
        <p id="deleteError" style="display:none; color:#dc2626; font-size:0.85rem; margin:0 0 0.75rem;"></p>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
            <button type="button" class="sa-btn sa-btn-secondary" onclick="document.getElementById('deleteModal').style.display='none'">Cancelar</button>
            <button type="button" id="deleteConfirmBtn" class="sa-btn" style="background:#dc2626; color:#fff; opacity:.5; cursor:not-allowed;" disabled>Eliminar</button>
        </div>
    </div>
</div>

<script>
var _delId = 0, _delSlug = '';
function openDeleteModal(id, slug) {
    _delId = id; _delSlug = slug;
    document.getElementById('deleteSlugLabel').textContent = slug;
    document.getElementById('deleteConfirmInput').value = '';
    document.getElementById('deleteError').style.display = 'none';
    var btn = document.getElementById('deleteConfirmBtn');
    btn.disabled = true; btn.style.opacity = '.5'; btn.style.cursor = 'not-allowed'; btn.textContent = 'Eliminar';
    document.getElementById('deleteModal').style.display = 'flex';
}
(function(){
    var inp = document.getElementById('deleteConfirmInput');
    var btn = document.getElementById('deleteConfirmBtn');
    var errP = document.getElementById('deleteError');

    inp.addEventListener('input', function(){
        var ok = inp.value.trim() === _delSlug;
        btn.disabled = !ok;
        btn.style.opacity = ok ? '1' : '.5';
        btn.style.cursor = ok ? 'pointer' : 'not-allowed';
        errP.style.display = 'none';
    });

    btn.addEventListener('click', function(){
        btn.disabled = true;
        btn.textContent = 'Eliminando…';
        fetch('<?= PLATFORM_PAGES_URL ?>/superadmin/api/delete-store.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({store_id: _delId, confirm_slug: inp.value.trim()})
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.success) {
                window.location.reload();
            } else {
                errP.textContent = data.error || 'Error al eliminar';
                errP.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Eliminar';
            }
        })
        .catch(function(){
            errP.textContent = 'Error de conexión';
            errP.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Eliminar';
        });
    });
})();

(function(){
    var inp = document.getElementById('saSearchStores');
    var noResults = document.getElementById('saSearchNoResultsStores');
    if (!inp) return;
    var rows = document.querySelectorAll('.sa-table-wrap tbody tr[data-search]');
    var cards = document.querySelectorAll('.sa-mobile-cards .sa-store-card-full[data-search]');
    function filter() {
        var q = inp.value.trim().toLowerCase();
        var anyVisible = false;
        rows.forEach(function(r){
            var show = !q || (r.getAttribute('data-search') || '').indexOf(q) !== -1;
            r.style.display = show ? '' : 'none';
            if (show) anyVisible = true;
        });
        cards.forEach(function(c){
            var show = !q || (c.getAttribute('data-search') || '').indexOf(q) !== -1;
            c.style.display = show ? '' : 'none';
            if (show) anyVisible = true;
        });
        if (noResults) noResults.style.display = (q && !anyVisible) ? 'block' : 'none';
    }
    inp.addEventListener('input', filter);
    inp.addEventListener('search', filter);
})();

document.querySelectorAll('.sa-store-card-toggle').forEach(function(btn){
    btn.addEventListener('click', function(){
        var details = this.closest('.sa-store-card-full').querySelector('.sa-store-card-details');
        var text = this.querySelector('.sa-store-card-toggle-text');
        var icon = this.querySelector('.sa-store-card-toggle-icon');
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
