<?php
/**
 * Sistema de suscripciones y planes
 * - Plan gratuito vs planes pagos (basic, pro, platinum)
 * - Renovación mensual, 3 días de tolerancia
 * - Auto-ocultar productos al bajar a free, restaurar al volver a pagar
 */

require_once dirname(__FILE__) . '/plans.php';

/** Días de tolerancia tras vencer el período */
define('SUBSCRIPTION_GRACE_DAYS', 3);

/**
 * Obtiene el plan efectivo de la tienda (considerando suscripción vencida)
 * @return array ['plan' => string, 'label' => string, 'subscription_ends_at' => ?string, 'status' => 'active'|'grace'|'expired'|'free']
 */
function getStoreSubscription($storeId = null) {
    $storeId = $storeId ?? (defined('CURRENT_STORE_ID') ? CURRENT_STORE_ID : 0);
    if ($storeId < 1) {
        return ['plan' => 'free', 'label' => 'Plan Gratuito', 'subscription_ends_at' => null, 'status' => 'free'];
    }

    $store = fetchStoreForSubscription($storeId);
    if (!$store) {
        return ['plan' => 'free', 'label' => 'Plan Gratuito', 'subscription_ends_at' => null, 'status' => 'free'];
    }

    $plan = $store['plan'] ?? 'free';
    $subscriptionPlan = $store['subscription_plan'] ?? null;
    $endsAt = $store['subscription_ends_at'] ?? null;

    // Si no hay plan de suscripción activo, usar stores.plan (puede ser basic/pro) o free
    if (empty($subscriptionPlan) || $subscriptionPlan === 'free') {
        $effectivePlan = in_array($plan, ['basic', 'pro', 'platinum']) ? $plan : 'free';
        $limits = getPlanLimits($effectivePlan);
        $label = $limits['name'] ?? ucfirst($effectivePlan);
        return [
            'plan'       => $effectivePlan,
            'label'      => $effectivePlan === 'free' ? 'Plan Gratuito' : ('Plan ' . $label),
            'subscription_ends_at' => null,
            'status'     => 'free',
        ];
    }

    $limits = getPlanLimits($subscriptionPlan);
    $label = $limits['name'] ?? ucfirst($subscriptionPlan);

    if (empty($endsAt)) {
        return [
            'plan'       => $subscriptionPlan,
            'label'      => 'Plan ' . $label,
            'subscription_ends_at' => null,
            'status'     => 'active',
        ];
    }

    $endsTs = strtotime($endsAt);
    $graceEndsTs = $endsTs + (SUBSCRIPTION_GRACE_DAYS * 86400);
    $now = time();

    if ($now <= $endsTs) {
        $status = 'active';
    } elseif ($now <= $graceEndsTs) {
        $status = 'grace';
    } else {
        $status = 'expired';
        // Debería haberse aplicado downgrade
        return [
            'plan'       => 'free',
            'label'      => 'Plan Gratuito',
            'subscription_ends_at' => $endsAt,
            'status'     => 'expired',
        ];
    }

    return [
        'plan'       => $subscriptionPlan,
        'label'      => 'Plan ' . $label,
        'subscription_ends_at' => $endsAt,
        'status'     => $status,
    ];
}

/**
 * Obtiene datos de la tienda para suscripción (stores está en la misma BD que products)
 */
function fetchStoreForSubscription($storeId) {
    if (defined('TIENDI_PLATFORM')) {
        require_once dirname(__FILE__) . '/platform-db.php';
        return platformFetchOne(
            'SELECT id, plan, subscription_ends_at, subscription_plan FROM stores WHERE id = :sid',
            ['sid' => (int) $storeId]
        );
    }
    if (!function_exists('getDB')) {
        require_once dirname(__FILE__) . '/db.php';
    }
    $pdo = getDB();
    if (!$pdo) return null;
    try {
        $stmt = $pdo->prepare('SELECT id, plan, subscription_ends_at, subscription_plan FROM stores WHERE id = :sid');
        $stmt->execute(['sid' => (int) $storeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        try {
            $stmt = $pdo->prepare('SELECT id, plan FROM stores WHERE id = :sid');
            $stmt->execute(['sid' => (int) $storeId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['subscription_ends_at'] = null;
                $row['subscription_plan'] = null;
            }
            return $row;
        } catch (Throwable $e2) {
            return null;
        }
    }
}

/**
 * Verifica y aplica downgrade si la suscripción venció (llamar en cada carga del admin)
 */
function checkAndApplySubscriptionDowngrade($storeId = null) {
    $storeId = $storeId ?? (defined('CURRENT_STORE_ID') ? CURRENT_STORE_ID : 0);
    if ($storeId < 1) return;

    $store = fetchStoreForSubscription($storeId);
    if (!$store || empty($store['subscription_plan']) || $store['subscription_plan'] === 'free') return;

    $endsAt = $store['subscription_ends_at'] ?? null;
    if (empty($endsAt)) return;

    $endsTs = strtotime($endsAt);
    $graceEndsTs = $endsTs + (SUBSCRIPTION_GRACE_DAYS * 86400);
    if (time() <= $graceEndsTs) return;

    applyPlanDowngrade($storeId);
}

/**
 * Aplica downgrade a plan free: oculta productos que exceden el límite
 */
function applyPlanDowngrade($storeId) {
    $storeId = (int) $storeId;
    if ($storeId < 1) return;

    $pdo = getDB();
    if (!$pdo) return;

    $limits = getPlanLimits('free');
    $maxProducts = $limits['max_products'] ?? 4;

    // 1. Actualizar stores: plan=free, subscription_plan=null, subscription_ends_at=null
    try {
        $pdo->prepare('UPDATE stores SET plan = "free", subscription_plan = NULL, subscription_ends_at = NULL WHERE id = ?')->execute([$storeId]);
    } catch (Throwable $e) {
        try {
            $pdo->prepare('UPDATE stores SET plan = "free" WHERE id = ?')->execute([$storeId]);
        } catch (Throwable $e2) {
            error_log('[Subscription] Error updating store: ' . $e2->getMessage());
            return;
        }
    }

    // 2. Productos visibles que NO son destacados: marcar auto_hidden y ocultar
    // Mantener visibles solo los destacados (hasta max_products si hay muchos)
    $stmt = $pdo->prepare('
        SELECT id FROM products 
        WHERE store_id = ? AND visible = 1 AND (destacado = 0 OR destacado IS NULL)
        ORDER BY destacado DESC, orden ASC, id ASC
    ');
    $stmt->execute([$storeId]);
    $toHide = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($toHide)) {
        $placeholders = implode(',', array_fill(0, count($toHide), '?'));
        $params = array_merge($toHide, [$storeId]);
        try {
            $pdo->prepare("UPDATE products SET visible = 0, auto_hidden_by_plan = 1 WHERE id IN ($placeholders) AND store_id = ?")->execute($params);
        } catch (Throwable $e) {
            $pdo->prepare("UPDATE products SET visible = 0 WHERE id IN ($placeholders) AND store_id = ?")->execute($params);
        }
    }
}

/**
 * Al activar/renovar plan pago: restaurar productos auto-ocultos
 */
function applyPlanUpgrade($storeId, $plan) {
    $storeId = (int) $storeId;
    if ($storeId < 1) return;

    $pdo = getDB();
    if (!$pdo) return;

    // Restaurar visible=1 solo para productos con auto_hidden_by_plan=1 (no los que el usuario ocultó manualmente)
    try {
        $pdo->prepare('UPDATE products SET visible = 1, auto_hidden_by_plan = 0 WHERE store_id = ? AND auto_hidden_by_plan = 1')->execute([$storeId]);
    } catch (Throwable $e) {
        $pdo->prepare('UPDATE products SET visible = 1 WHERE store_id = ?')->execute([$storeId]);
    }

    // Actualizar stores
    $endsAt = date('Y-m-d H:i:s', strtotime('+1 month'));
    try {
        $pdo->prepare('UPDATE stores SET plan = ?, subscription_plan = ?, subscription_ends_at = ? WHERE id = ?')
            ->execute([$plan, $plan, $endsAt, $storeId]);
    } catch (Throwable $e) {
        try {
            $pdo->prepare('UPDATE stores SET plan = ? WHERE id = ?')->execute([$plan, $storeId]);
        } catch (Throwable $e2) {
            error_log('[Subscription] Error updating store on upgrade: ' . $e2->getMessage());
        }
    }
}
