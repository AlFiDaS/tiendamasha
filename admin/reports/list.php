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
$availableMonths = getAvailableReportMonths();

// Generar reporte manualmente
if (isset($_POST['generate_report']) && isset($_POST['csrf_token'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF inválido');
    }
    
    $period = $_POST['month_year'] ?? '';
    if (preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
        $year = (int)$m[1];
        $month = (int)$m[2];
        
        // Verificar que el mes esté disponible
        $isValid = false;
        foreach ($availableMonths as $opt) {
            if ($opt['year'] === $year && $opt['month'] === $month) {
                $isValid = true;
                break;
            }
        }
        
        if ($isValid) {
            $saved = generateClosingMonthReport($month, $year);
            if ($saved) {
                $mesesEs = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                $success = "Reporte de " . $mesesEs[$month] . " $year generado correctamente";
                $reports = getSavedReports();
            } else {
                $error = "Error al generar el reporte";
            }
        } else {
            $error = "Mes no disponible para generar reporte";
        }
    } else {
        $error = "Selecciona un mes válido";
    }
}

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
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h2 style="margin: 0;">📊 Reportes Mensuales</h2>
        <a href="<?= ADMIN_URL ?>/reports/view-test.php" class="btn btn-secondary">Ver reporte de ejemplo</a>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Generar reporte manualmente -->
    <div class="card" style="margin-bottom: 2rem;">
        <h3>Generar Reporte Manualmente</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Solo podés generar reportes de meses completos. El mes actual recién está disponible a partir del día 1 del mes siguiente.
            Los meses disponibles van desde tu primera venta hasta el mes pasado.
        </p>
        <?php if (!empty($availableMonths)): ?>
        <form method="POST" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
            <div class="form-group" style="margin: 0; min-width: 200px;">
                <label for="month_year">Mes / Año</label>
                <select name="month_year" id="month_year" required>
                    <option value="">Seleccionar mes...</option>
                    <?php foreach ($availableMonths as $opt): ?>
                        <option value="<?= htmlspecialchars($opt['value']) ?>"><?= htmlspecialchars($opt['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="generate_report" class="btn btn-primary">Generar Reporte</button>
        </form>
        <?php else: ?>
        <p style="color: #999;">Aún no hay ventas registradas. Los meses estarán disponibles después de tu primera venta aprobada.</p>
        <?php endif; ?>
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

