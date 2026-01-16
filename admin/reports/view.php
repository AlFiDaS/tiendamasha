<?php
/**
 * Ver reporte mensual
 */
$pageTitle = 'Ver Reporte';
require_once '../../config.php';
require_once '../../helpers/reports.php';
require_once '../../helpers/auth.php';

requireAuth();

$filename = $_GET['file'] ?? '';
if (empty($filename) || !preg_match('/^reporte-\d{4}-\d{2}\.json$/', $filename)) {
    header('Location: ' . ADMIN_URL . '/reports/list.php');
    exit;
}

$filepath = BASE_PATH . '/reports/' . $filename;
if (!file_exists($filepath)) {
    header('Location: ' . ADMIN_URL . '/reports/list.php');
    exit;
}

$content = file_get_contents($filepath);
$report = json_decode($content, true);

if (!$report) {
    die('Error al cargar el reporte');
}

require_once '../_inc/header.php';
?>

<div class="admin-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2>📊 Reporte: <?= ucfirst($report['month_name']) ?> <?= $report['year'] ?></h2>
        <div>
            <a href="<?= ADMIN_URL ?>/reports/list.php" class="btn-secondary">← Volver</a>
            <a href="<?= ADMIN_URL ?>/reports/download.php?file=<?= urlencode($filename) ?>" class="btn-primary">Descargar JSON</a>
        </div>
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

