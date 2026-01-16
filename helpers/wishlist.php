<?php
/**
 * ============================================
 * HELPER: Wishlist/Favoritos
 * ============================================
 * Funciones para manejar wishlist de usuarios
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

// Asegurar que db.php esté cargado
if (!function_exists('executeQuery')) {
    require_once __DIR__ . '/db.php';
}

/**
 * Verificar si un producto está en la wishlist
 * @param string $sessionId ID de sesión del usuario
 * @param string $productId ID del producto
 * @return bool
 */
function isInWishlist($sessionId, $productId) {
    $sql = "SELECT id FROM wishlist WHERE session_id = :session_id AND product_id = :product_id";
    $result = fetchOne($sql, ['session_id' => $sessionId, 'product_id' => $productId]);
    return !empty($result);
}

/**
 * Obtener wishlist de un usuario
 * @param string $sessionId ID de sesión del usuario
 * @return array
 */
function getUserWishlist($sessionId) {
    $sql = "SELECT w.id, w.product_id, w.created_at, 
                   p.name, p.slug, p.image, p.price, p.categoria, p.stock
            FROM wishlist w
            INNER JOIN products p ON w.product_id = p.id
            WHERE w.session_id = :session_id
            ORDER BY w.created_at DESC";
    
    return fetchAll($sql, ['session_id' => $sessionId]) ?: [];
}

/**
 * Agregar producto a wishlist
 * @param string $sessionId ID de sesión del usuario
 * @param string $productId ID del producto
 * @return bool
 */
function addToWishlist($sessionId, $productId) {
    // Verificar si ya existe
    if (isInWishlist($sessionId, $productId)) {
        return true; // Ya está, no hay problema
    }
    
    $sql = "INSERT INTO wishlist (session_id, product_id) VALUES (:session_id, :product_id)";
    return executeQuery($sql, ['session_id' => $sessionId, 'product_id' => $productId]);
}

/**
 * Eliminar producto de wishlist
 * @param string $sessionId ID de sesión del usuario
 * @param string $productId ID del producto
 * @return bool
 */
function removeFromWishlist($sessionId, $productId) {
    $sql = "DELETE FROM wishlist WHERE session_id = :session_id AND product_id = :product_id";
    return executeQuery($sql, ['session_id' => $sessionId, 'product_id' => $productId]);
}

/**
 * Obtener cantidad de items en wishlist
 * @param string $sessionId ID de sesión del usuario
 * @return int
 */
function getWishlistCount($sessionId) {
    $sql = "SELECT COUNT(*) as count FROM wishlist WHERE session_id = :session_id";
    $result = fetchOne($sql, ['session_id' => $sessionId]);
    return (int)($result['count'] ?? 0);
}

