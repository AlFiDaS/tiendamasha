<?php
/**
 * Header de la plataforma Somos Tiendi
 */
if (!defined('TIENDI_PLATFORM')) {
    require_once dirname(__DIR__, 1) . '/../platform-config.php';
}
platformStartSession();
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
