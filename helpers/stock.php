<?php
/**
 * ============================================
 * HELPER: Gestión de Stock
 * ============================================
 * Funciones para manejar stock de productos
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
 * Descontar stock de un producto
 * @param string $productId ID del producto
 * @param int $quantity Cantidad a descontar
 * @param int $orderId ID de la orden (opcional, para registro)
 * @return array ['success' => bool, 'new_stock' => int|null, 'error' => string|null, 'unlimited' => bool]
 */
function decreaseStock($productId, $quantity, $orderId = null) {
    // Obtener stock actual
    $sql = "SELECT stock FROM products WHERE id = :id";
    $product = fetchOne($sql, ['id' => $productId]);
    
    if (!$product) {
        return ['success' => false, 'new_stock' => 0, 'error' => 'Producto no encontrado', 'unlimited' => false];
    }
    
    // Si stock es NULL, es ilimitado - no se descuenta
    if ($product['stock'] === null) {
        // Registrar movimiento para auditoría pero no descontar
        if ($orderId) {
            $movementSql = "INSERT INTO stock_movements (product_id, type, quantity, order_id, notes) 
                           VALUES (:product_id, 'sale', :quantity, :order_id, CONCAT('Venta - Stock Ilimitado - Orden #', :order_id))";
            executeQuery($movementSql, [
                'product_id' => $productId,
                'quantity' => -$quantity,
                'order_id' => $orderId
            ]);
        }
        return ['success' => true, 'new_stock' => null, 'error' => null, 'unlimited' => true];
    }
    
    $currentStock = (int)$product['stock'];
    $newStock = max(0, $currentStock - $quantity);
    
    // Actualizar stock
    $updateSql = "UPDATE products SET stock = :stock WHERE id = :id";
    $result = executeQuery($updateSql, ['stock' => $newStock, 'id' => $productId]);
    
    if ($result) {
        // Registrar movimiento de stock
        if ($orderId) {
            $movementSql = "INSERT INTO stock_movements (product_id, type, quantity, order_id, notes) 
                           VALUES (:product_id, 'sale', :quantity, :order_id, CONCAT('Venta - Orden #', :order_id))";
            executeQuery($movementSql, [
                'product_id' => $productId,
                'quantity' => -$quantity,
                'order_id' => $orderId
            ]);
        }
        
        return ['success' => true, 'new_stock' => $newStock, 'error' => null, 'unlimited' => false];
    }
    
    return ['success' => false, 'new_stock' => $currentStock, 'error' => 'Error al actualizar stock', 'unlimited' => false];
}

/**
 * Restaurar stock (cuando se cancela una orden)
 * @param string $productId ID del producto
 * @param int $quantity Cantidad a restaurar
 * @param int $orderId ID de la orden
 * @return array ['success' => bool, 'new_stock' => int, 'error' => string|null]
 */
function restoreStock($productId, $quantity, $orderId) {
    // Obtener stock actual
    $sql = "SELECT stock FROM products WHERE id = :id";
    $product = fetchOne($sql, ['id' => $productId]);
    
    if (!$product) {
        return ['success' => false, 'new_stock' => 0, 'error' => 'Producto no encontrado'];
    }
    
    // Si stock es NULL (ilimitado), no se descontó nada - no hay que restaurar
    if ($product['stock'] === null) {
        return ['success' => true, 'new_stock' => null, 'error' => null];
    }
    
    $currentStock = (int)$product['stock'];
    $newStock = $currentStock + $quantity;
    
    // Actualizar stock
    $updateSql = "UPDATE products SET stock = :stock WHERE id = :id";
    $result = executeQuery($updateSql, ['stock' => $newStock, 'id' => $productId]);
    
    if ($result) {
        // Registrar movimiento de stock
        $movementSql = "INSERT INTO stock_movements (product_id, type, quantity, order_id, notes) 
                       VALUES (:product_id, 'return', :quantity, :order_id, CONCAT('Devolución - Orden #', :order_id))";
        executeQuery($movementSql, [
            'product_id' => $productId,
            'quantity' => $quantity,
            'order_id' => $orderId
        ]);
        
        return ['success' => true, 'new_stock' => $newStock, 'error' => null];
    }
    
    return ['success' => false, 'new_stock' => $currentStock, 'error' => 'Error al actualizar stock'];
}

/**
 * Descontar stock cuando una orden es aprobada (solo si no se descontó antes)
 * @param int $orderId ID de la orden
 * @return array ['success' => bool, 'processed' => bool, 'errors' => array]
 */
function processOrderStockOnApproval($orderId) {
    if (!function_exists('fetchOne')) {
        require_once __DIR__ . '/db.php';
    }
    
    // Verificar si ya se procesó el stock para esta orden
    $existing = fetchOne("SELECT id FROM stock_movements WHERE order_id = :oid AND type = 'sale' LIMIT 1", ['oid' => $orderId]);
    if ($existing) {
        return ['success' => true, 'processed' => false, 'errors' => []];
    }
    
    $order = fetchOne("SELECT items FROM orders WHERE id = :id", ['id' => $orderId]);
    if (!$order || empty($order['items'])) {
        return ['success' => false, 'processed' => false, 'errors' => ['Orden no encontrada o sin items']];
    }
    
    $items = is_array($order['items']) ? $order['items'] : json_decode($order['items'], true);
    $errors = [];
    
    if ($items && is_array($items)) {
        foreach ($items as $item) {
            $slug = $item['slug'] ?? '';
            $quantity = (int)($item['cantidad'] ?? $item['quantity'] ?? 0);
            if (empty($slug) || $quantity <= 0) continue;
            
            $product = fetchOne("SELECT id FROM products WHERE slug = :slug", ['slug' => $slug]);
            if ($product && isset($product['id'])) {
                $result = decreaseStock($product['id'], $quantity, $orderId);
                if (!$result['success']) {
                    $errors[] = ($result['error'] ?? 'Error') . ' para ' . $slug;
                }
            }
        }
    }
    
    return ['success' => empty($errors), 'processed' => true, 'errors' => $errors];
}

/**
 * Restaurar stock cuando una orden aprobada pasa a cualquier otro estado
 * Usa stock_movements (ventas) para obtener las cantidades exactas descontadas
 * y restaurar correctamente, sumando por producto por si hay múltiples líneas
 * @param int $orderId ID de la orden
 * @return array ['success' => bool, 'processed' => bool, 'errors' => array]
 */
function processOrderStockOnRejection($orderId) {
    if (!function_exists('fetchOne')) {
        require_once __DIR__ . '/db.php';
    }
    
    // Verificar si ya se restauró el stock para esta orden
    $existing = fetchOne("SELECT id FROM stock_movements WHERE order_id = :oid AND type = 'return' LIMIT 1", ['oid' => $orderId]);
    if ($existing) {
        return ['success' => true, 'processed' => false, 'errors' => []];
    }
    
    // Obtener TODAS las ventas de esta orden, agrupadas por producto (sumar cantidades)
    $sales = fetchAll(
        "SELECT product_id, SUM(ABS(quantity)) as total_quantity 
         FROM stock_movements 
         WHERE order_id = :oid AND type = 'sale' 
         GROUP BY product_id",
        ['oid' => $orderId]
    );
    
    if (!$sales || !is_array($sales)) {
        // Si no hay movimientos de venta, no se descontó nada - no restaurar
        return ['success' => true, 'processed' => false, 'errors' => []];
    }
    
    $errors = [];
    foreach ($sales as $sale) {
        $productId = $sale['product_id'] ?? '';
        $quantity = (int)($sale['total_quantity'] ?? 0);
        if (empty($productId) || $quantity <= 0) continue;
        
        $result = restoreStock($productId, $quantity, $orderId);
        if (!$result['success']) {
            $errors[] = ($result['error'] ?? 'Error') . ' para producto ' . $productId;
        }
    }
    
    return ['success' => empty($errors), 'processed' => true, 'errors' => $errors];
}


/**
 * Verificar disponibilidad de stock para una orden
 * @param array $items Array de items con 'slug' y 'cantidad'
 * @return array ['available' => bool, 'unavailable' => array, 'errors' => array]
 */
function checkStockAvailability($items) {
    $unavailable = [];
    $errors = [];
    
    foreach ($items as $item) {
        $slug = $item['slug'] ?? '';
        $quantity = (int)($item['cantidad'] ?? 0);
        
        if (empty($slug) || $quantity <= 0) {
            continue;
        }
        
        // Buscar producto por slug
        $sql = "SELECT id, name, stock FROM products WHERE slug = :slug";
        $product = fetchOne($sql, ['slug' => $slug]);
        
        if (!$product) {
            $errors[] = "Producto no encontrado: {$slug}";
            continue;
        }
        
        // Si stock es NULL, es ilimitado - siempre disponible
        if ($product['stock'] === null) {
            continue;
        }
        
        $availableStock = (int)$product['stock'];
        
        // Solo verificar si el stock es limitado (no NULL) y es menor a la cantidad solicitada
        if ($availableStock < $quantity) {
            $unavailable[] = [
                'product' => $product['name'],
                'slug' => $slug,
                'requested' => $quantity,
                'available' => $availableStock
            ];
        }
    }
    
    return [
        'available' => empty($unavailable),
        'unavailable' => $unavailable,
        'errors' => $errors
    ];
}

/**
 * Obtener productos con stock bajo
 * @param int $threshold Umbral (opcional, usa stock_minimo del producto si no se especifica)
 * @return array
 */
function getLowStockProducts($threshold = null) {
    // Solo productos con stock limitado (no NULL) y mayor a 0
    if ($threshold === null) {
        $sql = "SELECT id, name, slug, stock, stock_minimo, categoria 
                FROM products 
                WHERE stock IS NOT NULL AND stock > 0 AND stock <= stock_minimo
                ORDER BY stock ASC";
    } else {
        $sql = "SELECT id, name, slug, stock, stock_minimo, categoria 
                FROM products 
                WHERE stock IS NOT NULL AND stock > 0 AND stock <= :threshold
                ORDER BY stock ASC";
    }
    
    $params = $threshold !== null ? ['threshold' => $threshold] : [];
    return fetchAll($sql, $params) ?: [];
}

/**
 * Obtener productos sin stock
 * @return array
 */
function getOutOfStockProducts() {
    // Solo productos con stock limitado (no NULL) que estén en 0
    $sql = "SELECT id, name, slug, stock, categoria 
            FROM products 
            WHERE stock IS NOT NULL AND stock = 0
            ORDER BY name ASC";
    
    return fetchAll($sql) ?: [];
}

/**
 * Actualizar stock de un producto
 * @param string $productId ID del producto
 * @param int $newStock Nueva cantidad de stock
 * @param string $notes Notas sobre el cambio (opcional)
 * @return bool
 */
function updateProductStock($productId, $newStock, $notes = null) {
    // Obtener stock actual
    $sql = "SELECT stock FROM products WHERE id = :id";
    $product = fetchOne($sql, ['id' => $productId]);
    
    if (!$product) {
        return false;
    }
    
    $oldStock = (int)$product['stock'];
    $difference = $newStock - $oldStock;
    
    // Actualizar stock
    $updateSql = "UPDATE products SET stock = :stock WHERE id = :id";
    $result = executeQuery($updateSql, ['stock' => $newStock, 'id' => $productId]);
    
    if ($result && $difference != 0) {
        // Registrar movimiento de stock
        $type = $difference > 0 ? 'restock' : 'adjustment';
        $movementSql = "INSERT INTO stock_movements (product_id, type, quantity, notes) 
                       VALUES (:product_id, :type, :quantity, :notes)";
        executeQuery($movementSql, [
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $difference,
            'notes' => $notes ?? 'Ajuste manual de stock'
        ]);
    }
    
    return $result;
}

