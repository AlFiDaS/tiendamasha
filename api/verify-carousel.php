<?php
/**
 * Script de verificación: devuelve el JSON del carrusel para diagnosticar.
 * Llamar desde: /tesst2/api/verify-carousel.php (o /{slug}/api/verify-carousel.php)
 * Así se usa el mismo contexto de tienda que la landing.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Simular la misma carga que landing-page.php (store-router ya habrá llamado setStoreContext)
require_once __DIR__ . '/../config.php';

$storeId = defined('CURRENT_STORE_ID') ? CURRENT_STORE_ID : 0;
$storeSlug = defined('CURRENT_STORE_SLUG') ? CURRENT_STORE_SLUG : '';

$settings = fetchOne("SELECT id, store_id, carousel_images FROM landing_page_settings WHERE id = 1 LIMIT 1");

$carousel = [];
if (!empty($settings['carousel_images'])) {
    $carousel = json_decode($settings['carousel_images'], true) ?: [];
}

$output = [
    'debug' => [
        'CURRENT_STORE_ID' => $storeId,
        'CURRENT_STORE_SLUG' => $storeSlug,
        'settings_row_id' => $settings['id'] ?? null,
        'settings_store_id' => $settings['store_id'] ?? null,
    ],
    'carousel_count' => count($carousel),
    'carousel' => $carousel,
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
