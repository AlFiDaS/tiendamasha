<?php
$requestId = (int) ($_GET['request_id'] ?? 0);
define('TIENDI_PLATFORM', true);
require_once __DIR__ . '/../platform-config.php';
$req = $requestId ? platformFetchOne('SELECT store_id FROM payment_requests WHERE id = :id', ['id' => $requestId]) : null;
$store = $req ? platformFetchOne('SELECT slug FROM stores WHERE id = :id', ['id' => $req['store_id']]) : null;
$slug = $store['slug'] ?? '';
$redirect = $slug ? (PLATFORM_URL . '/' . $slug . '/admin/planes.php?payment=pending') : (PLATFORM_PAGES_URL . '/dashboard.php');
header('Location: ' . $redirect);
exit;
