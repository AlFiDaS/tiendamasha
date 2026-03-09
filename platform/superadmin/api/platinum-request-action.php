<?php
/**
 * API: Gestionar solicitud de plan Platinum (Super Admin)
 * Acciones: contacted, approve, reject
 */
define('TIENDI_PLATFORM', true);
require_once __DIR__ . '/../../../platform-config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../../helpers/superadmin.php';
require_once __DIR__ . '/../../../helpers/plans.php';
requireSuperAdmin();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$requestId = (int) ($input['request_id'] ?? 0);
$action = trim(strtolower($input['action'] ?? ''));
$notes = trim($input['notes'] ?? '');

if (!$requestId || !in_array($action, ['contacted', 'approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

$validFromStatus = ['pending', 'contacted'];
$req = platformFetchOne(
    'SELECT pt.*, s.slug, s.subscription_ends_at, s.subscription_plan
     FROM platinum_requests pt
     JOIN stores s ON s.id = pt.store_id
     WHERE pt.id = :id AND pt.status IN ("pending", "contacted")',
    ['id' => $requestId]
);

if (!$req) {
    echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada o ya procesada']);
    exit;
}

$storeId = (int) $req['store_id'];

if ($action === 'contacted') {
    platformQuery(
        'UPDATE platinum_requests SET status = "contacted", admin_notes = :notes, updated_at = NOW() WHERE id = :id',
        ['notes' => $notes ?: null, 'id' => $requestId]
    );
    echo json_encode(['success' => true, 'action' => 'contacted']);
    exit;
}

if ($action === 'reject') {
    platformQuery(
        'UPDATE platinum_requests SET status = "rejected", admin_notes = :notes, updated_at = NOW() WHERE id = :id',
        ['notes' => $notes ?: null, 'id' => $requestId]
    );
    echo json_encode(['success' => true, 'action' => 'rejected']);
    exit;
}

$platCheck = isPlatinumAvailable($storeId);
if (!$platCheck['available']) {
    echo json_encode([
        'success' => false,
        'error' => 'Cupo Platinum agotado (' . $platCheck['current'] . '/' . $platCheck['max'] . '). Liberá un cupo antes de aprobar.'
    ]);
    exit;
}

$pdo = getPlatformDB();
if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

$durationMonths = 12;
$now = date('Y-m-d H:i:s');
$currentEnds = $req['subscription_ends_at'] ?? null;
$currentPlan = $req['subscription_plan'] ?? null;

$startFrom = $now;
if ($currentEnds && $currentPlan && $currentPlan !== 'free') {
    $endsTs = strtotime($currentEnds);
    if ($endsTs > time()) {
        $startFrom = $currentEnds;
    }
}

$endsAt = date('Y-m-d H:i:s', strtotime($startFrom . ' +' . $durationMonths . ' months'));

try {
    $pdo->beginTransaction();

    try {
        $pdo->prepare('UPDATE products SET visible = 1, auto_hidden_by_plan = 0 WHERE store_id = ? AND auto_hidden_by_plan = 1')
            ->execute([$storeId]);
    } catch (Throwable $e) {
        $pdo->prepare('UPDATE products SET visible = 1 WHERE store_id = ?')->execute([$storeId]);
    }

    try {
        $pdo->exec("ALTER TABLE stores MODIFY COLUMN plan VARCHAR(20) NOT NULL DEFAULT 'free'");
        $pdo->exec("ALTER TABLE stores MODIFY COLUMN subscription_plan VARCHAR(20) DEFAULT NULL");
    } catch (Throwable $e) {
        // already VARCHAR or not needed
    }

    $pdo->prepare('UPDATE stores SET plan = ?, subscription_plan = ?, subscription_ends_at = ? WHERE id = ?')
        ->execute(['platinum', 'platinum', $endsAt, $storeId]);

    platformQuery(
        'UPDATE platinum_requests SET status = "approved", admin_notes = :notes, updated_at = NOW() WHERE id = :id',
        ['notes' => $notes ?: null, 'id' => $requestId]
    );

    $pdo->commit();

    echo json_encode(['success' => true, 'action' => 'approved', 'subscription_ends_at' => $endsAt]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[PlatinumApprove] Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al aplicar el plan: ' . $e->getMessage()]);
}
