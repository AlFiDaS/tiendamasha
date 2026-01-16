<?php
/**
 * ============================================
 * HELPER: Cache Busting para Imágenes
 * ============================================
 * Agrega parámetros de versión a URLs de imágenes
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Agregar cache busting a una URL de imagen
 * @param string $imagePath (ruta relativa, ej: '/images/vela-xoxo/main.webp')
 * @return string (URL con parámetro de versión)
 */
function addCacheBust($imagePath) {
    if (empty($imagePath)) {
        return '';
    }
    
    // Si ya tiene parámetros, agregar al final
    $separator = strpos($imagePath, '?') !== false ? '&' : '?';
    
    // Obtener timestamp de modificación del archivo
    // La ruta en BD es /images/... (ruta web), convertir a ruta física usando IMAGES_PATH
    // Remover el prefijo /images/ de la ruta relativa
    $relativePath = preg_replace('#^/images/#', '', $imagePath);
    $fullPath = IMAGES_PATH . '/' . $relativePath;
    
    if (file_exists($fullPath)) {
        $timestamp = filemtime($fullPath);
        return $imagePath . $separator . 'v=' . $timestamp;
    }
    
    // Si el archivo no existe, usar timestamp actual
    return $imagePath . $separator . 'v=' . time();
}

/**
 * Agregar cache busting a múltiples imágenes
 * @param array $products (array de productos)
 * @return array (productos con URLs actualizadas)
 */
function addCacheBustToProducts($products) {
    if (!is_array($products)) {
        return $products;
    }
    
    foreach ($products as &$product) {
        if (isset($product['image']) && !empty($product['image'])) {
            $product['image'] = addCacheBust($product['image']);
        }
        
        if (isset($product['hoverImage']) && !empty($product['hoverImage'])) {
            $product['hoverImage'] = addCacheBust($product['hoverImage']);
        }
    }
    
    return $products;
}

