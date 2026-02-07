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
$primaryColor = $shopSettings['primary_color'] ?? '#FF6B35';

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
    
    <!-- Estadísticas de Ventas -->
    <h3 class="section-title">📊 Ventas</h3>
    <div class="stats-grid">
        <div class="stat-card stat-sales">
            <div class="stat-number">$<?= number_format($dashboardStats['today']['total_revenue'], 2, ',', '.') ?></div>
            <div class="stat-label">Ventas de Hoy</div>
            <div class="stat-subtitle"><?= $dashboardStats['today']['total_orders'] ?> pedidos</div>
        </div>
        
        <div class="stat-card stat-month">
            <div class="stat-number">$<?= number_format($dashboardStats['month']['total_revenue'], 2, ',', '.') ?></div>
            <div class="stat-label">Ventas del Mes</div>
            <div class="stat-subtitle"><?= $dashboardStats['month']['total_orders'] ?> pedidos</div>
        </div>
        
        <div class="stat-card stat-pending">
            <div class="stat-number"><?= $dashboardStats['pending_orders'] ?></div>
            <div class="stat-label">Pendientes</div>
            <div class="stat-subtitle">Órdenes por procesar</div>
        </div>
        
        <div class="stat-card stat-confirm">
            <div class="stat-number"><?= $dashboardStats['orders_to_confirm'] ?></div>
            <div class="stat-label">A Confirmar</div>
            <div class="stat-subtitle">Pagos pendientes</div>
        </div>
    </div>
    
    <!-- Estadísticas de Productos -->
    <h3 class="section-title">📦 Productos</h3>
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-number"><?= $dashboardStats['total_products'] ?></div>
            <div class="stat-label">Total Productos</div>
        </div>
        
        <div class="stat-card stat-low-stock">
            <div class="stat-number"><?= $dashboardStats['low_stock_products'] ?></div>
            <div class="stat-label">Stock Bajo</div>
        </div>
        
        <div class="stat-card stat-no-stock">
            <div class="stat-number"><?= $dashboardStats['no_stock_products'] ?></div>
            <div class="stat-label">Sin Stock</div>
        </div>
        
        <div class="stat-card stat-avg">
            <div class="stat-number">$<?= number_format($dashboardStats['month']['avg_order_value'], 2, ',', '.') ?></div>
            <div class="stat-label">Ticket Promedio</div>
        </div>
    </div>
    
    <!-- Productos Más Vendidos -->
    <?php if (!empty($topProducts)): ?>
    <div class="top-products-section">
        <h3 class="section-title">🏆 Productos Más Vendidos (Este Mes)</h3>
        <div class="top-products-list">
            <?php foreach ($topProducts as $index => $product): ?>
                <div class="top-product-item">
                    <div class="product-rank">#<?= $index + 1 ?></div>
                    <div class="product-image">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="no-image">📦</div>
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
        <h3 class="section-title">⚠️ Alertas de Stock Bajo</h3>
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
        <h3 class="section-title">📁 Productos por Categoría</h3>
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
    :root {
        --primary-color: <?= htmlspecialchars($primaryColor) ?>;
        --primary-color-hover: <?= htmlspecialchars(adjustBrightness($primaryColor, -15)) ?>;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin: 2rem 0;
    }
    
    .stat-card {
        border-radius: 8px;
        padding: 1.25rem;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .stat-total {
        background: linear-gradient(135deg, #fffdf9, #fff6ef);
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-visible {
        background: linear-gradient(135deg, #fffdf9, #fff6ef);
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-destacados {
        background: linear-gradient(135deg, #fffdf9, #fff6ef);
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-sales {
        background: linear-gradient(135deg, #f0f9f4, #e8f5e9);
        border-left: 4px solid #4caf50;
    }
    
    .stat-month {
        background: linear-gradient(135deg, #f0f9f4, #e8f5e9);
        border-left: 4px solid #4caf50;
    }
    
    .stat-pending {
        background: linear-gradient(135deg, #fff6ef, #ffe8d6);
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-confirm {
        background: linear-gradient(135deg, #fff6ef, #ffe8d6);
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-low-stock {
        background: linear-gradient(135deg, #fff6ef, #ffe8d6);
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-no-stock {
        background: linear-gradient(135deg, #fff6ef, #ffe8d6);
        border-left: 4px solid #f44336;
    }
    
    .stat-avg {
        background: linear-gradient(135deg, #fffdf9, #fff6ef);
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: #666;
        font-weight: 500;
    }
    
    .stat-subtitle {
        font-size: 0.75rem;
        color: #999;
        margin-top: 0.25rem;
    }
    
    .top-products-section,
    .low-stock-section {
        margin-top: 3rem;
    }
    
    .top-products-list,
    .low-stock-list {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .top-product-item,
    .low-stock-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }
    
    .top-product-item:last-child,
    .low-stock-item:last-child {
        border-bottom: none;
    }
    
    .product-rank {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary-color);
        min-width: 40px;
        text-align: center;
    }
    
    .product-image {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        overflow: hidden;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .no-image {
        font-size: 2rem;
    }
    
    .product-info {
        flex: 1;
    }
    
    .product-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .product-stats {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .stat-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: #f0f0f0;
        border-radius: 12px;
        font-size: 0.85rem;
        color: #666;
    }
    
    .stat-badge.revenue {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .stock-info {
        flex: 1;
    }
    
    .stock-details {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
        flex-wrap: wrap;
    }
    
    .stock-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .stock-badge.low {
        background: #fff3e0;
        color: #e65100;
    }
    
    .stock-badge.min {
        background: #f3e5f5;
        color: #6a1b9a;
    }
    
    .product-link,
    .stock-link {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s;
    }
    
    .product-link:hover,
    .stock-link:hover {
        color: var(--primary-color-hover);
        text-decoration: underline;
    }
    
    .section-title {
        margin-top: 3rem;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        color: #333;
    }
    
    .section-title:first-of-type {
        margin-top: 0;
    }
    
    .category-summary {
        margin-top: 3rem;
    }
    
    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .category-item {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .category-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .category-count {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary-color);
    }
    
    /* Responsive para mobile */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin: 1.5rem 0;
        }
        
        .stat-card {
            padding: 1rem 0.75rem;
        }
        
        .stat-number {
            font-size: 1.75rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
        }
        
        .section-title {
            margin-top: 2rem;
            font-size: 1.25rem;
        }
        
        .category-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .stat-card {
            padding: 0.875rem 0.5rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
        }
        
        .category-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php require_once '_inc/footer.php'; ?>

