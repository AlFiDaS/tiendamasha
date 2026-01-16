<?php
/**
 * ============================================
 * HELPER: Generación de Slugs
 * ============================================
 * Convierte nombres a URLs amigables
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Generar slug desde un texto
 * @param string $text
 * @return string
 */
function slugify($text) {
    // Convertir a minúsculas
    $text = mb_strtolower($text, 'UTF-8');
    
    // Reemplazar caracteres especiales españoles (Ñ se convierte en 'n')
    $replace = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ñ' => 'n', 'ü' => 'u',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
        'Ñ' => 'n', 'Ü' => 'u'
    ];
    $text = strtr($text, $replace);
    
    // Remover caracteres especiales, mantener solo alfanuméricos y espacios
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Reemplazar espacios múltiples por uno solo
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Remover guiones al inicio y final
    $text = trim($text, '-');
    
    // Verificar que no contenga 'ñ' (por si acaso)
    if (strpos($text, 'ñ') !== false || strpos($text, 'Ñ') !== false) {
        $text = str_replace(['ñ', 'Ñ'], 'n', $text);
    }
    
    return $text;
}

/**
 * Generar slug único
 * @param string $text
 * @param string|null $excludeId (ID del producto a excluir de la búsqueda)
 * @return string
 */
function generateUniqueSlug($text, $excludeId = null) {
    $baseSlug = slugify($text);
    
    if (empty($baseSlug)) {
        $baseSlug = 'producto';
    }
    
    $slug = $baseSlug;
    $counter = 1;
    
    // Buscar si el slug ya existe
    while (slugExists($slug, $excludeId)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
        
        // Prevenir loops infinitos
        if ($counter > 1000) {
            $slug = $baseSlug . '-' . time();
            break;
        }
    }
    
    return $slug;
}

/**
 * Verificar si un slug ya existe
 * @param string $slug
 * @param string|null $excludeId
 * @return bool
 */
function slugExists($slug, $excludeId = null) {
    $sql = "SELECT COUNT(*) as count FROM products WHERE slug = :slug";
    $params = ['slug' => $slug];
    
    if ($excludeId !== null) {
        $sql .= " AND id != :exclude_id";
        $params['exclude_id'] = $excludeId;
    }
    
    $result = fetchOne($sql, $params);
    return $result && $result['count'] > 0;
}

/**
 * Generar ID único para producto
 * @return string
 */
function generateProductId() {
    // Generar ID basado en timestamp + random
    return 'prod_' . time() . '_' . bin2hex(random_bytes(4));
}

