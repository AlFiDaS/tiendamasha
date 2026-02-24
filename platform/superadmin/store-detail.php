<?php
/**
 * Super Admin - Detalle de una tienda
 */
require_once __DIR__ . '/../../platform-config.php';

$storeId = (int) ($_GET['id'] ?? 0);
if (!$storeId) {
    header('Location: ' . PLATFORM_PAGES_URL . '/superadmin/stores.php');
    exit;
}

$store = getStoreDetail($storeId);
if (!$store) {
    header('Location: ' . PLATFORM_PAGES_URL . '/superadmin/stores.php');
    exit;
}

$pageTitle = 'Tienda: ' . $store['slug'];
$s = $store['stats'];
$domain = !empty($store['custom_domain']) ? $store['custom_domain'] : 'www.somostiendi.com/' . $store['slug'];

$statusLabels = [
    'active' => 'Activa', 'setup' => 'En configuración', 'suspended' => 'Suspendida',
];
$planLabels = [
    'free' => 'Free', 'basic' => 'Basic', 'pro' => 'Pro',
];

require_once __DIR__ . '/_inc/header.php';

$impersonateError = $_GET['error'] ?? '';
$impersonateErrorMsg = [
    'no_admin' => 'Esta tienda no tiene usuarios admin configurados.',
    'token_failed' => 'No se pudo generar el token de acceso. Verificá que la carpeta logs/ exista y tenga permisos de escritura.',
][$impersonateError] ?? '';
?>

<?php if ($impersonateErrorMsg): ?>
<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:1rem; margin-bottom:1.5rem; color:#991b1b;">
    <?= htmlspecialchars($impersonateErrorMsg) ?>
</div>
<?php endif; ?>

<div class="sa-page-header">
    <div style="display:flex; align-items:center; gap:1rem;">
        <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/stores.php" class="sa-btn sa-btn-secondary sa-btn-sm">← Volver</a>
        <div>
            <h1><?= htmlspecialchars($s['shop_name'] ?: $store['slug']) ?></h1>
            <p style="margin:0;"><?= htmlspecialchars($domain) ?></p>
        </div>
    </div>
    <?php if ($store['status'] === 'active'): ?>
        <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/impersonate-store.php?store_id=<?= (int)$store['id'] ?>" class="sa-btn sa-btn-primary" target="_blank">Acceder al Admin</a>
    <?php endif; ?>
</div>

<!-- Stats de la tienda -->
<div class="sa-stats">
    <div class="sa-stat-card">
        <div class="sa-stat-icon blue">📦</div>
        <div class="sa-stat-info">
            <h3><?= $s['db_error'] ? '?' : number_format($s['products_count']) ?></h3>
            <p>Productos</p>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon green">🛒</div>
        <div class="sa-stat-info">
            <h3><?= $s['db_error'] ? '?' : number_format($s['orders_count']) ?></h3>
            <p>Órdenes</p>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon amber">💰</div>
        <div class="sa-stat-info">
            <h3><?= $s['db_error'] ? '?' : '$' . number_format($s['revenue'], 0, ',', '.') ?></h3>
            <p>Ingresos (aprobados)</p>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon purple">🏷️</div>
        <div class="sa-stat-info">
            <h3><?= $s['db_error'] ? '?' : number_format($s['categories_count']) ?></h3>
            <p>Categorías</p>
        </div>
    </div>
</div>

<!-- Detalle -->
<div class="sa-detail-grid">
    <!-- Info de la tienda -->
    <div class="sa-detail-card">
        <h3>Información de la tienda</h3>
        <div class="sa-detail-row">
            <span class="sa-detail-label">ID</span>
            <span class="sa-detail-value">#<?= (int)$store['id'] ?></span>
        </div>
        <div class="sa-detail-row">
            <span class="sa-detail-label">Slug</span>
            <span class="sa-detail-value"><?= htmlspecialchars($store['slug']) ?></span>
        </div>
        <div class="sa-detail-row">
            <span class="sa-detail-label">Base de datos</span>
            <span class="sa-detail-value" style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars($store['db_name']) ?></span>
        </div>
        <div class="sa-detail-row">
            <span class="sa-detail-label">Dominio</span>
            <span class="sa-detail-value"><?= htmlspecialchars($domain) ?></span>
        </div>
        <div class="sa-detail-row">
            <span class="sa-detail-label">Plan</span>
            <span class="sa-badge sa-badge-<?= $store['plan'] ?>"><?= strtoupper($store['plan']) ?></span>
        </div>
        <div class="sa-detail-row">
            <span class="sa-detail-label">Estado</span>
            <span class="sa-badge sa-badge-<?= $store['status'] ?>"><?= $statusLabels[$store['status']] ?? $store['status'] ?></span>
        </div>
        <div class="sa-detail-row">
            <span class="sa-detail-label">Creada</span>
            <span class="sa-detail-value"><?= date('d/m/Y H:i', strtotime($store['created_at'])) ?></span>
        </div>
    </div>

    <!-- Owner -->
    <div class="sa-detail-card">
        <h3>Propietario</h3>
        <div class="sa-detail-row">
            <span class="sa-detail-label">Nombre</span>
            <span class="sa-detail-value"><?= htmlspecialchars($store['owner_name'] ?? '-') ?></span>
        </div>
        <div class="sa-detail-row">
            <span class="sa-detail-label">Email</span>
            <span class="sa-detail-value"><?= htmlspecialchars($store['owner_email'] ?? '-') ?></span>
        </div>
        <div class="sa-detail-row">
            <span class="sa-detail-label">Teléfono</span>
            <span class="sa-detail-value"><?= htmlspecialchars($store['owner_phone'] ?? '-') ?></span>
        </div>

        <h3 style="margin-top:1.5rem;">Acceso al panel admin</h3>
        <?php if (!empty($store['admin_users'])): ?>
            <?php foreach ($store['admin_users'] as $i => $au): ?>
            <?php if ($i > 0): ?><hr style="margin:1rem 0; border:none; border-top:1px solid #e5e7eb;"><?php endif; ?>
            <div class="sa-detail-row">
                <span class="sa-detail-label">Usuario</span>
                <span class="sa-detail-value" style="font-family:monospace;"><?= htmlspecialchars($au['username']) ?></span>
            </div>
            <div class="sa-detail-row">
                <span class="sa-detail-label">Email</span>
                <span class="sa-detail-value"><?= htmlspecialchars($au['email'] ?? '-') ?></span>
            </div>
            <?php endforeach; ?>
            <p style="font-size:0.85rem; color:var(--sa-muted); margin:0.5rem 0 0.5rem;">Usá estos datos para iniciar sesión manualmente si el acceso directo no funciona. La contraseña no se puede mostrar (está encriptada); usá "¿Olvidaste tu contraseña?" en el login.</p>
            <a href="/<?= htmlspecialchars($store['slug']) ?>/admin/login.php" target="_blank" class="sa-btn sa-btn-secondary sa-btn-sm" style="margin-top:0.5rem;">Ir al login del admin</a>
        <?php else: ?>
            <p style="color:var(--sa-muted); font-size:0.9rem; padding:0.5rem 0;">Sin usuarios admin configurados</p>
        <?php endif; ?>

        <h3 style="margin-top:1.5rem;">Miembros del equipo</h3>
        <?php if (empty($store['members'])): ?>
            <p style="color:var(--sa-muted); font-size:0.9rem; padding:0.5rem 0;">Sin miembros registrados</p>
        <?php else: ?>
            <?php foreach ($store['members'] as $member): ?>
            <div class="sa-detail-row">
                <span>
                    <strong><?= htmlspecialchars($member['name'] ?? 'Sin nombre') ?></strong>
                    <span style="font-size:0.8rem; color:var(--sa-muted);"><?= htmlspecialchars($member['email']) ?></span>
                </span>
                <span class="sa-badge sa-badge-<?= $member['role'] === 'owner' ? 'pro' : 'free' ?>">
                    <?= ucfirst($member['role']) ?>
                </span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($s['db_error']): ?>
<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:1.25rem; color:#991b1b; margin-bottom:2rem;">
    <strong>Error de conexión:</strong> No se pudo conectar a la base de datos <code><?= htmlspecialchars($store['db_name']) ?></code>.
    Verificá que la BD exista y las credenciales sean correctas.
</div>
<?php endif; ?>

<!-- Zona peligrosa -->
<div class="sa-detail-card" style="border:1px solid #fecaca; margin-top:2rem;">
    <h3 style="color:#dc2626;">Zona peligrosa</h3>
    <p style="color:var(--sa-muted); font-size:0.9rem; margin:0.5rem 0 1rem;">
        Eliminar esta tienda borrará todas sus tablas, productos, órdenes, configuraciones y datos asociados. Esta acción es irreversible.
    </p>
    <button type="button" class="sa-btn" style="background:#dc2626; color:#fff;" onclick="document.getElementById('deleteModal').style.display='flex'">
        🗑️ Eliminar tienda
    </button>
</div>

<!-- Modal de confirmación -->
<div id="deleteModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.5); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:440px; width:100%; box-shadow:0 25px 50px rgba(0,0,0,.25);">
        <h3 style="margin:0 0 0.5rem; color:#dc2626;">Eliminar tienda</h3>
        <p style="color:#666; font-size:0.9rem; margin:0 0 1.25rem;">
            Para confirmar, escribí el nombre de la tienda: <strong style="color:#111;"><?= htmlspecialchars($store['slug']) ?></strong>
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
(function(){
    var slug = <?= json_encode($store['slug']) ?>;
    var storeId = <?= (int)$store['id'] ?>;
    var inp = document.getElementById('deleteConfirmInput');
    var btn = document.getElementById('deleteConfirmBtn');
    var errP = document.getElementById('deleteError');

    inp.addEventListener('input', function(){
        var ok = inp.value.trim() === slug;
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
            body: JSON.stringify({store_id: storeId, confirm_slug: inp.value.trim()})
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.success) {
                window.location.href = '<?= PLATFORM_PAGES_URL ?>/superadmin/stores.php';
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
</script>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
