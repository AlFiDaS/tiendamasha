<?php
/**
 * Lista de reportes mensuales
 */
$pageTitle = 'Reportes Mensuales';
require_once '../../config.php';
require_once '../../helpers/reports.php';
require_once '../../helpers/auth.php';

requireAuth();
require_once '../_inc/header.php';

// Obtener reportes guardados
$reports = getSavedReports();

// Eliminar reporte
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    if (!validateCSRFToken($_GET['csrf_token'])) {
        die('Token CSRF inválido');
    }
    
    $filename = $_GET['delete'];
    if (preg_match('/^reporte-\d{4}-\d{2}\.json$/', $filename)) {
        $filepath = BASE_PATH . '/reports/' . $filename;
        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                $success = "Reporte eliminado exitosamente";
                // Recargar lista
                $reports = getSavedReports();
            } else {
                $error = "Error al eliminar el reporte";
            }
        } else {
            $error = "Reporte no encontrado";
        }
    } else {
        $error = "Nombre de archivo inválido";
    }
}
?>

<div class="admin-content">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-secondary">← Volver al Dashboard</a>
    </div>
    <div style="margin-bottom: 2rem;">
        <h2>📊 Reportes Mensuales</h2>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Información sobre generación automática -->
    <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, #e0a4ce15 0%, #f7d4ed15 100%); border-left: 4px solid #e0a4ce;">
        <h3 style="color: #e0a4ce; margin-bottom: 0.5rem;">ℹ️ Generación Automática</h3>
        <p style="color: #666; margin: 0;">
            Los reportes mensuales se generan automáticamente el último día de cada mes a las 2:00 AM. 
            No es posible generar reportes manualmente para mantener la integridad de los datos.
        </p>
    </div>
    
    <!-- Lista de reportes -->
    <div class="card">
        <h3>Reportes Guardados</h3>
        <?php if (empty($reports)): ?>
            <p style="color: #666; padding: 2rem; text-align: center;">
                No hay reportes guardados aún. Los reportes se generan automáticamente el último día de cada mes.
            </p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mes/Año</th>
                        <th>Pedidos</th>
                        <th>Total Ventas</th>
                        <th>Tamaño</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td>
                                <strong><?= ucfirst($report['month_name']) ?> <?= $report['year'] ?></strong>
                            </td>
                            <td><?= number_format($report['total_orders'] ?? 0) ?></td>
                            <td>$<?= number_format($report['total_revenue'] ?? 0, 2, ',', '.') ?></td>
                            <td><?= number_format($report['size'] / 1024, 2) ?> KB</td>
                            <td><?= date('d/m/Y H:i', $report['created']) ?></td>
                            <td>
                                <a href="<?= ADMIN_URL ?>/reports/view.php?file=<?= urlencode($report['filename']) ?>" class="btn btn-small btn-primary">Ver</a>
                                <a href="<?= ADMIN_URL ?>/reports/download.php?file=<?= urlencode($report['filename']) ?>&format=excel" class="btn btn-small btn-secondary">📥 Descargar Excel</a>
                                <a href="?delete=<?= urlencode($report['filename']) ?>&csrf_token=<?= urlencode(generateCSRFToken()) ?>" 
                                   class="btn btn-small btn-danger" 
                                   onclick="return confirm('¿Estás seguro de eliminar este reporte?')">🗑️ Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../_inc/footer.php'; ?>

