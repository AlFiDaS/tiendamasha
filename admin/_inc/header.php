<?php
/**
 * Header compartido del panel de administración
 */
if (!defined('LUME_ADMIN')) {
    require_once '../../config.php';
}

requireAuth();
$currentUser = getCurrentUser();

// Notificaciones de stock (para la campana)
require_once __DIR__ . '/../../helpers/stock.php';
$lowStockProducts = getLowStockProducts();
$outOfStockProducts = getOutOfStockProducts();
$stockNotificationsCount = count($lowStockProducts) + count($outOfStockProducts);

// Obtener configuración de la tienda
require_once __DIR__ . '/../../helpers/shop-settings.php';
$shopSettings = getShopSettings();
$shopName = $shopSettings['shop_name'] ?? SITE_NAME;
$primaryColor = '#5672E1';

function adjustBrightness($hex, $percent) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, round($r + ($r * $percent / 100))));
    $g = max(0, min(255, round($g + ($g * $percent / 100))));
    $b = max(0, min(255, round($b + ($b * $percent / 100))));
    
    return '#' . str_pad(dechex((int)$r), 2, '0', STR_PAD_LEFT) . 
           str_pad(dechex((int)$g), 2, '0', STR_PAD_LEFT) . 
           str_pad(dechex((int)$b), 2, '0', STR_PAD_LEFT);
}

$primaryColorHover = adjustBrightness($primaryColor, -15);
$primaryColorLight = adjustBrightness($primaryColor, 20);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php if (defined('CURRENT_STORE_SLUG')): ?>
    <script>
    window.__STORE_BASE="/<?= htmlspecialchars(CURRENT_STORE_SLUG) ?>";
    (function(){
    var b=window.__STORE_BASE;
    var f=window.fetch;
    window.fetch=function(u,o){
      if(typeof u==="string"){
        if(u.startsWith("/api/"))u=b+u;
        else if(u.match(/^https?:\/\/[^/]+(:\d+)?\/api\//)){var p=new URL(u);p.pathname=b+p.pathname;u=p.toString()}
      }
      return f.call(this,u,o)
    };
    var X=XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open=function(m,u){
      if(typeof u==="string"&&u.startsWith("/api/"))arguments[1]=b+u;
      return X.apply(this,arguments)
    };
    })();
    </script>
    <?php endif; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= htmlspecialchars($shopName) ?></title>
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/css/admin.css">
    <style>
        :root {
            --primary-color: <?= htmlspecialchars($primaryColor) ?>;
            --primary-color-hover: <?= htmlspecialchars($primaryColorHover) ?>;
            --primary-color-light: <?= htmlspecialchars($primaryColorLight) ?>;
            <?php 
            $hex = ltrim($primaryColor, '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            ?>
            --primary-color-rgb: <?= $r ?>, <?= $g ?>, <?= $b ?>;
        }
    </style>
</head>
<body<?= (basename($_SERVER['PHP_SELF'] ?? '') == 'landing-page.php') ? ' class="landing-editor-page"' : '' ?>>
    <div class="admin-layout">
        <!-- Sidebar izquierda -->
        <aside class="admin-nav-sidebar" id="admin-nav-sidebar">
            <div class="admin-nav-brand">
                <span class="admin-nav-brand-text"><?= htmlspecialchars($shopName) ?></span>
            </div>
            
            <nav class="admin-nav-menu">
                <section>
                    <a href="<?= ADMIN_URL ?>/index.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'index.php' && empty($_GET)) ? 'active' : '' ?>">
                        <span class="nav-icon">📊</span> Dashboard
                        <?php if ($stockNotificationsCount > 0): ?><span class="nav-badge"><?= $stockNotificationsCount ?></span><?php endif; ?>
                    </a>
                    <a href="<?= ADMIN_URL ?>/list.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'list.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📦</span> Productos
                    </a>
                    <a href="<?= ADMIN_URL ?>/ordenes/list.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'ordenes') !== false) ? 'active' : '' ?>">
                        <span class="nav-icon">🛒</span> Pedidos
                    </a>
                </section>
                <section>
                    <div class="menu-label">Accesos rápidos</div>
                    <a href="<?= ADMIN_URL ?>/add.php"><span class="nav-icon" style="color:inherit;">+</span> Agregar producto</a>
                    <a href="<?= ADMIN_URL ?>/ordenar.php"><span class="nav-icon">📋</span> Ordenar productos</a>
                </section>
                <section>
                    <div class="menu-label">Catálogo</div>
                    <a href="<?= ADMIN_URL ?>/galeria/list.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'galeria') !== false) ? 'active' : '' ?>">
                        <span class="nav-icon">🖼️</span> Galería
                    </a>
                    <a href="<?= ADMIN_URL ?>/categorias/list.php"><span class="nav-icon">📁</span> Categorías</a>
                    <a href="<?= ADMIN_URL ?>/cupones/list.php"><span class="nav-icon">🎟️</span> Cupones</a>
                </section>
                <section>
                    <div class="menu-label">Configuración</div>
                    <a href="<?= ADMIN_URL ?>/landing-page.php"><span class="nav-icon">🏠</span> Página de inicio</a>
                    <a href="<?= ADMIN_URL ?>/tienda.php"><span class="nav-icon">⚙️</span> Datos de la tienda</a>
                    <a href="<?= ADMIN_URL ?>/pagos.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'pagos.php' || strpos($_SERVER['REQUEST_URI'] ?? '', 'pagos') !== false) ? 'active' : '' ?>"><span class="nav-icon">💳</span> Configurar pagos</a>
                    <a href="<?= ADMIN_URL ?>/notificaciones.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'notificaciones.php') ? 'active' : '' ?>"><span class="nav-icon">📱</span> Notificaciones</a>
                </section>
                <section>
                    <div class="menu-label">Información</div>
                    <a href="<?= ADMIN_URL ?>/reports/list.php"><span class="nav-icon">📈</span> Reportes mensuales</a>
                </section>
            </nav>
        </aside>
        
        <!-- Área principal -->
        <div class="admin-main-wrapper" id="admin-main-wrapper">
            <!-- Barra superior -->
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button class="admin-sidebar-toggle" id="admin-sidebar-toggle" aria-label="Alternar menú">☰</button>
                </div>
                <div class="admin-topbar-right">
                    <div class="admin-notifications">
                        <button class="admin-notifications-btn" id="admin-notifications-btn" aria-label="Notificaciones">
                            🔔
                            <?php if ($stockNotificationsCount > 0): ?>
                                <span class="admin-notifications-badge"><?= $stockNotificationsCount ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="admin-notifications-dropdown" id="admin-notifications-dropdown">
                            <div class="admin-notifications-header">Notificaciones de Stock</div>
                            <?php if ($stockNotificationsCount > 0): ?>
                                <?php foreach (array_slice($outOfStockProducts, 0, 5) as $p): ?>
                                    <a href="<?= ADMIN_URL ?>/edit.php?id=<?= htmlspecialchars($p['id']) ?>" class="admin-notification-item stock-out">
                                        <span class="notif-title">⚠️ Sin stock: <?= htmlspecialchars($p['name']) ?></span>
                                        <span class="notif-desc">Stock agotado - Actualizar ahora</span>
                                    </a>
                                <?php endforeach; ?>
                                <?php foreach (array_slice($lowStockProducts, 0, 5) as $p): ?>
                                    <a href="<?= ADMIN_URL ?>/edit.php?id=<?= htmlspecialchars($p['id']) ?>" class="admin-notification-item stock-low">
                                        <span class="notif-title">📉 Stock bajo: <?= htmlspecialchars($p['name']) ?></span>
                                        <span class="notif-desc">Quedan <?= $p['stock'] ?> (mín: <?= $p['stock_minimo'] ?>)</span>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="admin-notification-item" style="cursor:default;">
                                    <span class="notif-desc">No hay alertas de stock</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="admin-user-menu" id="admin-user-menu">
                        <div class="admin-user-avatar"><?= strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)) ?></div>
                        <span class="admin-user-name"><?= htmlspecialchars($currentUser['username'] ?? 'Admin') ?></span>
                        <span style="font-size:0.7rem;color:#9ca3af;">▼</span>
                        <div class="admin-user-dropdown" id="admin-user-dropdown">
                            <a href="<?= ADMIN_URL ?>/perfil.php">👤 Mi Perfil</a>
                            <a href="<?= ADMIN_URL ?>/logout.php">🚪 Cerrar sesión</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="admin-container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
