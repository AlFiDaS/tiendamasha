<?php
/**
 * Header compartido del panel de administración
 */
if (!defined('LUME_ADMIN')) {
    require_once '../../config.php';
}

requireAuth();
$currentUser = getCurrentUser();
require_once __DIR__ . '/icon.php';

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
    <svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true"><symbol id="icon-cart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></symbol><symbol id="icon-heart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></symbol><symbol id="icon-package" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></symbol><symbol id="icon-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></symbol><symbol id="icon-credit-card" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></symbol><symbol id="icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></symbol><symbol id="icon-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></symbol><symbol id="icon-clipboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></symbol><symbol id="icon-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></symbol><symbol id="icon-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></symbol><symbol id="icon-smartphone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></symbol><symbol id="icon-paperclip" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></symbol><symbol id="icon-tag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/></symbol><symbol id="icon-dollar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></symbol><symbol id="icon-image" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></symbol><symbol id="icon-folder" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></symbol><symbol id="icon-home" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></symbol><symbol id="icon-settings" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></symbol><symbol id="icon-user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></symbol><symbol id="icon-save" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></symbol><symbol id="icon-ticket" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/></symbol><symbol id="icon-alert" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></symbol><symbol id="icon-star" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></symbol><symbol id="icon-arrow-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></symbol><symbol id="icon-store" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></symbol><symbol id="icon-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></symbol><symbol id="icon-log-out" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></symbol><symbol id="icon-trending-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></symbol><symbol id="icon-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></symbol><symbol id="icon-bar-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></symbol><symbol id="icon-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></symbol><symbol id="icon-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol><symbol id="icon-zap" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></symbol></svg>
    <div class="admin-layout">
        <!-- Sidebar izquierda -->
        <aside class="admin-nav-sidebar" id="admin-nav-sidebar">
            <div class="admin-nav-brand">
                <span class="admin-nav-brand-text"><?= htmlspecialchars($shopName) ?></span>
            </div>
            
            <nav class="admin-nav-menu">
                <section>
                    <a href="<?= ADMIN_URL ?>/index.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'index.php' && empty($_GET)) ? 'active' : '' ?>">
                        <span class="nav-icon"><?= icon('chart', 20) ?></span> Dashboard
                        <?php if ($stockNotificationsCount > 0): ?><span class="nav-badge"><?= $stockNotificationsCount ?></span><?php endif; ?>
                    </a>
                    <a href="<?= ADMIN_URL ?>/list.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'list.php') ? 'active' : '' ?>">
                        <span class="nav-icon"><?= icon('package', 20) ?></span> Productos
                    </a>
                    <a href="<?= ADMIN_URL ?>/ordenes/list.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'ordenes') !== false) ? 'active' : '' ?>">
                        <span class="nav-icon"><?= icon('cart', 20) ?></span> Pedidos
                    </a>
                </section>
                <section>
                    <div class="menu-label">Accesos rápidos</div>
                    <a href="<?= ADMIN_URL ?>/add.php"><span class="nav-icon"><?= icon('plus', 20) ?></span> Agregar producto</a>
                    <a href="<?= ADMIN_URL ?>/ordenar.php"><span class="nav-icon"><?= icon('clipboard', 20) ?></span> Ordenar productos</a>
                </section>
                <section>
                    <div class="menu-label">Catálogo</div>
                    <a href="<?= ADMIN_URL ?>/galeria/list.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'galeria') !== false) ? 'active' : '' ?>">
                        <span class="nav-icon"><?= icon('image', 20) ?></span> Galería
                    </a>
                    <a href="<?= ADMIN_URL ?>/categorias/list.php"><span class="nav-icon"><?= icon('folder', 20) ?></span> Categorías</a>
                    <a href="<?= ADMIN_URL ?>/cupones/list.php"><span class="nav-icon"><?= icon('ticket', 20) ?></span> Cupones</a>
                </section>
                <section>
                    <div class="menu-label">Configuración</div>
                    <a href="<?= ADMIN_URL ?>/landing-page.php"><span class="nav-icon"><?= icon('home', 20) ?></span> Página de inicio</a>
                    <a href="<?= ADMIN_URL ?>/tienda.php"><span class="nav-icon"><?= icon('settings', 20) ?></span> Datos de la tienda</a>
                    <a href="<?= ADMIN_URL ?>/pagos.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'pagos.php' || strpos($_SERVER['REQUEST_URI'] ?? '', 'pagos') !== false) ? 'active' : '' ?>"><span class="nav-icon"><?= icon('credit-card', 20) ?></span> Configurar pagos</a>
                    <a href="<?= ADMIN_URL ?>/notificaciones.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'notificaciones.php') ? 'active' : '' ?>"><span class="nav-icon"><?= icon('smartphone', 20) ?></span> Notificaciones</a>
                </section>
                <section>
                    <div class="menu-label">Información</div>
                    <a href="<?= ADMIN_URL ?>/reports/list.php"><span class="nav-icon"><?= icon('bar-chart', 20) ?></span> Reportes mensuales</a>
                </section>
            </nav>
        </aside>
        
        <!-- Área principal -->
        <div class="admin-main-wrapper" id="admin-main-wrapper">
            <!-- Barra superior -->
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button class="admin-sidebar-toggle" id="admin-sidebar-toggle" aria-label="Alternar menú"><?= icon('menu', 24) ?></button>
                </div>
                <div class="admin-topbar-right">
                    <div class="admin-notifications">
                        <button class="admin-notifications-btn" id="admin-notifications-btn" aria-label="Notificaciones">
                            <?= icon('bell', 20) ?>
                            <?php if ($stockNotificationsCount > 0): ?>
                                <span class="admin-notifications-badge"><?= $stockNotificationsCount ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="admin-notifications-dropdown" id="admin-notifications-dropdown">
                            <div class="admin-notifications-header">Notificaciones de Stock</div>
                            <?php if ($stockNotificationsCount > 0): ?>
                                <?php foreach (array_slice($outOfStockProducts, 0, 5) as $p): ?>
                                    <a href="<?= ADMIN_URL ?>/edit.php?id=<?= htmlspecialchars($p['id']) ?>" class="admin-notification-item stock-out">
                                        <span class="notif-title"><?= icon('alert', 16) ?> Sin stock: <?= htmlspecialchars($p['name']) ?></span>
                                        <span class="notif-desc">Stock agotado - Actualizar ahora</span>
                                    </a>
                                <?php endforeach; ?>
                                <?php foreach (array_slice($lowStockProducts, 0, 5) as $p): ?>
                                    <a href="<?= ADMIN_URL ?>/edit.php?id=<?= htmlspecialchars($p['id']) ?>" class="admin-notification-item stock-low">
                                        <span class="notif-title"><?= icon('trending-down', 16) ?> Stock bajo: <?= htmlspecialchars($p['name']) ?></span>
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
                            <a href="<?= ADMIN_URL ?>/perfil.php"><?= icon('user', 16) ?> Mi Perfil</a>
                            <a href="<?= ADMIN_URL ?>/logout.php"><?= icon('log-out', 16) ?> Cerrar sesión</a>
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
