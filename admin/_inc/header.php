<?php
/**
 * Header compartido del panel de administración
 */
if (!defined('LUME_ADMIN')) {
    require_once '../../config.php';
}

requireAuth();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?>LUME</title>
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
        
        .admin-header {
            background: linear-gradient(135deg, #e0a4ce 0%, #d89bc0 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .admin-menu-toggle {
            display: none;
            font-size: 1.5rem;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0.5rem;
            z-index: 1002;
        }
        
        .admin-header-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .admin-header-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .admin-header-nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .admin-header-nav .logout-btn {
            background: rgba(255,255,255,0.25);
            font-weight: 600;
            border: 2px solid rgba(255,255,255,0.5);
            padding: 0.5rem 1.25rem;
        }
        
        .admin-header-nav .logout-btn:hover {
            background: rgba(255,255,255,0.35);
            border-color: rgba(255,255,255,0.7);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
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
            background: #e0a4ce;
            color: white;
        }
        
        /* Filtros - Desktop */
        .filters-container {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
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
            border-color: #e0a4ce;
            box-shadow: 0 0 0 2px rgba(224, 164, 206, 0.2);
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
            background: #e0a4ce;
            color: white;
        }
        
        .btn-primary:hover {
            background: #d89bc0;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
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
            background: #007bff;
            color: white;
        }
        
        .actions .btn-edit:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,123,255,0.3);
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
            background: linear-gradient(135deg, #e0a4ce 0%, #d89bc0 100%);
            color: white;
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
            border-bottom: 2px solid #e0a4ce;
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
            color: #e0a4ce;
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
        @media (max-width: 768px) {
            .admin-header {
                padding: 1rem;
            }
            
            .admin-header-content {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                position: relative;
            }
            
            .admin-header h1 {
                font-size: 1.25rem;
            }
            
            .admin-menu-toggle {
                display: block;
            }
            
            .admin-header-nav {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                right: 0;
                background: linear-gradient(135deg, #e0a4ce 0%, #d89bc0 100%);
                width: 100%;
                padding: 1rem;
                gap: 0.5rem;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 1001;
                margin-top: 0.5rem;
                border-radius: 8px;
            }
            
            .admin-header-nav.open {
                display: flex;
            }
            
            .admin-header-nav a {
                width: 100%;
                text-align: left;
                padding: 0.75rem 1rem;
                border-radius: 4px;
            }
            
            .admin-header-nav a:hover {
                background: rgba(255,255,255,0.2);
            }
            
            .admin-header-nav .logout-btn {
                background: rgba(255,255,255,0.25);
                font-weight: 600;
                border: 2px solid rgba(255,255,255,0.5);
                margin-top: 0.5rem;
                width: 100%;
                text-align: center;
            }
            
            .admin-header-nav .logout-btn:hover {
                background: rgba(255,255,255,0.35);
                border-color: rgba(255,255,255,0.7);
            }
            
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
                color: #e0a4ce;
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
                border-color: #e0a4ce;
                box-shadow: 0 0 0 2px rgba(224, 164, 206, 0.2);
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
    <header class="admin-header">
        <div class="admin-header-content">
            <h1>Hola, Gisela</h1>
            <button id="admin-menu-toggle" class="admin-menu-toggle" aria-label="Abrir menú">
                ☰
            </button>
            <nav class="admin-header-nav" id="admin-header-nav">
                <a href="<?= ADMIN_URL ?>/index.php">Dashboard</a>
                <a href="<?= ADMIN_URL ?>/list.php">Productos</a>
                <a href="<?= ADMIN_URL ?>/galeria/list.php">Galería</a>
                <a href="<?= ADMIN_URL ?>/ordenes/list.php">Pedidos</a>
                <a href="<?= ADMIN_URL ?>/logout.php" class="logout-btn">Salir</a>
            </nav>
        </div>
    </header>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('admin-menu-toggle');
            const nav = document.getElementById('admin-header-nav');
            
            if (toggle && nav) {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    nav.classList.toggle('open');
                    if (nav.classList.contains('open')) {
                        toggle.textContent = '✕';
                    } else {
                        toggle.textContent = '☰';
                    }
                });
                
                document.addEventListener('click', function(e) {
                    if (nav.classList.contains('open') && 
                        !nav.contains(e.target) && 
                        e.target.id !== 'admin-menu-toggle' &&
                        !toggle.contains(e.target)) {
                        nav.classList.remove('open');
                        toggle.textContent = '☰';
                    }
                });
            }
        });
    </script>
    
    <div class="admin-container">
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

