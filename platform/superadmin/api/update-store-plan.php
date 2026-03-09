<?php
/**
 * API: Actualizar plan de una tienda (Super Admin)
 */
define('TIENDI_PLATFORM', true);
require_once __DIR__ . '/../../../platform-config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../../helpers/superadmin.php';
requireSuperAdmin();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$storeId = (int) ($input['store_id'] ?? 0);
$plan = trim(strtolower($input['plan'] ?? ''));

$validPlans = ['free', 'basic', 'pro', 'platinum'];
if (!$storeId || !in_array($plan, $validPlans, true)) {
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos. Planes válidos: free, basic, pro, platinum']);
    exit;
}

require_once __DIR__ . '/../../../helpers/plans.php';

if ($plan === 'platinum') {
    $platCheck = isPlatinumAvailable($storeId);
    if (!$platCheck['available']) {
        echo json_encode([
            'success' => false,
            'error' => 'Cupo Platinum agotado (' . $platCheck['current'] . '/' . $platCheck['max'] . '). No se pueden asignar más tiendas a este plan.'
        ]);
        exit;
    }
}

$store = platformFetchOne('SELECT id, plan FROM stores WHERE id = :id', ['id' => $storeId]);
if (!$store) {
    echo json_encode(['success' => false, 'error' => 'Tienda no encontrada']);
    exit;
}

$ok = platformQuery(
    'UPDATE stores SET plan = :plan, subscription_plan = :plan WHERE id = :id',
    ['plan' => $plan, 'id' => $storeId]
);
if (!$ok) {
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el plan']);
    exit;
}

echo json_encode(['success' => true, 'plan' => $plan]);
