<?php
/**
 * API: Aprobar o rechazar solicitud de pago (Super Admin)
 * Al aprobar: activa el plan en la tienda por la duración indicada
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
requireSuperAdmin();

function deleteProofFile($proofImage) {
    if (empty($proofImage)) return;
    $basePath = dirname(__DIR__, 3);
    $filePath = $basePath . $proofImage;
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
    $publicPath = $basePath . '/public' . $proofImage;
    if (file_exists($publicPath)) {
        @unlink($publicPath);
    }
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$requestId = (int) ($input['payment_request_id'] ?? 0);
$action = trim(strtolower($input['action'] ?? ''));
$notes = trim($input['notes'] ?? '');

if (!$requestId || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

$req = platformFetchOne(
    'SELECT pr.*, s.slug, s.subscription_ends_at, s.subscription_plan
     FROM payment_requests pr
     JOIN stores s ON s.id = pr.store_id
     WHERE pr.id = :id AND pr.status = :status',
    ['id' => $requestId, 'status' => 'paid']
);

if (!$req) {
    echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada o ya procesada']);
    exit;
}

$storeId = (int) $req['store_id'];
$plan = $req['plan'];
$durationMonths = (int) $req['duration_months'];
$proofImage = $req['proof_image'] ?? null;

if ($action === 'approve' && $plan === 'platinum') {
    require_once __DIR__ . '/../../../helpers/plans.php';
    $platCheck = isPlatinumAvailable($storeId);
    if (!$platCheck['available']) {
        echo json_encode([
            'success' => false,
            'error' => 'No se puede aprobar: cupo Platinum agotado (' . $platCheck['current'] . '/' . $platCheck['max'] . '). Rechazá esta solicitud o liberá un cupo.'
        ]);
        exit;
    }
}

if ($action === 'reject') {
    deleteProofFile($proofImage);
    platformQuery(
        'UPDATE payment_requests SET status = "rejected", notes = :notes, approved_at = NOW(), approved_by = :uid, proof_image = NULL WHERE id = :id',
        ['notes' => $notes, 'uid' => $_SESSION['platform_user_id'] ?? null, 'id' => $requestId]
    );
    echo json_encode(['success' => true, 'action' => 'rejected']);
    exit;
}

// Aprobar: aplicar upgrade con duración
$pdo = getPlatformDB();
if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

// Calcular subscription_ends_at
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

    // 1. Restaurar productos auto-ocultos
    try {
        $pdo->prepare('UPDATE products SET visible = 1, auto_hidden_by_plan = 0 WHERE store_id = ? AND auto_hidden_by_plan = 1')
            ->execute([$storeId]);
    } catch (Throwable $e) {
        $pdo->prepare('UPDATE products SET visible = 1 WHERE store_id = ?')->execute([$storeId]);
    }

    // 2. Actualizar stores
    $pdo->prepare('UPDATE stores SET plan = ?, subscription_plan = ?, subscription_ends_at = ? WHERE id = ?')
        ->execute([$plan, $plan, $endsAt, $storeId]);

    // 3. Marcar payment_request como aprobado y limpiar comprobante
    platformQuery(
        'UPDATE payment_requests SET status = "approved", notes = :notes, approved_at = NOW(), approved_by = :uid, proof_image = NULL WHERE id = :id',
        ['notes' => $notes, 'uid' => $_SESSION['platform_user_id'] ?? null, 'id' => $requestId]
    );

    $pdo->commit();

    deleteProofFile($proofImage);

    echo json_encode(['success' => true, 'action' => 'approved', 'subscription_ends_at' => $endsAt]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[ApprovePayment] Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al aplicar el plan: ' . $e->getMessage()]);
}
