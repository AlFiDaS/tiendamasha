<?php
/**
 * Configuración Rápida - Redirige a /platform/
 * La configuración rápida solo se hace desde platform/configuracion-rapida.php
 */
require_once '../config.php';

$storeSlug = defined('CURRENT_STORE_SLUG') ? CURRENT_STORE_SLUG : '';
$platformUrl = rtrim(BASE_URL, '/') . '/platform/configuracion-rapida.php';
if ($storeSlug) {
    $platformUrl .= '?store=' . urlencode($storeSlug);
}
header('Location: ' . $platformUrl);
exit;
