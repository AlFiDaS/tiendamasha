<?php
/**
 * Ver reporte de prueba (datos ficticios)
 */
$pageTitle = 'Reporte de Prueba';
require_once '../../config.php';
require_once '../../helpers/reports.php';
require_once '../../helpers/auth.php';

requireAuth();

$report = generateTestReport();

require_once '../_inc/header.php';
?>

<div class="admin-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2><?= icon('bar-chart', 24) ?> Reporte de Prueba: <?= htmlspecialchars($report['month_name']) ?> <?= $report['year'] ?></h2>
            <p style="color: #999; margin: 0.25rem 0 0 0; font-size: 0.9rem;">Datos ficticios para visualizar el formato del reporte</p>
        </div>
        <a href="<?= ADMIN_URL ?>/reports/list.php" class="btn btn-secondary">← Volver</a>
    </div>
    
    <!-- Resumen -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-number"><?= number_format($report['total_orders']) ?></div>
            <div class="stat-label">Total Pedidos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">$<?= number_format($report['total_revenue'], 2, ',', '.') ?></div>
            <div class="stat-label">Total Ventas</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">$<?= number_format($report['avg_order_value'], 2, ',', '.') ?></div>
            <div class="stat-label">Ticket Promedio</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($report['orders_with_coupons'] ?? 0) ?></div>
            <div class="stat-label">Pedidos con Cupones</div>
        </div>
    </div>
    
    <!-- Productos más vendidos -->
    <?php if (!empty($report['top_products'])): ?>
        <div class="card" style="margin-bottom: 2rem;">
            <h3>Productos Más Vendidos</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad Vendida</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['top_products'] as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= number_format($product['total_sold']) ?></td>
                            <td>$<?= number_format($product['total_revenue'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <!-- Detalle de órdenes -->
    <?php if (!empty($report['orders'])): ?>
        <div class="card">
            <h3>Detalle de Órdenes (<?= count($report['orders']) ?>)</h3>
            <div style="max-height: 500px; overflow-y: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Método</th>
                            <th>Cupón</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['orders'] as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['order_id']) ?></td>
                                <td><?= htmlspecialchars($order['payer_name']) ?></td>
                                <td>$<?= number_format($order['total_amount'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($order['payment_method']) ?></td>
                                <td><?= !empty($order['coupon_code']) ? htmlspecialchars($order['coupon_code']) : '-' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../_inc/footer.php'; ?>
