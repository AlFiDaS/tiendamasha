<?php
/**
 * ============================================
 * HELPER: Estadísticas y Dashboard
 * ============================================
 * Funciones para obtener estadísticas de ventas, productos, etc.
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

/**
 * Obtener ventas del día
 * @param string $date Fecha en formato Y-m-d (opcional, por defecto hoy)
 * @return array
 */
function getSalesToday($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value
            FROM orders 
            WHERE DATE(created_at) = :date 
            AND status IN ('approved', 'a_confirmar')";
    
    $result = fetchOne($sql, ['date' => $date]);
    
    return [
        'date' => $date,
        'total_orders' => (int)($result['total_orders'] ?? 0),
        'total_revenue' => (float)($result['total_revenue'] ?? 0),
        'avg_order_value' => (float)($result['avg_order_value'] ?? 0)
    ];
}

/**
 * Obtener ventas del mes
 * @param int $month Mes (1-12), opcional
 * @param int $year Año, opcional
 * @return array
 */
function getSalesThisMonth($month = null, $year = null) {
    if ($month === null) {
        $month = (int)date('m');
    }
    if ($year === null) {
        $year = (int)date('Y');
    }
    
    $sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                DAY(created_at) as day
            FROM orders 
            WHERE MONTH(created_at) = :month 
            AND YEAR(created_at) = :year
            AND status IN ('approved', 'a_confirmar')
            GROUP BY DAY(created_at)
            ORDER BY day ASC";
    
    $dailySales = fetchAll($sql, ['month' => $month, 'year' => $year]);
    
    // Calcular totales
    $sqlTotal = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value
                FROM orders 
                WHERE MONTH(created_at) = :month 
                AND YEAR(created_at) = :year
                AND status IN ('approved', 'a_confirmar')";
    
    $totals = fetchOne($sqlTotal, ['month' => $month, 'year' => $year]);
    
    return [
        'month' => $month,
        'year' => $year,
        'total_orders' => (int)($totals['total_orders'] ?? 0),
        'total_revenue' => (float)($totals['total_revenue'] ?? 0),
        'avg_order_value' => (float)($totals['avg_order_value'] ?? 0),
        'daily_sales' => $dailySales ?: []
    ];
}

/**
 * Obtener productos más vendidos
 * @param int $limit Límite de resultados (default: 10)
 * @param string $period Periodo: 'today', 'week', 'month', 'all' (default: 'month')
 * @return array
 */
function getTopSellingProducts($limit = 10, $period = 'month') {
    $dateCondition = '';
    $params = [];
    
    switch ($period) {
        case 'today':
            $dateCondition = "AND DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $dateCondition = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            break;
        case 'all':
        default:
            $dateCondition = "";
            break;
    }
    
    // Obtener todas las órdenes con items
    $sql = "SELECT id, items, total_amount 
            FROM orders 
            WHERE status IN ('approved', 'a_confirmar')
            {$dateCondition}";
    
    $orders = fetchAll($sql, $params);
    
    // Procesar items en PHP (más compatible que JSON_TABLE)
    $productStats = [];
    
    foreach ($orders as $order) {
        $items = json_decode($order['items'] ?? '[]', true);
        
        if (!is_array($items)) {
            continue;
        }
        
        foreach ($items as $item) {
            $slug = $item['slug'] ?? '';
            $quantity = (int)($item['cantidad'] ?? 0);
            $price = $item['price'] ?? '0';
            
            if (empty($slug) || $quantity <= 0) {
                continue;
            }
            
            // Extraer precio numérico
            $priceNum = (float)str_replace(['$', '.', ','], '', $price);
            
            if (!isset($productStats[$slug])) {
                $productStats[$slug] = [
                    'slug' => $slug,
                    'total_sold' => 0,
                    'total_revenue' => 0
                ];
            }
            
            $productStats[$slug]['total_sold'] += $quantity;
            $productStats[$slug]['total_revenue'] += $priceNum * $quantity;
        }
    }
    
    // Obtener información de productos
    $topProducts = [];
    foreach ($productStats as $slug => $stats) {
        $productSql = "SELECT id, name, slug, image FROM products WHERE slug = :slug";
        $product = fetchOne($productSql, ['slug' => $slug]);
        
        if ($product) {
            $topProducts[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'image' => $product['image'],
                'total_sold' => $stats['total_sold'],
                'total_revenue' => $stats['total_revenue']
            ];
        }
    }
    
    // Ordenar por cantidad vendida
    usort($topProducts, function($a, $b) {
        return $b['total_sold'] - $a['total_sold'];
    });
    
    // Limitar resultados
    return array_slice($topProducts, 0, $limit);
}

/**
 * Obtener estadísticas generales del dashboard
 * @return array
 */
function getDashboardStats() {
    // Ventas del día
    $today = getSalesToday();
    
    // Ventas del mes
    $month = getSalesThisMonth();
    
    // Órdenes pendientes
    $sqlPending = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
    $pending = fetchOne($sqlPending);
    
    // Órdenes a confirmar
    $sqlConfirm = "SELECT COUNT(*) as count FROM orders WHERE status = 'a_confirmar'";
    $toConfirm = fetchOne($sqlConfirm);
    
    // Productos con stock bajo (solo los que tienen stock limitado)
    $sqlLowStock = "SELECT COUNT(*) as count FROM products WHERE stock IS NOT NULL AND stock > 0 AND stock <= stock_minimo";
    $lowStock = fetchOne($sqlLowStock);
    
    // Productos sin stock (solo los que tienen stock limitado)
    $sqlNoStock = "SELECT COUNT(*) as count FROM products WHERE stock IS NOT NULL AND stock = 0";
    $noStock = fetchOne($sqlNoStock);
    
    // Total de productos
    $sqlTotalProducts = "SELECT COUNT(*) as count FROM products";
    $totalProducts = fetchOne($sqlTotalProducts);
    
    // Productos más vendidos
    $topProducts = getTopSellingProducts(5, 'month');
    
    return [
        'today' => $today,
        'month' => $month,
        'pending_orders' => (int)($pending['count'] ?? 0),
        'orders_to_confirm' => (int)($toConfirm['count'] ?? 0),
        'low_stock_products' => (int)($lowStock['count'] ?? 0),
        'no_stock_products' => (int)($noStock['count'] ?? 0),
        'total_products' => (int)($totalProducts['count'] ?? 0),
        'top_products' => $topProducts
    ];
}

/**
 * Obtener ventas por día de la semana (últimos 7 días)
 * @return array
 */
function getSalesByDay() {
    $sql = "SELECT 
                DAYNAME(created_at) as day_name,
                DAYOFWEEK(created_at) as day_num,
                DATE(created_at) as date,
                COUNT(*) as orders,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            AND status IN ('approved', 'a_confirmar')
            GROUP BY DATE(created_at), DAYNAME(created_at), DAYOFWEEK(created_at)
            ORDER BY date ASC";
    
    return fetchAll($sql) ?: [];
}

