<?php
/**
 * Header del panel Super Admin - Somos Tiendi
 */
if (!defined('TIENDI_PLATFORM')) {
    require_once dirname(__DIR__, 2) . '/../platform-config.php';
}
requireSuperAdmin();
$_saUser = platformGetCurrentUser();
$_saCurrentPage = basename($_SERVER['PHP_SELF'], '.php');
require_once __DIR__ . '/icon.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Super Admin | Somos Tiendi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PLATFORM_PAGES_URL ?>/superadmin/css/superadmin.css">
</head>
<body>
<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true"><symbol id="icon-bar-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></symbol><symbol id="icon-store" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></symbol><symbol id="icon-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol><symbol id="icon-save" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></symbol><symbol id="icon-package" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></symbol><symbol id="icon-cart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></symbol><symbol id="icon-dollar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></symbol><symbol id="icon-tag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/></symbol><symbol id="icon-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></symbol><symbol id="icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></symbol><symbol id="icon-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></symbol><symbol id="icon-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></symbol><symbol id="icon-star" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></symbol><symbol id="icon-zap" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></symbol></svg>
    <header class="sa-mobile-topbar" id="sa-mobile-topbar">
        <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/" class="sa-mobile-logo">Somos <span>Tiendi</span></a>
        <button type="button" class="sa-nav-toggle" id="sa-nav-toggle" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>
    </header>
    <div class="sa-sidebar-backdrop" id="sa-sidebar-backdrop"></div>
    <aside class="sa-sidebar" id="sa-sidebar">
        <div class="sa-sidebar-logo">
            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/">Somos <span>Tiendi</span></a>
            <small>Super Admin</small>
        </div>
        <nav class="sa-nav">
            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/" class="<?= $_saCurrentPage === 'index' ? 'active' : '' ?>">
                <span class="sa-nav-icon"><?= icon('bar-chart', 20) ?></span> Dashboard
            </a>
            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/stores.php" class="<?= $_saCurrentPage === 'stores' ? 'active' : '' ?>">
                <span class="sa-nav-icon"><?= icon('store', 20) ?></span> Tiendas
            </a>
            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/users.php" class="<?= $_saCurrentPage === 'users' ? 'active' : '' ?>">
                <span class="sa-nav-icon"><?= icon('users', 20) ?></span> Usuarios
            </a>
            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/backup.php" class="<?= $_saCurrentPage === 'backup' ? 'active' : '' ?>">
                <span class="sa-nav-icon"><?= icon('save', 20) ?></span> Backups
            </a>
        </nav>
        <div class="sa-sidebar-footer">
            <a href="<?= PLATFORM_PAGES_URL ?>/dashboard.php">← Volver a mi panel</a>
        </div>
    </aside>
    <script>
    (function(){
        var t=document.getElementById('sa-nav-toggle');
        var s=document.getElementById('sa-sidebar');
        var b=document.getElementById('sa-sidebar-backdrop');
        function close(){ if(s)s.classList.remove('open'); if(t)t.classList.remove('active'); if(b)b.classList.remove('open'); }
        if(t&&s){ t.addEventListener('click',function(){ s.classList.toggle('open'); t.classList.toggle('active'); if(b)b.classList.toggle('open'); }); }
        if(b){ b.addEventListener('click',close); }
        if(s){ s.querySelectorAll('a').forEach(function(a){ a.addEventListener('click',close); }); }
    })();
    </script>
    <main class="sa-main">
