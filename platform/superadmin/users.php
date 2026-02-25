<?php
/**
 * Super Admin - Lista de usuarios de la plataforma
 */
$pageTitle = 'Usuarios';
require_once __DIR__ . '/../../platform-config.php';

$users = platformFetchAll(
    'SELECT pu.*,
            (SELECT COUNT(*) FROM store_members sm WHERE sm.user_id = pu.id) as stores_count
     FROM platform_users pu
     ORDER BY pu.created_at DESC'
);

require_once __DIR__ . '/_inc/header.php';
?>

<div class="sa-page-header">
    <h1>Usuarios de la plataforma</h1>
    <p><?= count($users) ?> usuario(s) registrado(s)</p>
</div>

<div class="sa-table-wrap sa-has-mobile-cards">
    <div class="sa-table-header">
        <h2>Todos los usuarios</h2>
        <input type="search" id="saSearchUsers" class="sa-search-input" placeholder="Buscar por nombre o email..." autocomplete="off">
    </div>
    <div class="sa-table-scroll">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Tiendas</th>
                    <th>Verificado</th>
                    <th>Registrado</th>
                    <th>Último login</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr data-search=""><td colspan="8" style="text-align:center; padding:3rem; color:var(--sa-muted);">No hay usuarios aún</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): 
                    $searchText = strtolower(($u['name'] ?? '') . ' ' . ($u['email'] ?? '') . ' ' . ($u['phone'] ?? ''));
                ?>
                <tr data-search="<?= htmlspecialchars($searchText) ?>">
                    <td style="color:var(--sa-muted);">#<?= (int)$u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['name'] ?? '-') ?></strong></td>
                    <td style="color:var(--sa-muted);"><?= htmlspecialchars($u['email']) ?></td>
                    <td style="font-size:0.85rem;"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                    <td style="text-align:center; font-weight:600;"><?= (int)$u['stores_count'] ?></td>
                    <td style="text-align:center;">
                        <?php if ($u['email_verified']): ?>
                            <span class="sa-badge sa-badge-active">Sí</span>
                        <?php else: ?>
                            <span class="sa-badge sa-badge-setup">No</span>
                        <?php endif; ?>
                    </td>
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
        <?php if (empty($users)): ?>
            <p class="sa-mobile-cards-empty">No hay usuarios aún</p>
        <?php else: ?>
            <?php foreach ($users as $u): 
                $searchText = strtolower(($u['name'] ?? '') . ' ' . ($u['email'] ?? '') . ' ' . ($u['phone'] ?? ''));
            ?>
            <div class="sa-store-card sa-user-card" data-search="<?= htmlspecialchars($searchText) ?>">
                <div class="sa-user-card-header">
                    <span class="sa-user-card-id">#<?= (int)$u['id'] ?></span>
                    <strong class="sa-user-card-name"><?= htmlspecialchars($u['name'] ?? '-') ?></strong>
                    <span class="sa-user-card-stores"><?= (int)$u['stores_count'] ?> tienda<?= (int)$u['stores_count'] !== 1 ? 's' : '' ?></span>
                    <button type="button" class="sa-user-card-toggle" aria-expanded="false">
                        <span class="sa-user-card-toggle-text">Ver detalles</span>
                        <span class="sa-user-card-toggle-icon">▼</span>
                    </button>
                </div>
                <div class="sa-user-card-details" hidden>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Email</span><span><?= htmlspecialchars($u['email']) ?></span></div>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Teléfono</span><span><?= htmlspecialchars($u['phone'] ?? '-') ?></span></div>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Verificado</span><span class="sa-badge sa-badge-<?= $u['email_verified'] ? 'active' : 'setup' ?>"><?= $u['email_verified'] ? 'Sí' : 'No' ?></span></div>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Registrado</span><span><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></span></div>
                    <div class="sa-user-card-detail-row"><span class="sa-detail-label">Último login</span><span><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Nunca' ?></span></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<p class="sa-search-no-results" id="saSearchNoResults" style="display:none; padding:1.5rem; text-align:center; color:var(--sa-muted);">No se encontraron resultados</p>

<script>
(function(){
    var inp = document.getElementById('saSearchUsers');
    var noResults = document.getElementById('saSearchNoResults');
    if (!inp) return;
    var rows = document.querySelectorAll('.sa-table-wrap tbody tr[data-search]');
    var cards = document.querySelectorAll('.sa-mobile-cards .sa-user-card[data-search]');
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
        noResults.style.display = (q && !anyVisible) ? 'block' : 'none';
    }
    inp.addEventListener('input', filter);
    inp.addEventListener('search', filter);
})();

document.querySelectorAll('.sa-user-card-toggle').forEach(function(btn){
    btn.addEventListener('click', function(){
        var details = this.closest('.sa-user-card').querySelector('.sa-user-card-details');
        var text = this.querySelector('.sa-user-card-toggle-text');
        var icon = this.querySelector('.sa-user-card-toggle-icon');
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
