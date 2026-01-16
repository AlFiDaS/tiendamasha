<?php
/**
 * ============================================
 * API REST: Wishlist/Favoritos
 * ============================================
 * Endpoint para gestionar wishlist de usuarios
 * Compatible: PHP 7.4+
 * ============================================
 */

// Desactivar display de errores ANTES de cargar config
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Headers CORS y JSON
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Asegurar que LUME_ADMIN está definido
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Cargar configuración
require_once '../config.php';

// Temporalmente desactivar display_errors
ini_set('display_errors', 0);

// Cargar helpers
require_once '../helpers/db.php';

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Obtener session_id (puede venir del cliente o generarse)
    $sessionId = $input['session_id'] ?? $_GET['session_id'] ?? session_id();
    
    if (empty($sessionId)) {
        throw new Exception('Session ID requerido');
    }
    
    switch ($method) {
        case 'GET':
            // Obtener wishlist del usuario
            // Solo mostrar productos visibles y de categorías visibles
            $sql = "SELECT w.id, w.product_id, w.created_at, 
                           p.name, p.slug, p.image, p.hoverImage, p.price, p.categoria, p.stock
                    FROM wishlist w
                    INNER JOIN products p ON w.product_id = p.id
                    INNER JOIN categories c ON p.categoria = c.slug
                    WHERE w.session_id = :session_id
                      AND p.visible = 1
                      AND c.visible = 1
                    ORDER BY w.created_at DESC";
            
            $items = fetchAll($sql, ['session_id' => $sessionId]);
            
            echo json_encode([
                'success' => true,
                'items' => $items ?: [],
                'count' => count($items ?: [])
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case 'POST':
            // Agregar producto a wishlist
            $productId = $input['product_id'] ?? '';
            
            if (empty($productId)) {
                throw new Exception('Product ID requerido');
            }
            
            // Verificar que el producto existe, es visible y su categoría es visible
            $product = fetchOne(
                "SELECT p.id FROM products p
                 INNER JOIN categories c ON p.categoria = c.slug
                 WHERE p.id = :id AND p.visible = 1 AND c.visible = 1",
                ['id' => $productId]
            );
            if (!$product) {
                throw new Exception('Producto no encontrado o no disponible');
            }
            
            // Verificar si ya está en wishlist
            $existing = fetchOne(
                "SELECT id FROM wishlist WHERE session_id = :session_id AND product_id = :product_id",
                ['session_id' => $sessionId, 'product_id' => $productId]
            );
            
            if ($existing) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ya está en favoritos',
                    'already_exists' => true
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $sql = "INSERT INTO wishlist (session_id, product_id) VALUES (:session_id, :product_id)";
                if (executeQuery($sql, ['session_id' => $sessionId, 'product_id' => $productId])) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Agregado a favoritos'
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    throw new Exception('Error al agregar a favoritos');
                }
            }
            break;
            
        case 'DELETE':
            // Eliminar producto de wishlist
            $productId = $input['product_id'] ?? $_GET['product_id'] ?? '';
            
            if (empty($productId)) {
                throw new Exception('Product ID requerido');
            }
            
            $sql = "DELETE FROM wishlist WHERE session_id = :session_id AND product_id = :product_id";
            if (executeQuery($sql, ['session_id' => $sessionId, 'product_id' => $productId])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Eliminado de favoritos'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Error al eliminar de favoritos');
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

