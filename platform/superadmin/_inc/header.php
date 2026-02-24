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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Super Admin | Somos Tiendi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PLATFORM_PAGES_URL ?>/superadmin/css/superadmin.css">
</head>
<body>
    <aside class="sa-sidebar">
        <div class="sa-sidebar-logo">
            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/">Somos <span>Tiendi</span></a>
            <small>Super Admin</small>
        </div>
        <nav class="sa-nav">
            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/" class="<?= $_saCurrentPage === 'index' ? 'active' : '' ?>">
                <span class="sa-nav-icon">📊</span> Dashboard
            </a>
            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/stores.php" class="<?= $_saCurrentPage === 'stores' ? 'active' : '' ?>">
                <span class="sa-nav-icon">🏪</span> Tiendas
            </a>
            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/users.php" class="<?= $_saCurrentPage === 'users' ? 'active' : '' ?>">
                <span class="sa-nav-icon">👥</span> Usuarios
            </a>
        </nav>
        <div class="sa-sidebar-footer">
            <a href="<?= PLATFORM_PAGES_URL ?>/dashboard.php">← Volver a mi panel</a>
        </div>
    </aside>
    <main class="sa-main">
