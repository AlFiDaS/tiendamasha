<?php
/**
 * ============================================
 * API REST: Configuración de la Tienda
 * ============================================
 * Endpoint para obtener configuración de la tienda (logo, footer, etc.)
 * Compatible: PHP 7.4+
 * ============================================
 */

// Desactivar display de errores ANTES de cargar config
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Headers CORS y JSON (deben ir antes de cualquier output)
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
    // Headers para evitar caché en desarrollo
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Asegurar que LUME_ADMIN está definido antes de cargar helpers
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Cargar configuración
require_once '../config.php';

// Temporalmente desactivar display_errors (config.php lo puede activar)
ini_set('display_errors', 0);

// Cargar helpers
require_once '../helpers/cache-bust.php';
require_once '../helpers/shop-settings.php';

try {
    // Obtener configuración de la tienda
    $settings = getShopSettings();
    
    if (!$settings) {
        throw new Exception('No se encontró configuración de la tienda');
    }
    
    // Normalizar redes sociales: si es solo usuario, convertir a URL completa
    $instagram = $settings['instagram'] ?? '';
    $facebook = $settings['facebook'] ?? '';
    if ($instagram && !preg_match('#^https?://#i', $instagram)) {
        $instagram = 'https://www.instagram.com/' . preg_replace('/^@/', '', $instagram) . '/';
    }
    if ($facebook && !preg_match('#^https?://#i', $facebook)) {
        $facebook = 'https://www.facebook.com/' . preg_replace('/^@/', '', $facebook) . '/';
    }
    // MercadoPago: indicar si está configurado (sin exponer el token)
    // En multi-tenant: solo usar shop_settings de la tienda actual (no fallback a .env)
    if (defined('CURRENT_STORE_ID') && CURRENT_STORE_ID > 0) {
        $mercadopagoConfigured = !empty(trim($settings['mercadopago_access_token'] ?? ''));
    } else {
        $mpToken = !empty($settings['mercadopago_access_token']) ? $settings['mercadopago_access_token'] : (defined('MERCADOPAGO_ACCESS_TOKEN') ? MERCADOPAGO_ACCESS_TOKEN : '');
        $mercadopagoConfigured = !empty($mpToken);
    }

    // Preparar datos para el frontend
    $shopData = [
        'shop_name' => $settings['shop_name'] ?? '',
        'shop_logo' => !empty($settings['shop_logo']) ? addCacheBust($settings['shop_logo']) : null,
        'whatsapp_number' => $settings['whatsapp_number'] ?? '',
        'whatsapp_message' => $settings['whatsapp_message'] ?? '',
        'instagram' => $instagram,
        'facebook' => $facebook,
        'email' => $settings['email'] ?? '',
        'phone' => $settings['phone'] ?? '',
        'address' => $settings['address'] ?? '',
        'description' => $settings['description'] ?? '',
        'footer_description' => $settings['footer_description'] ?? '',
        'footer_copyright' => $settings['footer_copyright'] ?? '',
        'creation_year' => $settings['creation_year'] ?? null,
        'transfer_alias' => $settings['transfer_alias'] ?? '',
        'transfer_cbu' => $settings['transfer_cbu'] ?? '',
        'transfer_titular' => $settings['transfer_titular'] ?? '',
        'mercadopago_configured' => $mercadopagoConfigured
    ];
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'shop' => $shopData
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar la configuración: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
