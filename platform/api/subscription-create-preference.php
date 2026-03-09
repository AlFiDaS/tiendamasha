<?php
/**
 * API: Crear preferencia de MercadoPago para pago de membresía
 * Recibe: store_id, plan, duration_months
 * Retorna: checkout_url, payment_request_id
 */
define('TIENDI_PLATFORM', true);
require_once dirname(__DIR__, 2) . '/platform-config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$accessToken = defined('PLATFORM_MP_ACCESS_TOKEN') ? PLATFORM_MP_ACCESS_TOKEN : '';
if (empty($accessToken)) {
    echo json_encode(['success' => false, 'error' => 'MercadoPago de plataforma no configurado. Contactá al administrador.']);
    exit;
}

require_once dirname(__DIR__, 2) . '/helpers/subscription-pricing.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$storeId = (int) ($input['store_id'] ?? 0);
$plan = preg_replace('/[^a-z]/', '', strtolower($input['plan'] ?? ''));
$durationMonths = (int) ($input['duration_months'] ?? 1);

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

$limits = getPlanLimits($plan);
$planName = $limits['name'] ?? ucfirst($plan);

// Crear registro payment_request
$externalRef = 'sub_' . $storeId . '_' . uniqid();
$ok = platformQuery(
    'INSERT INTO payment_requests (store_id, plan, duration_months, amount, status) VALUES (:sid, :plan, :dur, :amt, :status)',
    ['sid' => $storeId, 'plan' => $plan, 'dur' => $durationMonths, 'amt' => $pricing['total'], 'status' => 'pending_payment']
);
if (!$ok) {
    echo json_encode(['success' => false, 'error' => 'Error al crear la solicitud']);
    exit;
}

$requestId = platformLastInsertId();

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host;

$preferenceData = [
    'items' => [[
        'title'       => 'Plan ' . $planName . ' - ' . $pricing['duration_label'] . ' - ' . $store['slug'],
        'quantity'    => 1,
        'unit_price'  => (float) $pricing['total'],
        'currency_id' => 'ARS',
    ]],
    'external_reference' => $externalRef,
    'back_urls' => [
        'success' => $baseUrl . '/platform/subscription-success.php?request_id=' . $requestId,
        'failure' => $baseUrl . '/platform/subscription-failure.php?request_id=' . $requestId,
        'pending' => $baseUrl . '/platform/subscription-pending.php?request_id=' . $requestId,
    ],
    'auto_return' => 'approved',
    'notification_url' => $baseUrl . '/platform/api/subscription-webhook.php',
    'metadata' => [
        'payment_request_id' => $requestId,
        'store_id' => $storeId,
        'plan' => $plan,
        'duration_months' => $durationMonths,
    ],
];

$ch = curl_init('https://api.mercadopago.com/checkout/preferences');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
    ],
    CURLOPT_POSTFIELDS => json_encode($preferenceData),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || !empty($curlError)) {
    $errMsg = 'Error de conexión con MercadoPago: ' . ($curlError ?: 'Sin respuesta');
    error_log('[SubscriptionMP] curl error: ' . $errMsg);
    platformQuery('UPDATE payment_requests SET status = "rejected", notes = :n WHERE id = :id', ['n' => $errMsg, 'id' => $requestId]);
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

$preference = json_decode($response, true);
if ($httpCode < 200 || $httpCode >= 300 || !isset($preference['init_point'])) {
    $mpError = $preference['message'] ?? ($preference['error'] ?? '');
    if (empty($mpError) && isset($preference['cause'])) {
        $causes = array_map(function($c) { return ($c['code'] ?? '') . ': ' . ($c['description'] ?? ''); }, (array)$preference['cause']);
        $mpError = implode('; ', $causes);
    }
    $fullError = 'HTTP ' . $httpCode . ' - ' . ($mpError ?: substr($response, 0, 300));
    error_log('[SubscriptionMP] API error: ' . $fullError);
    platformQuery('UPDATE payment_requests SET status = "rejected", notes = :n WHERE id = :id', ['n' => 'Error MP: ' . $fullError, 'id' => $requestId]);
    echo json_encode(['success' => false, 'error' => 'Error al crear el pago (HTTP ' . $httpCode . '): ' . ($mpError ?: 'Respuesta inesperada de MercadoPago')]);
    exit;
}

platformQuery(
    'UPDATE payment_requests SET mercadopago_preference_id = :pref WHERE id = :id',
    ['pref' => $preference['id'] ?? null, 'id' => $requestId]
);

echo json_encode([
    'success' => true,
    'checkout_url' => $preference['init_point'],
    'payment_request_id' => $requestId,
]);
