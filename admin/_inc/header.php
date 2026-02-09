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
$primaryColor = '#5672E1'; // Color fijo del panel admin

// Función para ajustar brillo de color (para hover states)
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= htmlspecialchars($shopName) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
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
        
        /* === LAYOUT: Sidebar izquierda + Topbar + Contenido === */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar izquierda - estilo Xtreme Admin */
        .admin-nav-sidebar {
            width: 260px;
            min-width: 260px;
            background: linear-gradient(180deg, #1e2746 0%, #252d4a 50%, #1a2142 100%);
            color: rgba(255,255,255,0.9);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            transition: transform 0.3s ease, width 0.3s ease;
        }
        
        .admin-nav-sidebar.collapsed {
            transform: translateX(-260px);
        }
        
        .admin-nav-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .admin-nav-brand-text {
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
        }
        
        .admin-nav-menu {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }
        
        .admin-nav-menu section {
            margin-bottom: 1rem;
        }
        
        .admin-nav-menu .menu-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.4);
            padding: 0.5rem 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .admin-nav-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.95rem;
            border-left: 3px solid transparent;
        }
        
        .admin-nav-menu a:hover {
            background: rgba(255,255,255,0.08);
            color: white;
        }
        
        .admin-nav-menu a.active {
            background: rgba(255,255,255,0.12);
            color: white;
            border-left-color: var(--primary-color);
        }
        
        .admin-nav-menu .nav-icon {
            font-size: 1.15rem;
            width: 24px;
            text-align: center;
            opacity: 0.9;
        }
        
        .admin-nav-menu .nav-badge {
            margin-left: auto;
            background: #7c3aed;
            color: white;
            font-size: 0.75rem;
            padding: 0.15rem 0.5rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        /* Topbar superior */
        .admin-topbar {
            height: 60px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .admin-topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .admin-sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.35rem;
            cursor: pointer;
            color: #6b7280;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s;
            display: none;
        }
        
        .admin-sidebar-toggle:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .admin-topbar-right {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        
        .admin-notifications {
            position: relative;
        }
        
        .admin-notifications-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #6b7280;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s;
            position: relative;
        }
        
        .admin-notifications-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .admin-notifications-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-notifications-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            min-width: 320px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1001;
            display: none;
            border: 1px solid #e5e7eb;
        }
        
        .admin-notifications-dropdown.open {
            display: block;
        }
        
        .admin-notifications-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #374151;
        }
        
        .admin-notification-item {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
            font-size: 0.9rem;
            text-decoration: none;
            display: block;
            transition: background 0.2s;
        }
        
        .admin-notification-item:hover {
            background: #f9fafb;
        }
        
        .admin-notification-item .notif-title {
            font-weight: 600;
            color: #1f2937;
        }
        
        .admin-notification-item .notif-desc {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .admin-notification-item.stock-low .notif-title { color: #d97706; }
        .admin-notification-item.stock-out .notif-title { color: #dc2626; }
        
        .admin-user-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.75rem;
            background: #f9fafb;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid #e5e7eb;
            position: relative;
        }
        
        .admin-user-menu:hover {
            background: #f3f4f6;
        }
        
        .admin-user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-hover) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .admin-user-name {
            font-weight: 500;
            color: #374151;
            font-size: 0.95rem;
        }
        
        .admin-user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            min-width: 180px;
            z-index: 1001;
            display: none;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .admin-user-dropdown.open {
            display: block;
        }
        
        .admin-user-dropdown a {
            display: block;
            padding: 0.75rem 1.25rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        
        .admin-user-dropdown a:hover {
            background: #f9fafb;
        }
        
        .admin-main-wrapper {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .admin-main-wrapper.sidebar-collapsed {
            margin-left: 0;
        }
        
        .admin-container {
            flex: 1;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .page-welcome {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 0.25rem 0;
        }
        
        .page-desc {
            font-size: 0.95rem;
            color: #6b7280;
            margin: 0;
        }
        
        .page-breadcrumb {
            font-size: 0.9rem;
            color: #9ca3af;
        }
        
        .page-header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        
        .page-subtitle {
            font-size: 0.95rem;
            color: #6b7280;
            margin: 0.25rem 0 0 0;
        }
        
        .content-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #374151;
            margin: 0 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .admin-sidebar {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar ul {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .admin-sidebar a {
            color: #333;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            background: var(--primary-color);
            color: white;
        }
        
        /* Filtros - Desktop */
        .filters-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .filters-form .form-group {
            margin-bottom: 0;
        }
        
        .filters-form .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .filters-form .form-group select,
        .filters-form .form-group input[type="text"] {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            background: white;
            height: 2.5rem;
            box-sizing: border-box;
        }
        
        .filters-form .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .filters-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .filters-actions .btn {
            padding: 0.5rem 1rem;
            height: 2.5rem;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-content {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: var(--primary-color-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 0.5rem;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.875rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Ocultar td combinado en desktop */
        tbody td.mobile-info {
            display: none;
        }
        
        /* Mostrar td individuales en desktop */
        tbody td.desktop-only {
            display: table-cell;
        }
        
        table, .admin-content table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        .admin-content table th,
        .admin-content table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .admin-content table thead th {
            background: linear-gradient(135deg, #1e2746 0%, #252d4a 100%);
            color: white;
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .admin-content table tbody tr:hover {
            background: #f8f9fa;
        }
        
        table th,
        table td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .actions a {
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .actions .btn-edit {
            background: var(--primary-color);
            color: white;
        }
        
        .actions .btn-edit:hover {
            background: var(--primary-color-hover);
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .actions .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .actions .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(220,53,69,0.3);
        }
        
        /* 🎨 COMPONENTES MODERNOS */
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .card h3 {
            margin: 0 0 1rem 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .data-table thead {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .data-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .data-table tbody tr {
            transition: background 0.2s ease;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-small.btn-primary {
            background: #e0a4ce;
            color: white;
        }
        
        .btn-small.btn-primary:hover {
            background: #d89bc0;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(224, 164, 206, 0.3);
        }
        
        .btn-small.btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-small.btn-danger:hover {
            background: #c82333;
        }
        
        .btn-small.btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-small.btn-secondary:hover {
            background: #5a6268;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 500;
        }
        
        .stat-subtitle {
            font-size: 0.85rem;
            color: #999;
            margin-top: 0.25rem;
        }
        
        /* 📱 RESPONSIVE STYLES */
        @media (max-width: 992px) {
            .admin-sidebar-toggle {
                display: flex;
            }
            
            .admin-nav-sidebar {
                transform: translateX(-260px);
            }
            
            .admin-nav-sidebar.open {
                transform: translateX(0);
            }
            
            .admin-main-wrapper {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .admin-container {
                margin: 1rem auto;
                padding: 0 1rem;
            }
            
            .admin-content {
                padding: 0.75rem;
            }
            
            .admin-sidebar {
                padding: 1rem;
            }
            
            .admin-sidebar ul {
                flex-direction: column;
            }
            
            .admin-sidebar a {
                width: 100%;
                text-align: center;
            }
            
            /* Tablas responsive - convertir a cards compactas en mobile */
            table {
                width: 100%;
            }
            
            thead {
                display: none;
            }
            
            tbody tr {
                display: grid;
                grid-template-columns: 70px 1fr;
                grid-template-rows: auto auto auto auto auto;
                gap: 0.25rem 0.5rem;
                border: 1px solid #e0e0e0;
                margin-bottom: 0.75rem;
                padding: 0.5rem;
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            }
            
            tbody td {
                border: none;
                padding: 0;
                text-align: left;
            }
            
            tbody td:before {
                display: none;
            }
            
            /* Fila 1: Imagen (izquierda) / Categoría (derecha) */
            tbody td[data-label="Imagen"] {
                grid-row: 1 / 5;
                grid-column: 1;
                align-self: start;
            }
            
            tbody td[data-label="Imagen"] img,
            tbody td[data-label="Imagen"] div {
                width: 70px !important;
                height: 70px !important;
                border-radius: 8px;
                object-fit: cover;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            tbody td[data-label="Categoría"] {
                grid-row: 1;
                grid-column: 2;
                align-self: start;
                justify-self: end;
            }
            
            /* Fila 2: Nombre */
            tbody td[data-label="Nombre"] {
                grid-row: 2;
                grid-column: 2;
            }
            
            tbody td[data-label="Nombre"] strong {
                font-size: 1rem;
                font-weight: 700;
                color: #333;
                display: block;
                line-height: 1.2;
                margin-bottom: 0;
            }
            
            tbody td[data-label="Nombre"] small {
                display: none;
            }
            
            tbody td[data-label="Nombre"] br.desktop-only {
                display: none;
            }
            
            /* Fila 3: Precio (debajo del nombre) */
            tbody td[data-label="Precio"] {
                grid-row: 3;
                grid-column: 2;
                justify-self: start;
                align-self: start;
                font-size: 1.1rem;
                font-weight: 700;
                color: var(--primary-color);
                margin-top: 0;
            }
            
            /* Ocultar campos individuales en mobile */
            tbody td.desktop-only {
                display: none !important;
            }
            
            /* Mostrar y posicionar td combinado en mobile */
            tbody td.mobile-info {
                display: block !important;
                grid-row: 4;
                grid-column: 2;
                white-space: nowrap;
                overflow: hidden;
                margin-top: 0.25rem;
                padding-top: 0;
            }
            
            tbody td.mobile-info .badge {
                display: inline-block;
                margin-right: 0.3rem;
                padding: 0.25rem 0.5rem;
                font-size: 0.65rem;
                font-weight: 600;
                border-radius: 6px;
                line-height: 1.2;
                white-space: nowrap;
            }
            
            /* Fila 5: Editar / Eliminar */
            tbody td[data-label="Acciones"] {
                grid-row: 5;
                grid-column: 1 / 3;
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid #f0f0f0;
            }
            
            /* Badges compactos */
            tbody td .badge {
                display: inline-block;
                padding: 0.3rem 0.6rem;
                font-size: 0.7rem;
                font-weight: 600;
                border-radius: 10px;
                line-height: 1.2;
            }
            
            /* Botones de acción */
            .actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
                width: 100%;
            }
            
            .actions a {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
                text-align: center;
                border-radius: 6px;
                font-weight: 600;
                transition: all 0.3s;
            }
            
            .actions .btn-edit {
                background: #007bff;
                color: white;
            }
            
            .actions .btn-edit:hover {
                background: #0056b3;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,123,255,0.3);
            }
            
            .actions .btn-delete {
                background: #dc3545;
                color: white;
            }
            
            .actions .btn-delete:hover {
                background: #c82333;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(220,53,69,0.3);
            }
            
            /* Formularios responsive */
            .form-group {
                margin-bottom: 1rem;
            }
            
            /* Botones responsive */
            .btn {
                width: 100%;
                text-align: center;
                margin-bottom: 0.5rem;
            }
            
            /* Filtros responsive - 2 líneas en mobile */
            .filters-container {
                background: #f8f9fa;
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1rem;
            }
            
            .filters-form {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 0.5rem;
            }
            
            .filters-form .form-group {
                margin-bottom: 0;
            }
            
            .filters-form .form-group label {
                display: block;
                font-size: 0.75rem;
                margin-bottom: 0.25rem;
                color: #666;
                font-weight: 600;
            }
            
            .filters-form .form-group select,
            .filters-form .form-group input[type="text"] {
                width: 100%;
                padding: 0.4rem 0.5rem;
                font-size: 0.85rem;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: white;
                height: 2.2rem;
                box-sizing: border-box;
            }
            
            .filters-form .form-group input[type="text"]:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
            }
            
            /* Campo de búsqueda ocupa toda la primera fila en mobile */
            .filters-form .form-group.search-group {
                grid-column: 1 / -1;
            }
            
            /* Segunda fila: Categoría, Visible, Stock */
            .filters-form .form-group:not(.search-group) {
                grid-column: span 1;
            }
            
            .filters-actions {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
                margin-top: 0.5rem;
            }
            
            .filters-actions .btn {
                padding: 0.4rem 0.75rem;
                font-size: 0.85rem;
                white-space: nowrap;
                height: 2.2rem;
                box-sizing: border-box;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* En pantallas muy pequeñas, hacer los selects más compactos */
            @media (max-width: 400px) {
                .filters-form {
                    gap: 0.35rem;
                }
                
                .filters-form .form-group label {
                    font-size: 0.7rem;
                    margin-bottom: 0.2rem;
                }
                
                .filters-form .form-group select,
                .filters-form .form-group input[type="text"] {
                    padding: 0.35rem 0.4rem;
                    font-size: 0.8rem;
                }
                
                .filters-actions .btn {
                    padding: 0.35rem 0.6rem;
                    font-size: 0.8rem;
                }
            }
        }
        
        @media (max-width: 480px) {
            .admin-header h1 {
                font-size: 1rem;
            }
            
            .admin-content {
                padding: 0.75rem;
            }
            
            table {
                font-size: 0.875rem;
            }
            
            /* Grid de galería responsive */
            .galeria-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar izquierda -->
        <aside class="admin-nav-sidebar" id="admin-nav-sidebar">
            <div class="admin-nav-brand">
                <span class="admin-nav-brand-text"><?= htmlspecialchars($shopName) ?></span>
            </div>
            
            <nav class="admin-nav-menu">
                <section>
                    <div class="menu-label">Principal</div>
                    <a href="<?= ADMIN_URL ?>/index.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'index.php' && empty($_GET)) ? 'active' : '' ?>">
                        <span class="nav-icon">📊</span> Dashboard
                        <?php if ($stockNotificationsCount > 0): ?><span class="nav-badge"><?= $stockNotificationsCount ?></span><?php endif; ?>
                    </a>
                    <a href="<?= ADMIN_URL ?>/list.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'list.php') ? 'active' : '' ?>">
                        <span class="nav-icon">📦</span> Productos
                    </a>
                    <a href="<?= ADMIN_URL ?>/add.php"><span class="nav-icon">➕</span> Agregar Producto</a>
                    <a href="<?= ADMIN_URL ?>/ordenar.php"><span class="nav-icon">📋</span> Ordenar Productos</a>
                </section>
                <section>
                    <div class="menu-label">Gestión</div>
                    <a href="<?= ADMIN_URL ?>/galeria/list.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'galeria') !== false) ? 'active' : '' ?>">
                        <span class="nav-icon">🖼️</span> Galería
                    </a>
                    <a href="<?= ADMIN_URL ?>/ordenes/list.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'ordenes') !== false) ? 'active' : '' ?>">
                        <span class="nav-icon">🛒</span> Pedidos
                    </a>
                    <a href="<?= ADMIN_URL ?>/categorias/list.php"><span class="nav-icon">📁</span> Categorías</a>
                    <a href="<?= ADMIN_URL ?>/cupones/list.php"><span class="nav-icon">🎟️</span> Cupones</a>
                </section>
                <section>
                    <div class="menu-label">Configuración</div>
                    <a href="<?= ADMIN_URL ?>/landing-page.php"><span class="nav-icon">🏠</span> Landing Page</a>
                    <a href="<?= ADMIN_URL ?>/tienda.php"><span class="nav-icon">⚙️</span> Tienda</a>
                    <a href="<?= ADMIN_URL ?>/reports/list.php"><span class="nav-icon">📈</span> Reportes</a>
                    <a href="<?= ADMIN_URL ?>/backup/list.php"><span class="nav-icon">💾</span> Backups</a>
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

