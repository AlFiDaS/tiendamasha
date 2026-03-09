<?php
/**
 * ============================================
 * HELPER: Sistema de Planes
 * ============================================
 * Define límites por plan y funciones de validación
 * Compatible: PHP 7.4+
 * ============================================
 */

/**
 * Límites por plan
 * - max_stores: máximo de tiendas que puede crear el usuario (plan free = 1)
 * - max_products: máximo de productos por tienda (null = ilimitado)
 * - templates: si tiene acceso a múltiples templates de diseño
 * - custom_domain: si puede usar dominio personalizado
 * - custom_design: si tiene diseño 100% personalizado (platinum)
 */
/** Máximo de tiendas con plan Platinum (cupo limitado) */
define('PLATINUM_MAX_STORES', 2);

function getPlanLimits($plan) {
    $plans = [
        'free' => [
            'name'         => 'Gratuito',
            'price'        => 0,
            'price_label'  => 'Gratis',
            'max_stores'   => 1,
            'max_products' => 4,
            'templates'    => false,
            'custom_domain'=> false,
            'custom_design'=> false,
            'min_duration' => 1,
        ],
        'basic' => [
            'name'         => 'Basic',
            'price'        => 15000,
            'price_label'  => '$15.000/mes',
            'max_stores'   => 1,
            'max_products' => 50,
            'templates'    => false,
            'custom_domain'=> false,
            'custom_design'=> false,
            'min_duration' => 1,
        ],
        'pro' => [
            'name'         => 'Pro',
            'price'        => 30000,
            'price_label'  => '$30.000/mes',
            'max_stores'   => 1,
            'max_products' => 200,
            'templates'    => true,
            'custom_domain'=> false,
            'custom_design'=> false,
            'min_duration' => 1,
        ],
        'platinum' => [
            'name'         => 'Platinum',
            'price'        => 5000000,
            'price_label'  => '$5.000.000/año',
            'max_stores'   => 1,
            'max_products' => null,
            'templates'    => true,
            'custom_domain'=> true,
            'custom_design'=> true,
            'min_duration' => 12,
        ],
    ];
    $plan = strtolower(trim($plan ?? 'free'));
    return $plans[$plan] ?? $plans['free'];
}

/**
 * Verifica si la tienda puede agregar más productos
 * @param int|null $storeId ID de la tienda (en admin puede ser null, usa CURRENT_STORE_ID)
 * @param string|null $plan Plan actual (en admin usa CURRENT_STORE_PLAN)
 * @param int|null $currentProductCount Cantidad actual (si null, se cuenta desde BD)
 * @return array ['allowed' => bool, 'limit' => int|null, 'current' => int, 'error' => string|null]
 */
function canStoreAddProduct($storeId = null, $plan = null, $currentProductCount = null) {
    $plan = $plan ?? (defined('CURRENT_STORE_PLAN') ? CURRENT_STORE_PLAN : 'free');
    $limits = getPlanLimits($plan);
    $max = $limits['max_products'];

    if ($currentProductCount === null) {
        if (defined('LUME_ADMIN') && defined('CURRENT_STORE_ID')) {
            require_once dirname(__FILE__) . '/db.php';
            $row = fetchOne('SELECT COUNT(*) as c FROM products', []);
            $currentProductCount = (int) ($row['c'] ?? 0);
        } elseif (defined('TIENDI_PLATFORM') && $storeId) {
            require_once dirname(__FILE__) . '/platform-db.php';
            $row = platformFetchOne('SELECT COUNT(*) as c FROM products WHERE store_id = :sid', ['sid' => (int) $storeId]);
            $currentProductCount = (int) ($row['c'] ?? 0);
        } else {
            $currentProductCount = 0;
        }
    }

    $currentProductCount = (int) $currentProductCount;

    if ($max === null) {
        return ['allowed' => true, 'limit' => null, 'current' => $currentProductCount, 'error' => null];
    }

    if ($currentProductCount >= $max) {
        return [
            'allowed' => false,
            'limit'   => $max,
            'current' => $currentProductCount,
            'error'   => 'Límite de productos alcanzado (' . $max . '). Actualizá tu plan para agregar más.',
        ];
    }

    return [
        'allowed' => true,
        'limit'   => $max,
        'current' => $currentProductCount,
        'error'   => null,
    ];
}

/**
 * Verifica si hay cupo disponible para el plan Platinum
 * @param int|null $excludeStoreId Excluir esta tienda del conteo (para upgrades)
 * @return array ['available' => bool, 'current' => int, 'max' => int]
 */
function isPlatinumAvailable($excludeStoreId = null) {
    $max = defined('PLATINUM_MAX_STORES') ? PLATINUM_MAX_STORES : 2;
    if (defined('TIENDI_PLATFORM')) {
        $sql = "SELECT COUNT(*) as c FROM stores WHERE (plan = 'platinum' OR subscription_plan = 'platinum')";
        $params = [];
        if ($excludeStoreId) {
            $sql .= " AND id != :sid";
            $params['sid'] = (int) $excludeStoreId;
        }
        $row = platformFetchOne($sql, $params);
    } else {
        $row = ['c' => 0];
    }
    $current = (int) ($row['c'] ?? 0);
    return ['available' => $current < $max, 'current' => $current, 'max' => $max];
}

/**
 * Obtiene las duraciones válidas para un plan
 */
function getValidDurationsForPlan($plan) {
    $limits = getPlanLimits($plan);
    $minDuration = $limits['min_duration'] ?? 1;
    $allDurations = function_exists('getSubscriptionDurations') ? getSubscriptionDurations() : [
        1 => ['months' => 1], 6 => ['months' => 6], 12 => ['months' => 12], 36 => ['months' => 36]
    ];
    $valid = [];
    foreach ($allDurations as $months => $d) {
        if ($months >= $minDuration) {
            $valid[$months] = $d;
        }
    }
    return $valid;
}

/**
 * Obtiene el mensaje de límite para mostrar en UI
 */
function getProductLimitMessage($plan) {
    $limits = getPlanLimits($plan);
    $max = $limits['max_products'];
    if ($max === null) {
        return 'Productos ilimitados';
    }
    return 'Máximo ' . $max . ' productos';
}
