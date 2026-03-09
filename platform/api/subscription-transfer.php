<?php
/**
 * API: Crear solicitud de pago por transferencia bancaria
 * Recibe: store_id, plan, duration_months, payer_name + archivo comprobante (proof_image)
 * Retorna: success, payment_request_id
 */
define('TIENDI_PLATFORM', true);
require_once dirname(__DIR__, 2) . '/platform-config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

require_once dirname(__DIR__, 2) . '/helpers/subscription-pricing.php';

$storeId = (int) ($_POST['store_id'] ?? 0);
$plan = preg_replace('/[^a-z]/', '', strtolower($_POST['plan'] ?? ''));
$durationMonths = (int) ($_POST['duration_months'] ?? 1);
$payerName = trim($_POST['payer_name'] ?? '');

$validPlans = ['basic', 'pro', 'platinum'];
$validDurations = [1, 6, 12, 36];
if (!$storeId || !in_array($plan, $validPlans) || !in_array($durationMonths, $validDurations)) {
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

$planLimits = getPlanLimits($plan);
$minDuration = $planLimits['min_duration'] ?? 1;
if ($durationMonths < $minDuration) {
    echo json_encode(['success' => false, 'error' => 'El plan ' . ucfirst($plan) . ' requiere un mínimo de ' . $minDuration . ' meses']);
    exit;
}

if ($plan === 'platinum') {
    $platCheck = isPlatinumAvailable($storeId);
    if (!$platCheck['available']) {
        echo json_encode(['success' => false, 'error' => 'El cupo de Platinum está agotado (' . $platCheck['current'] . '/' . $platCheck['max'] . ')']);
        exit;
    }
}

if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Debés subir el comprobante de transferencia']);
    exit;
}

$store = platformFetchOne('SELECT id, slug, owner_id FROM stores WHERE id = :id', ['id' => $storeId]);
if (!$store) {
    echo json_encode(['success' => false, 'error' => 'Tienda no encontrada']);
    exit;
}

$pricing = getSubscriptionPrice($plan, $durationMonths);
if ($pricing['total'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'Plan o precio inválido']);
    exit;
}

// Validar y subir comprobante
$file = $_FILES['proof_image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'El comprobante debe ser JPG, PNG o WebP']);
    exit;
}
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'El comprobante no puede superar 5MB']);
    exit;
}

$ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mimeType] ?? 'jpg';

// Directorio de comprobantes de planes
$proofsDir = dirname(__DIR__, 2) . '/images/plan-proofs';
if (!is_dir($proofsDir)) {
    @mkdir($proofsDir, 0755, true);
}

$filename = 'transfer_' . $storeId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destination = $proofsDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'error' => 'Error al subir el comprobante']);
    exit;
}
@chmod($destination, 0644);

$proofPath = '/images/plan-proofs/' . $filename;

// Crear registro payment_request como "paid" (transferencia ya hecha, esperando aprobación)
$ok = platformQuery(
    'INSERT INTO payment_requests (store_id, plan, duration_months, amount, status, payment_method, proof_image, payer_name, paid_at)
     VALUES (:sid, :plan, :dur, :amt, :status, :method, :proof, :name, NOW())',
    [
        'sid'    => $storeId,
        'plan'   => $plan,
        'dur'    => $durationMonths,
        'amt'    => $pricing['total'],
        'status' => 'paid',
        'method' => 'transferencia',
        'proof'  => $proofPath,
        'name'   => $payerName ?: ($store['slug'] ?? ''),
    ]
);

if (!$ok) {
    @unlink($destination);
    echo json_encode(['success' => false, 'error' => 'Error al crear la solicitud']);
    exit;
}

$requestId = platformLastInsertId();

echo json_encode([
    'success' => true,
    'payment_request_id' => $requestId,
    'message' => '¡Comprobante enviado! El administrador revisará tu transferencia y activará el plan.',
]);
