<?php
/**
 * Dashboard - Lista de tiendas del usuario
 */
$pageTitle = 'Mis Tiendas';
require_once __DIR__ . '/../platform-config.php';
platformRequireAuth();

$userId = $_SESSION['platform_user_id'];
$stores = platformGetUserStores($userId);

$statusLabels = [
    'active'    => ['Activa', 'active'],
    'setup'     => ['En configuración', 'setup'],
    'suspended' => ['Suspendida', 'suspended'],
];

require_once __DIR__ . '/_inc/header.php';
?>

<div class="platform-page-header">
    <h1>Mis Tiendas</h1>
    <p>Administrá todas tus tiendas desde un solo lugar</p>
</div>

<?php if (!empty($_SESSION['platform_flash_success'])): ?>
    <div class="alert-t alert-t-success" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($_SESSION['platform_flash_success']) ?></div>
    <?php unset($_SESSION['platform_flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['platform_flash_error'])): ?>
    <div class="alert-t alert-t-error" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($_SESSION['platform_flash_error']) ?></div>
    <?php unset($_SESSION['platform_flash_error']); ?>
<?php endif; ?>

<?php if (isset($_GET['config_ok'])): ?>
    <div class="alert-t alert-t-success" style="margin-bottom: 1.5rem;">
        ¡Configuración rápida completada correctamente!
    </div>
<?php endif; ?>

<?php
// DEBUG temporal: ?_debug=1 muestra estado (eliminar después de resolver)
if (isset($_GET['_debug'])):
    $storesCount = is_array($stores) ? count($stores) : 0;
    ?>
    <div class="alert-t" style="margin-bottom:1rem; background:#1e293b; color:#e2e8f0; padding:1rem; border-radius:8px; font-family:monospace; font-size:0.9rem;">
        <strong>DEBUG</strong><br>
        $stores es array: <?= is_array($stores) ? 'SÍ' : 'NO' ?><br>
        Cantidad: <?= $storesCount ?><br>
        empty($stores): <?= empty($stores) ? 'SÍ (mostrará "No tenés tiendas")' : 'NO (debería mostrar tarjetas)' ?>
    </div>
<?php endif; ?>

<?php if (empty($stores)): ?>
    <div class="platform-card">
        <div class="empty-state">
            <div class="empty-state-icon"><?= icon('store', 48) ?></div>
            <h3>No tenés tiendas aún</h3>
            <p>Creá tu primera tienda online en minutos y empezá a vender hoy mismo.</p>
            <a href="<?= PLATFORM_PAGES_URL ?>/create-store.php" class="btn-t btn-t-primary">
                + Crear mi primera tienda
            </a>
        </div>
    </div>
<?php else:
    $stores = array_values($stores); // reindexar por si hay keys raros
?>
    <div class="platform-dashboard-actions">
        <?php if (canUserCreateStore($userId)): ?>
            <a href="<?= PLATFORM_PAGES_URL ?>/create-store.php" class="btn-t btn-t-primary btn-t-sm">
                + Nueva Tienda
            </a>
        <?php else: ?>
            <span class="btn-t btn-t-secondary btn-t-sm" style="opacity:0.8;" title="Límite alcanzado. Actualizá tu plan para crear más tiendas.">
                + Nueva Tienda (límite alcanzado)
            </span>
        <?php endif; ?>
    </div>

    <div class="stores-grid">
        <?php foreach ($stores as $store):
            $status = $statusLabels[$store['status']] ?? ['Desconocido', 'setup'];
            $domain = !empty($store['custom_domain'])
                ? $store['custom_domain']
                : 'www.somostiendi.com/' . $store['slug'];
        ?>
        <div class="store-card">
            <div class="store-card-header">
                <div class="store-card-icon"><?= icon('store', 32) ?></div>
                <div class="store-card-info">
                    <h3><?= htmlspecialchars($store['slug']) ?></h3>
                    <p><?= htmlspecialchars($domain) ?></p>
                </div>
            </div>

            <div class="store-card-meta">
                <span class="store-card-status <?= $status[1] ?>"><?= $status[0] ?></span>
                <span style="font-size:0.8rem; color:var(--t-muted);">
                    Plan: <strong><?= strtoupper(htmlspecialchars($store['plan'])) ?></strong>
                </span>
                <span style="font-size:0.8rem; color:var(--t-muted);">
                    Rol: <?= htmlspecialchars($store['role']) ?>
                </span>
            </div>

            <div class="store-card-actions">
                <?php if (!empty($store['configuracion_rapida_completada'])): ?>
                    <?php if ($store['status'] === 'active'): ?>
                        <a href="/<?= htmlspecialchars($store['slug']) ?>/admin/" class="btn-t btn-t-primary btn-t-sm">
                            Administrar
                        </a>
                        <a href="/<?= htmlspecialchars($store['slug']) ?>/" target="_blank" class="btn-t btn-t-secondary btn-t-sm">
                            Ver Tienda
                        </a>
                    <?php elseif ($store['status'] === 'setup'): ?>
                        <a href="/<?= htmlspecialchars($store['slug']) ?>/admin/" class="btn-t btn-t-primary btn-t-sm">
                            Completar Configuración
                        </a>
                    <?php else: ?>
                        <span class="btn-t btn-t-secondary btn-t-sm" style="opacity:0.6; pointer-events:none;">
                            Suspendida
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= PLATFORM_PAGES_URL ?>/configuracion-rapida.php?store=<?= urlencode($store['slug']) ?>" class="btn-t btn-t-primary btn-t-sm" style="background:var(--t-primary);">
                        Completar Configuración Rápida
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
