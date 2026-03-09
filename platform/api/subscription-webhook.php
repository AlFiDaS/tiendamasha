<?php
/**
 * Webhook MercadoPago para pagos de membresía
 * Recibe notificaciones de pago y actualiza payment_requests
 */
define('TIENDI_PLATFORM', true);
require_once dirname(__DIR__, 2) . '/platform-config.php';

// MercadoPago envía GET con topic=payment&id=xxx
$topic = $_GET['topic'] ?? '';
$id = $_GET['id'] ?? '';

if ($topic !== 'payment' || empty($id)) {
    http_response_code(400);
    exit;
}

$accessToken = defined('PLATFORM_MP_ACCESS_TOKEN') ? PLATFORM_MP_ACCESS_TOKEN : '';
if (empty($accessToken)) {
    http_response_code(500);
    exit;
}

// Consultar estado del pago a MercadoPago
$ch = curl_init('https://api.mercadopago.com/v1/payments/' . $id);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    exit;
}

$payment = json_decode($response, true);
$status = $payment['status'] ?? '';
$preferenceId = $payment['metadata']['payment_request_id'] ?? null;

if ($status !== 'approved') {
    http_response_code(200);
    exit;
}

$payerEmail = $payment['payer']['email'] ?? null;
$payerName = trim(($payment['payer']['first_name'] ?? '') . ' ' . ($payment['payer']['last_name'] ?? ''));

// Buscar por preference_id de MercadoPago (el pago viene de una preference)
$mpPreferenceId = $payment['metadata']['preference_id'] ?? $payment['preference_id'] ?? '';
$req = platformFetchOne('SELECT id FROM payment_requests WHERE mercadopago_preference_id = :pref AND status = "pending_payment"', ['pref' => $mpPreferenceId]);

if (!$req && $preferenceId) {
    $req = platformFetchOne('SELECT id FROM payment_requests WHERE id = :id AND status = "pending_payment"', ['id' => (int) $preferenceId]);
}

if ($req) {
    platformQuery(
        'UPDATE payment_requests SET status = "paid", mercadopago_payment_id = :mpid, payer_email = :email, payer_name = :name, paid_at = NOW() WHERE id = :id',
        ['mpid' => $id, 'email' => $payerEmail, 'name' => trim($payerName), 'id' => $req['id']]
    );
}

http_response_code(200);
