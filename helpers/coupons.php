<?php
/**
 * ============================================
 * HELPER: Sistema de Cupones/Descuentos
 * ============================================
 * Funciones para manejar cupones promocionales
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
 * Validar y aplicar un cupón
 * @param string $code Código del cupón
 * @param float $totalAmount Monto total del carrito
 * @param array $items Items del carrito (para validar categorías/productos)
 * @return array ['valid' => bool, 'discount' => float, 'message' => string, 'coupon' => array|null]
 */
function validateCoupon($code, $totalAmount, $items = []) {
    if (empty($code)) {
        return ['valid' => false, 'discount' => 0, 'message' => 'Código de cupón requerido', 'coupon' => null];
    }
    
    // Buscar cupón
    $sql = "SELECT * FROM coupons WHERE code = :code AND active = 1";
    $coupon = fetchOne($sql, ['code' => strtoupper(trim($code))]);
    
    if (!$coupon) {
        return ['valid' => false, 'discount' => 0, 'message' => 'Código de cupón no válido', 'coupon' => null];
    }
    
    // Verificar fechas de validez
    $now = date('Y-m-d H:i:s');
    if ($coupon['valid_from'] && $coupon['valid_from'] > $now) {
        return ['valid' => false, 'discount' => 0, 'message' => 'Este cupón aún no está disponible', 'coupon' => null];
    }
    
    if ($coupon['valid_until'] && $coupon['valid_until'] < $now) {
        return ['valid' => false, 'discount' => 0, 'message' => 'Este cupón ha expirado', 'coupon' => null];
    }
    
    // Verificar límite de uso
    if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'discount' => 0, 'message' => 'Este cupón ha alcanzado su límite de uso', 'coupon' => null];
    }
    
    // Verificar monto mínimo de compra
    if ($coupon['min_purchase'] > 0 && $totalAmount < $coupon['min_purchase']) {
        $minFormatted = number_format($coupon['min_purchase'], 2, ',', '.');
        return ['valid' => false, 'discount' => 0, 'message' => "El monto mínimo para este cupón es \${$minFormatted}", 'coupon' => null];
    }
    
    // Verificar aplicabilidad (all, category, product)
    if ($coupon['applicable_to'] !== 'all') {
        $applicable = false;
        
        if ($coupon['applicable_to'] === 'category') {
            foreach ($items as $item) {
                if (isset($item['categoria']) && $item['categoria'] === $coupon['category_slug']) {
                    $applicable = true;
                    break;
                }
            }
        } elseif ($coupon['applicable_to'] === 'product') {
            foreach ($items as $item) {
                if (isset($item['id']) && $item['id'] === $coupon['product_id']) {
                    $applicable = true;
                    break;
                }
            }
        }
        
        if (!$applicable) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Este cupón no aplica a los productos en tu carrito', 'coupon' => null];
        }
    }
    
    // Calcular descuento
    $discount = 0;
    if ($coupon['type'] === 'percentage') {
        $discount = ($totalAmount * $coupon['value']) / 100;
    } else {
        $discount = $coupon['value'];
    }
    
    // Aplicar descuento máximo si existe
    if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
        $discount = $coupon['max_discount'];
    }
    
    // El descuento no puede ser mayor al total
    if ($discount > $totalAmount) {
        $discount = $totalAmount;
    }
    
    return [
        'valid' => true,
        'discount' => round($discount, 2),
        'message' => 'Cupón aplicado correctamente',
        'coupon' => $coupon
    ];
}

/**
 * Registrar uso de un cupón
 * @param string $code Código del cupón
 * @return bool
 */
function useCoupon($code) {
    $sql = "UPDATE coupons SET used_count = used_count + 1 WHERE code = :code";
    return executeQuery($sql, ['code' => strtoupper(trim($code))]);
}

/**
 * Obtener todos los cupones
 * @param bool $activeOnly Solo cupones activos
 * @return array
 */
function getAllCoupons($activeOnly = false) {
    $sql = "SELECT * FROM coupons";
    if ($activeOnly) {
        $sql .= " WHERE active = 1";
    }
    $sql .= " ORDER BY created_at DESC";
    
    return fetchAll($sql) ?: [];
}

/**
 * Obtener un cupón por ID
 * @param int $id ID del cupón
 * @return array|null
 */
function getCouponById($id) {
    $sql = "SELECT * FROM coupons WHERE id = :id";
    return fetchOne($sql, ['id' => $id]);
}

/**
 * Crear o actualizar un cupón
 * @param array $couponData Datos del cupón
 * @param int|null $id ID del cupón (null para crear nuevo)
 * @return int|bool ID del cupón creado o true si se actualizó, false en error
 */
function saveCoupon($couponData, $id = null) {
    if ($id === null) {
        // Crear nuevo
        $sql = "INSERT INTO coupons (
            code, type, value, min_purchase, max_discount, usage_limit,
            valid_from, valid_until, active, applicable_to, category_slug, product_id
        ) VALUES (
            :code, :type, :value, :min_purchase, :max_discount, :usage_limit,
            :valid_from, :valid_until, :active, :applicable_to, :category_slug, :product_id
        )";
        
        $params = [
            'code' => strtoupper(trim($couponData['code'] ?? '')),
            'type' => $couponData['type'] ?? 'percentage',
            'value' => $couponData['value'] ?? 0,
            'min_purchase' => $couponData['min_purchase'] ?? 0,
            'max_discount' => $couponData['max_discount'] ?? null,
            'usage_limit' => $couponData['usage_limit'] ?? null,
            'valid_from' => $couponData['valid_from'] ?? null,
            'valid_until' => $couponData['valid_until'] ?? null,
            'active' => isset($couponData['active']) ? (int)$couponData['active'] : 1,
            'applicable_to' => $couponData['applicable_to'] ?? 'all',
            'category_slug' => $couponData['category_slug'] ?? null,
            'product_id' => $couponData['product_id'] ?? null
        ];
        
        if (executeQuery($sql, $params)) {
            return lastInsertId();
        }
    } else {
        // Actualizar existente
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['code', 'type', 'value', 'min_purchase', 'max_discount', 'usage_limit',
                          'valid_from', 'valid_until', 'active', 'applicable_to', 'category_slug', 'product_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($couponData[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $field === 'active' ? (int)$couponData[$field] : $couponData[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE coupons SET " . implode(', ', $fields) . " WHERE id = :id";
        return executeQuery($sql, $params);
    }
    
    return false;
}

/**
 * Eliminar un cupón
 * @param int $id ID del cupón
 * @return bool
 */
function deleteCoupon($id) {
    $sql = "DELETE FROM coupons WHERE id = :id";
    return executeQuery($sql, ['id' => $id]);
}

