<?php
/**
 * ============================================
 * HELPER: Generación de Reportes
 * ============================================
 * Funciones para generar reportes mensuales
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

// Asegurar que db.php esté cargado
if (!function_exists('executeQuery')) {
    require_once __DIR__ . '/db.php';
}

if (!function_exists('getSalesThisMonth')) {
    require_once __DIR__ . '/stats.php';
}

/**
 * Generar reporte mensual completo
 * @param int $month Mes (1-12)
 * @param int $year Año
 * @return array|false
 */
function generateMonthlyReport($month, $year) {
    // Obtener ventas del mes
    $sales = getSalesThisMonth($month, $year);
    
    // Obtener productos más vendidos
    $topProducts = getTopSellingProducts(10, 'month');
    
    // Obtener órdenes del mes
    $sql = "SELECT 
                id, order_id, payer_name, payer_email, payer_phone,
                total_amount, payment_method, status, created_at,
                coupon_code, discount_amount
            FROM orders 
            WHERE MONTH(created_at) = :month 
            AND YEAR(created_at) = :year
            AND status IN ('approved', 'a_confirmar')
            ORDER BY created_at DESC";
    
    $orders = fetchAll($sql, ['month' => $month, 'year' => $year]);
    
    // Asegurar que $orders sea un array
    if ($orders === false) {
        $orders = [];
    }
    
    // Calcular estadísticas adicionales
    $stats = [
        'month' => $month,
        'year' => $year,
        'month_name' => date('F', mktime(0, 0, 0, $month, 1, $year)),
        'total_orders' => $sales['total_orders'],
        'total_revenue' => $sales['total_revenue'],
        'avg_order_value' => $sales['avg_order_value'],
        'top_products' => $topProducts,
        'orders' => $orders,
        'total_discounts' => 0,
        'orders_with_coupons' => 0
    ];
    
    // Calcular descuentos aplicados
    if (is_array($orders)) {
        foreach ($orders as $order) {
            if (!empty($order['coupon_code'])) {
                $stats['orders_with_coupons']++;
                $stats['total_discounts'] += (float)($order['discount_amount'] ?? 0);
            }
        }
    }
    
    return $stats;
}

/**
 * Guardar reporte mensual en archivo JSON
 * @param array $report Datos del reporte
 * @return string|false Ruta del archivo guardado o false en error
 */
function saveMonthlyReport($report) {
    $reportsDir = BASE_PATH . '/reports';
    
    // Crear directorio si no existe
    if (!is_dir($reportsDir)) {
        if (!mkdir($reportsDir, 0755, true)) {
            error_log('Error al crear directorio de reportes: ' . $reportsDir);
            return false;
        }
    }
    
    // Nombre del archivo: reporte-YYYY-MM.json
    $filename = sprintf('reporte-%04d-%02d.json', $report['year'], $report['month']);
    $filepath = $reportsDir . '/' . $filename;
    
    // Guardar reporte
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($filepath, $json, LOCK_EX) === false) {
        error_log('Error al guardar reporte: ' . $filepath);
        return false;
    }
    
    return $filepath;
}

/**
 * Generar y guardar reporte del mes anterior
 * @return bool
 */
function generateLastMonthReport() {
    $lastMonth = (int)date('m', strtotime('first day of last month'));
    $lastYear = (int)date('Y', strtotime('first day of last month'));
    
    $report = generateMonthlyReport($lastMonth, $lastYear);
    if (!$report) {
        return false;
    }
    
    $saved = saveMonthlyReport($report);
    return $saved !== false;
}

/**
 * Obtener lista de reportes guardados
 * @return array
 */
function getSavedReports() {
    $reportsDir = BASE_PATH . '/reports';
    
    if (!is_dir($reportsDir)) {
        return [];
    }
    
    $files = glob($reportsDir . '/reporte-*.json');
    $reports = [];
    
    foreach ($files as $file) {
        $basename = basename($file, '.json');
        if (preg_match('/reporte-(\d{4})-(\d{2})/', $basename, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            
            $fileInfo = [
                'year' => $year,
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1, $year)),
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'created' => filemtime($file)
            ];
            
            // Cargar datos básicos del reporte
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if ($data) {
                $fileInfo['total_orders'] = $data['total_orders'] ?? 0;
                $fileInfo['total_revenue'] = $data['total_revenue'] ?? 0;
            }
            
            $reports[] = $fileInfo;
        }
    }
    
    // Ordenar por fecha (más reciente primero)
    usort($reports, function($a, $b) {
        if ($a['year'] !== $b['year']) {
            return $b['year'] - $a['year'];
        }
        return $b['month'] - $a['month'];
    });
    
    return $reports;
}

/**
 * Verificar si es el último día del mes y generar reporte automáticamente
 * Esta función debe ser llamada por un cron job diario
 * @return bool
 */
function checkAndGenerateMonthlyReport() {
    $today = (int)date('d');
    $lastDayOfMonth = (int)date('t'); // Último día del mes actual
    
    // Si es el último día del mes, generar reporte del mes anterior
    if ($today === $lastDayOfMonth) {
        return generateLastMonthReport();
    }
    
    return false;
}

