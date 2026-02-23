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
            AND status IN ('approved', 'a_confirmar', 'finalizado')
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
 * Generar y guardar reporte del mes que está terminando (mes actual cuando es último día)
 * @param int|null $month Mes (1-12), si null usa el mes actual
 * @param int|null $year Año, si null usa el año actual
 * @return bool
 */
function generateClosingMonthReport($month = null, $year = null) {
    $month = $month ?? (int)date('m');
    $year = $year ?? (int)date('Y');
    
    $report = generateMonthlyReport($month, $year);
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
 * Generar reporte de prueba con datos ficticios
 * @return array
 */
function generateTestReport() {
    $mesesEs = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $month = (int)date('m');
    $year = (int)date('Y');
    $lastMonth = $month === 1 ? 12 : $month - 1;
    $lastYear = $month === 1 ? $year - 1 : $year;
    
    return [
        'month' => $lastMonth,
        'year' => $lastYear,
        'month_name' => $mesesEs[$lastMonth],
        'total_orders' => 15,
        'total_revenue' => 245000,
        'avg_order_value' => 16333.33,
        'total_discounts' => 5000,
        'orders_with_coupons' => 3,
        'top_products' => [
            ['name' => 'Vela Aromática Lavanda', 'total_sold' => 12, 'total_revenue' => 48000],
            ['name' => 'Vela Decorativa Corazón', 'total_sold' => 8, 'total_revenue' => 36000],
            ['name' => 'Souvenir Bodas x10', 'total_sold' => 5, 'total_revenue' => 45000],
            ['name' => 'Vela Navideña', 'total_sold' => 4, 'total_revenue' => 24000],
            ['name' => 'Vela Personalizada', 'total_sold' => 3, 'total_revenue' => 27000],
        ],
        'orders' => [
            ['order_id' => '#101', 'payer_name' => 'María González', 'payer_email' => 'maria@email.com', 'total_amount' => 18500, 'payment_method' => 'transferencia_directa', 'coupon_code' => 'VERANO10', 'created_at' => $lastYear . '-' . sprintf('%02d', $lastMonth) . '-15 14:30:00'],
            ['order_id' => '#102', 'payer_name' => 'Juan Pérez', 'payer_email' => 'juan@email.com', 'total_amount' => 32000, 'payment_method' => 'credit_card', 'coupon_code' => '', 'created_at' => $lastYear . '-' . sprintf('%02d', $lastMonth) . '-18 10:15:00'],
            ['order_id' => '#103', 'payer_name' => 'Ana Martínez', 'payer_email' => 'ana@email.com', 'total_amount' => 12500, 'payment_method' => 'transferencia_directa', 'coupon_code' => 'BIENVENIDO', 'created_at' => $lastYear . '-' . sprintf('%02d', $lastMonth) . '-20 16:45:00'],
            ['order_id' => '#104', 'payer_name' => 'Carlos Rodríguez', 'payer_email' => 'carlos@email.com', 'total_amount' => 28000, 'payment_method' => 'mercadopago_transferencia', 'coupon_code' => '', 'created_at' => $lastYear . '-' . sprintf('%02d', $lastMonth) . '-22 09:20:00'],
            ['order_id' => '#105', 'payer_name' => 'Laura Fernández', 'payer_email' => 'laura@email.com', 'total_amount' => 45000, 'payment_method' => 'transferencia_directa', 'coupon_code' => 'VERANO10', 'created_at' => $lastYear . '-' . sprintf('%02d', $lastMonth) . '-25 11:00:00'],
        ]
    ];
}

/**
 * Obtener meses disponibles para generar reporte manualmente
 * Desde el mes de la primera venta hasta el mes anterior al actual
 * (el mes actual solo está disponible a partir del día 1 del mes siguiente)
 * @return array [['year'=>2026, 'month'=>1, 'label'=>'Enero 2026'], ...]
 */
function getAvailableReportMonths() {
    // Mes más reciente que se puede generar: el mes anterior al actual
    $lastAvailable = new DateTime('first day of last month');
    
    // Primer mes con ventas
    $first = fetchOne(
        "SELECT MIN(created_at) as first_date FROM orders WHERE status IN ('approved', 'a_confirmar', 'finalizado')"
    );
    
    if (!$first || empty($first['first_date'])) {
        return [];
    }
    
    $firstDate = new DateTime($first['first_date']);
    $firstMonth = (int)$firstDate->format('m');
    $firstYear = (int)$firstDate->format('Y');
    
    $months = [];
    $mesesEs = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    $current = new DateTime();
    $current->setDate($firstYear, $firstMonth, 1);
    
    while ($current <= $lastAvailable) {
        $m = (int)$current->format('m');
        $y = (int)$current->format('Y');
        $months[] = [
            'year' => $y,
            'month' => $m,
            'label' => $mesesEs[$m] . ' ' . $y,
            'value' => sprintf('%04d-%02d', $y, $m)
        ];
        $current->modify('+1 month');
    }
    
    return array_reverse($months); // Más reciente primero
}

/**
 * Verificar si es el último día del mes y generar reporte automáticamente
 * Esta función debe ser llamada por un cron job diario
 * El último día de cada mes genera el reporte de ESE mes (el que está cerrando)
 * @return bool
 */
function checkAndGenerateMonthlyReport() {
    $today = (int)date('d');
    $lastDayOfMonth = (int)date('t'); // Último día del mes actual
    
    // Si es el último día del mes, generar reporte del mes actual (el que está cerrando)
    if ($today === $lastDayOfMonth) {
        return generateClosingMonthReport();
    }
    
    return false;
}

