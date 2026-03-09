<?php
/**
 * Dashboard del panel administrativo
 */
$pageTitle = 'Dashboard';
require_once '../config.php';
require_once '../helpers/stats.php';
require_once '../helpers/stock.php';
require_once '../helpers/shop-settings.php';
require_once '_inc/header.php';

// Obtener configuración de la tienda
$shopSettings = getShopSettings();
$shopName = $shopSettings['shop_name'] ?? SITE_NAME;
$primaryColor = '#5672E1'; // Color fijo del panel admin

// La función adjustBrightness() está definida en header.php
// Obtener el color hover (se calcula en header.php después de incluir)

// Obtener estadísticas del dashboard
$dashboardStats = getDashboardStats();

// Obtener productos con stock bajo
$lowStockProducts = getLowStockProducts();

// Obtener productos más vendidos
$topProducts = getTopSellingProducts(5, 'month');

// Obtener ventas por día (últimos 7 días)
$salesByDay = getSalesByDay();
?>

<div class="admin-content">
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-welcome">Hola, bienvenido</h1>
            <p class="page-desc">Panel de administración de <?= htmlspecialchars($shopName) ?></p>
        </div>
        <div class="page-breadcrumb">Dashboard / Inicio</div>
    </div>
    
    <h3 class="section-title"><?= icon('chart', 20) ?> Ventas</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green"><?= icon('dollar', 24) ?></div>
            <div>
                <div class="stat-number">$<?= number_format($dashboardStats['today']['total_revenue'], 2, ',', '.') ?></div>
                <div class="stat-label">Ventas de Hoy</div>
                <div class="stat-subtitle"><?= $dashboardStats['today']['total_orders'] ?> pedidos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><?= icon('chart', 24) ?></div>
            <div>
                <div class="stat-number">$<?= number_format($dashboardStats['month']['total_revenue'], 2, ',', '.') ?></div>
                <div class="stat-label">Ventas del Mes</div>
                <div class="stat-subtitle"><?= $dashboardStats['month']['total_orders'] ?> pedidos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><?= icon('cart', 24) ?></div>
            <div>
                <div class="stat-number"><?= $dashboardStats['pending_orders'] ?></div>
                <div class="stat-label">Pendientes</div>
                <div class="stat-subtitle">Ordenes por procesar</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><?= icon('credit-card', 24) ?></div>
            <div>
                <div class="stat-number"><?= $dashboardStats['orders_to_confirm'] ?></div>
                <div class="stat-label">A Confirmar</div>
                <div class="stat-subtitle">Pagos pendientes</div>
            </div>
        </div>
    </div>

    <h3 class="section-title"><?= icon('package', 20) ?> Productos</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><?= icon('package', 24) ?></div>
            <div>
                <div class="stat-number"><?= $dashboardStats['total_products'] ?></div>
                <div class="stat-label">Total Productos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><?= icon('alert', 24) ?></div>
            <div>
                <div class="stat-number"><?= $dashboardStats['low_stock_products'] ?></div>
                <div class="stat-label">Stock Bajo</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rose"><?= icon('x', 24) ?></div>
            <div>
                <div class="stat-number"><?= $dashboardStats['no_stock_products'] ?></div>
                <div class="stat-label">Sin Stock</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><?= icon('dollar', 24) ?></div>
            <div>
                <div class="stat-number">$<?= number_format($dashboardStats['month']['avg_order_value'], 2, ',', '.') ?></div>
                <div class="stat-label">Ticket Promedio</div>
            </div>
        </div>
    </div>
    
    <!-- Productos Más Vendidos -->
    <?php if (!empty($topProducts)): ?>
    <div class="top-products-section">
        <h3 class="section-title"><?= icon('star', 20) ?> Productos Más Vendidos (Este Mes)</h3>
        <div class="top-products-list">
            <?php foreach ($topProducts as $index => $product): ?>
                <div class="top-product-item">
                    <div class="product-rank">#<?= $index + 1 ?></div>
                    <div class="product-image">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="no-image"><?= icon('package', 32) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                        <div class="product-stats">
                            <span class="stat-badge"><?= (int)$product['total_sold'] ?> vendidos</span>
                            <span class="stat-badge revenue">$<?= number_format((float)($product['total_revenue'] ?? 0), 2, ',', '.') ?></span>
                        </div>
                    </div>
                    <a href="edit.php?id=<?= htmlspecialchars($product['id']) ?>" class="product-link">Ver →</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Alertas de Stock Bajo -->
    <?php if (!empty($lowStockProducts)): ?>
    <div class="low-stock-section">
        <h3 class="section-title"><?= icon('alert', 20) ?> Alertas de Stock Bajo</h3>
        <div class="low-stock-list">
            <?php foreach ($lowStockProducts as $product): ?>
                <div class="low-stock-item">
                    <div class="stock-info">
                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                        <div class="stock-details">
                            <span class="stock-badge low">Stock: <?= $product['stock'] ?></span>
                            <span class="stock-badge min">Mínimo: <?= $product['stock_minimo'] ?></span>
                        </div>
                    </div>
                    <a href="edit.php?id=<?= htmlspecialchars($product['id']) ?>" class="stock-link">Actualizar Stock →</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Resumen por categoría -->
    <?php
    $categorias = fetchAll("SELECT categoria, COUNT(*) as count FROM products GROUP BY categoria");
    if (!empty($categorias)):
    ?>
    <div class="category-summary">
        <h3 class="section-title"><?= icon('folder', 20) ?> Productos por Categoría</h3>
        <div class="category-grid">
            <?php foreach ($categorias as $cat): ?>
                <div class="category-item">
                    <div class="category-name"><?= ucfirst(htmlspecialchars($cat['categoria'])) ?></div>
                    <div class="category-count"><?= $cat['count'] ?> productos</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .top-products-section,
    .low-stock-section,
    .category-summary { margin-top: 2rem; }

    .top-products-list,
    .low-stock-list {
        background: var(--admin-card);
        border: 1px solid var(--admin-border);
        border-radius: var(--admin-radius);
        padding: 0;
    }

    .top-product-item,
    .low-stock-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.85rem 1.15rem;
        border-bottom: 1px solid var(--admin-bg);
    }
    .top-product-item:last-child,
    .low-stock-item:last-child { border-bottom: none; }

    .product-rank {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--admin-primary);
        min-width: 36px;
        text-align: center;
    }

    .product-image {
        width: 48px; height: 48px;
        border-radius: 10px;
        overflow: hidden;
        background: var(--admin-bg);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .product-image img { width: 100%; height: 100%; object-fit: cover; }
    .no-image { color: var(--admin-muted); }

    .product-info { flex: 1; min-width: 0; }
    .product-name { font-weight: 600; color: var(--admin-dark); font-size: 0.9rem; }

    .product-stats { display: flex; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.25rem; }
    .stat-badge {
        display: inline-flex; align-items: center;
        padding: 0.15rem 0.55rem;
        background: var(--admin-bg);
        border-radius: 50px;
        font-size: 0.75rem; font-weight: 600; color: var(--admin-muted);
    }
    .stat-badge.revenue { background: #d1fae5; color: #065f46; }

    .stock-info { flex: 1; }
    .stock-details { display: flex; gap: 0.35rem; margin-top: 0.3rem; flex-wrap: wrap; }
    .stock-badge {
        display: inline-flex; align-items: center;
        padding: 0.15rem 0.55rem;
        border-radius: 50px;
        font-size: 0.75rem; font-weight: 600;
    }
    .stock-badge.low { background: #fef3c7; color: #92400e; }
    .stock-badge.min { background: #f3e8ff; color: #7c3aed; }

    .product-link,
    .stock-link {
        color: var(--admin-primary);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .product-link:hover,
    .stock-link:hover { text-decoration: underline; }

    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1rem;
    }
    .category-item {
        background: var(--admin-card);
        border: 1px solid var(--admin-border);
        border-radius: var(--admin-radius);
        padding: 1.25rem;
        text-align: center;
        transition: border-color 0.2s;
    }
    .category-item:hover { border-color: var(--admin-primary); }
    .category-name { font-size: 0.9rem; font-weight: 600; color: var(--admin-dark); margin-bottom: 0.25rem; }
    .category-count { font-size: 1.3rem; font-weight: 800; color: var(--admin-primary); }

    @media (max-width: 768px) {
        .top-product-item,
        .low-stock-item { padding: 0.75rem; gap: 0.75rem; }
        .product-image { width: 40px; height: 40px; }
        .product-rank { font-size: 1rem; min-width: 28px; }
        .category-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 480px) {
        .category-grid { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    }
</style>

<?php require_once '_inc/footer.php'; ?>

