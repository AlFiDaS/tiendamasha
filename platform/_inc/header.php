<?php
/**
 * Header de la plataforma Somos Tiendi
 */
if (!defined('TIENDI_PLATFORM')) {
    require_once dirname(__DIR__, 1) . '/../platform-config.php';
}
platformStartSession();
require_once __DIR__ . '/icon.php';
$_platformUser = platformIsAuthenticated() ? platformGetCurrentUser() : false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Somos Tiendi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PLATFORM_PAGES_URL ?>/css/platform.css">
</head>
<body>
<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true"><symbol id="icon-package" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></symbol><symbol id="icon-credit-card" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></symbol><symbol id="icon-smartphone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></symbol><symbol id="icon-bar-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></symbol><symbol id="icon-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></symbol><symbol id="icon-image" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></symbol><symbol id="icon-store" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></symbol></svg>
    <?php if ($_platformUser): ?>
    <nav class="platform-topbar">
        <a href="<?= PLATFORM_PAGES_URL ?>/dashboard.php" class="platform-logo">
            Somos <span>Tiendi</span>
        </a>
        <div class="platform-nav">
            <a href="<?= PLATFORM_PAGES_URL ?>/dashboard.php" class="<?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : '' ?>">Mis Tiendas</a>
            <a href="<?= PLATFORM_PAGES_URL ?>/create-store.php" class="<?= (basename($_SERVER['PHP_SELF']) === 'create-store.php') ? 'active' : '' ?>">Crear Tienda</a>
            <?php if (isSuperAdmin()): ?>
                <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/" style="background:var(--t-primary); color:#fff; border-radius:8px;">Super Admin</a>
            <?php endif; ?>
            <a href="<?= PLATFORM_PAGES_URL ?>/logout.php" class="platform-user-pill">
                <span class="platform-avatar"><?= strtoupper(substr($_platformUser['name'] ?? 'U', 0, 1)) ?></span>
                <?= htmlspecialchars($_platformUser['name'] ?? 'Usuario') ?>
            </a>
        </div>
    </nav>
    <?php endif; ?>
    <main class="platform-main">
