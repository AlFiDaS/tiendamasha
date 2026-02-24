<?php
require_once __DIR__ . '/../../../platform-config.php';
requireSuperAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$storeId = (int)($data['store_id'] ?? 0);
$confirmSlug = trim($data['confirm_slug'] ?? '');

if (!$storeId || !$confirmSlug) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$store = platformFetchOne('SELECT slug FROM stores WHERE id = :id', ['id' => $storeId]);
if (!$store) {
    echo json_encode(['success' => false, 'error' => 'Tienda no encontrada']);
    exit;
}

if ($confirmSlug !== $store['slug']) {
    echo json_encode(['success' => false, 'error' => 'El nombre no coincide. Escribí "' . $store['slug'] . '" para confirmar.']);
    exit;
}

$result = deleteStore($storeId);
echo json_encode($result);
