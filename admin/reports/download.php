<?php
/**
 * Descargar reporte mensual (Excel CSV o JSON)
 */
require_once '../../config.php';
require_once '../../helpers/reports.php';
require_once '../../helpers/auth.php';

requireAuth();

$filename = $_GET['file'] ?? '';
$format = $_GET['format'] ?? 'excel'; // 'excel' o 'json'

if (empty($filename) || !preg_match('/^reporte-\d{4}-\d{2}\.json$/', $filename)) {
    die('Archivo inválido');
}

$filepath = BASE_PATH . '/reports/' . $filename;
if (!file_exists($filepath)) {
    die('Archivo no encontrado');
}

// Leer reporte JSON
$reportData = json_decode(file_get_contents($filepath), true);
if (!$reportData) {
    die('Error al leer el reporte');
}

// Si se solicita Excel (CSV)
if ($format === 'excel') {
    // Generar nombre de archivo CSV
    $csvFilename = str_replace('.json', '.csv', $filename);
    
    // Abrir output stream
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $csvFilename . '"');
    
    // Agregar BOM para Excel (UTF-8)
    echo "\xEF\xBB\xBF";
    
    // Abrir output
    $output = fopen('php://output', 'w');
    
    // Encabezados principales
    fputcsv($output, ['REPORTE MENSUAL - ' . strtoupper($reportData['month_name']) . ' ' . $reportData['year']], ';');
    fputcsv($output, [], ';');
    
    // Estadísticas generales
    fputcsv($output, ['ESTADÍSTICAS GENERALES'], ';');
    fputcsv($output, ['Total de Pedidos', $reportData['total_orders']], ';');
    fputcsv($output, ['Total de Ventas', '$' . number_format($reportData['total_revenue'], 2, ',', '.')], ';');
    fputcsv($output, ['Ticket Promedio', '$' . number_format($reportData['avg_order_value'], 2, ',', '.')], ';');
    fputcsv($output, ['Pedidos con Cupones', $reportData['orders_with_coupons'] ?? 0], ';');
    fputcsv($output, ['Total Descuentos', '$' . number_format($reportData['total_discounts'] ?? 0, 2, ',', '.')], ';');
    fputcsv($output, [], ';');
    
    // Productos más vendidos
    if (!empty($reportData['top_products'])) {
        fputcsv($output, ['PRODUCTOS MÁS VENDIDOS'], ';');
        fputcsv($output, ['Producto', 'Cantidad Vendida', 'Total Recaudado'], ';');
        foreach ($reportData['top_products'] as $product) {
            fputcsv($output, [
                $product['name'],
                $product['total_sold'],
                '$' . number_format($product['total_revenue'] ?? 0, 2, ',', '.')
            ], ';');
        }
        fputcsv($output, [], ';');
    }
    
    // Detalle de órdenes
    if (!empty($reportData['orders'])) {
        fputcsv($output, ['DETALLE DE ÓRDENES'], ';');
        fputcsv($output, [
            'ID Pedido',
            'Cliente',
            'Email',
            'Teléfono',
            'Total',
            'Método de Pago',
            'Estado',
            'Cupón',
            'Descuento',
            'Fecha'
        ], ';');
        
        foreach ($reportData['orders'] as $order) {
            fputcsv($output, [
                $order['order_id'] ?? $order['id'],
                $order['payer_name'] ?? '',
                $order['payer_email'] ?? '',
                $order['payer_phone'] ?? '',
                '$' . number_format($order['total_amount'] ?? 0, 2, ',', '.'),
                $order['payment_method'] ?? '',
                $order['status'] ?? '',
                $order['coupon_code'] ?? '',
                '$' . number_format($order['discount_amount'] ?? 0, 2, ',', '.'),
                date('d/m/Y H:i', strtotime($order['created_at']))
            ], ';');
        }
    }
    
    fclose($output);
    exit;
}

// Si se solicita JSON (por compatibilidad)
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;

