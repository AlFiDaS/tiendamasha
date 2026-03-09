<?php
/**
 * API: Crear solicitud de plan Platinum
 * Recibe JSON: store_id, contact_name, phone, shop_name
 */
define('TIENDI_PLATFORM', true);
require_once dirname(__DIR__, 2) . '/platform-config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

require_once dirname(__DIR__, 2) . '/helpers/plans.php';

$input = json_decode(file_get_contents('php://input'), true);
$storeId     = (int) ($input['store_id'] ?? 0);
$contactName = trim($input['contact_name'] ?? '');
$phone       = trim($input['phone'] ?? '');
$shopName    = trim($input['shop_name'] ?? '');

if (!$storeId || !$contactName || !$phone || !$shopName) {
    echo json_encode(['success' => false, 'error' => 'Completá todos los campos']);
    exit;
}

$store = platformFetchOne('SELECT id, slug FROM stores WHERE id = :id', ['id' => $storeId]);
if (!$store) {
    echo json_encode(['success' => false, 'error' => 'Tienda no encontrada']);
    exit;
}

$platCheck = isPlatinumAvailable($storeId);
if (!$platCheck['available']) {
    echo json_encode(['success' => false, 'error' => 'El cupo de Platinum está agotado']);
    exit;
}

$pending = platformFetchOne(
    "SELECT id FROM platinum_requests WHERE store_id = :sid AND status = 'pending'",
    ['sid' => $storeId]
);
if ($pending) {
    echo json_encode(['success' => false, 'error' => 'Ya tenés una solicitud pendiente. Te contactaremos pronto.']);
    exit;
}

$ok = platformQuery(
    'INSERT INTO platinum_requests (store_id, contact_name, phone, shop_name, status, created_at)
     VALUES (:sid, :name, :phone, :shop, :status, NOW())',
    [
        'sid'    => $storeId,
        'name'   => $contactName,
        'phone'  => $phone,
        'shop'   => $shopName,
        'status' => 'pending',
    ]
);

if (!$ok) {
    echo json_encode(['success' => false, 'error' => 'Error al crear la solicitud']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => '¡Solicitud enviada! Nos comunicaremos por WhatsApp.',
]);
