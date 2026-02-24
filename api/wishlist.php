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
            // Obtener wishlist del usuario (solo productos visibles)
            // fetchAllRaw: evita que injectStoreId excluya filas con store_id=NULL
            $sql = "SELECT w.id, w.product_id, w.created_at, 
                           p.name, p.slug, p.image, p.hoverImage, p.price, p.categoria, p.stock
                    FROM wishlist w
                    INNER JOIN products p ON w.product_id = p.id
                    WHERE w.session_id = :session_id
                      AND p.visible = 1
                    ORDER BY w.created_at DESC";
            $items = fetchAllRaw($sql, ['session_id' => $sessionId]);
            
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
            
            // Verificar que el producto existe y es visible (acepta id numérico o slug)
            // Incluir store_id para BD compartida: el product_id debe existir en products de esta tienda (evita FK violation)
            $isNumeric = (is_numeric($productId) && (int) $productId > 0);
            $product = false;
            
            if (defined('CURRENT_STORE_ID') && CURRENT_STORE_ID > 0) {
                if ($isNumeric) {
                    $product = fetchOneRaw(
                        "SELECT id FROM products WHERE id = :id AND visible = 1 AND (store_id = :sid OR store_id IS NULL)",
                        ['id' => (int) $productId, 'sid' => (int) CURRENT_STORE_ID]
                    );
                } else {
                    $product = fetchOneRaw(
                        "SELECT id FROM products WHERE slug = :slug AND visible = 1 AND (store_id = :sid OR store_id IS NULL)",
                        ['slug' => trim($productId), 'sid' => (int) CURRENT_STORE_ID]
                    );
                }
            }
            if (!$product) {
                if ($isNumeric) {
                    $product = fetchOneRaw(
                        "SELECT id FROM products WHERE id = :id AND visible = 1",
                        ['id' => (int) $productId]
                    );
                } else {
                    $product = fetchOneRaw(
                        "SELECT id FROM products WHERE slug = :slug AND visible = 1",
                        ['slug' => trim($productId)]
                    );
                }
            }
            
            if (!$product) {
                throw new Exception('Producto no encontrado o no disponible');
            }
            
            // product_id puede ser INT o VARCHAR según esquema
            $realProductId = $product['id'];
            if (is_numeric($realProductId)) {
                $realProductId = (int) $realProductId;
            } else {
                $realProductId = (string) trim($realProductId);
            }
            
            // Verificar si ya está en wishlist
            $existing = fetchOneRaw(
                "SELECT id FROM wishlist WHERE session_id = :session_id AND product_id = :product_id",
                ['session_id' => $sessionId, 'product_id' => $realProductId]
            );
            
            if ($existing) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ya está en favoritos',
                    'already_exists' => true
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // INSERT: intentar con store_id primero, fallback sin store_id
                $inserted = false;
                $lastError = '';
                if (defined('CURRENT_STORE_ID') && CURRENT_STORE_ID > 0) {
                    $inserted = executeRaw(
                        "INSERT INTO wishlist (store_id, session_id, product_id) VALUES (:store_id, :session_id, :product_id)",
                        ['store_id' => (int) CURRENT_STORE_ID, 'session_id' => $sessionId, 'product_id' => $realProductId],
                        $lastError
                    );
                }
                if (!$inserted) {
                    $inserted = executeRaw(
                        "INSERT INTO wishlist (session_id, product_id) VALUES (:session_id, :product_id)",
                        ['session_id' => $sessionId, 'product_id' => $realProductId],
                        $lastError
                    );
                }
                if ($inserted) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Agregado a favoritos'
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    throw new Exception('Error al agregar a favoritos' . ($lastError ? ': ' . $lastError : ''));
                }
            }
            break;
            
        case 'DELETE':
            // Eliminar producto de wishlist (acepta id numérico o slug)
            $productId = $input['product_id'] ?? $_GET['product_id'] ?? '';
            
            if (empty($productId)) {
                throw new Exception('Product ID requerido');
            }
            
            $isNumeric = (is_numeric($productId) && (int) $productId > 0);
            if ($isNumeric) {
                $realProductId = (int) $productId;
            } else {
                $product = false;
                if (defined('CURRENT_STORE_ID') && CURRENT_STORE_ID > 0) {
                    $product = fetchOneRaw(
                        "SELECT id FROM products WHERE slug = :slug AND visible = 1 AND (store_id = :sid OR store_id IS NULL)",
                        ['slug' => trim($productId), 'sid' => (int) CURRENT_STORE_ID]
                    );
                }
                if (!$product) {
                    $product = fetchOneRaw(
                        "SELECT id FROM products WHERE slug = :slug AND visible = 1",
                        ['slug' => trim($productId)]
                    );
                }
                $realProductId = $product ? $product['id'] : null;
                if (is_numeric($realProductId)) $realProductId = (int) $realProductId;
                else $realProductId = (string) trim($realProductId ?? '');
            }
            
            if ($realProductId === '' || $realProductId === null || (is_numeric($realProductId) && $realProductId < 1)) {
                throw new Exception('Producto no encontrado');
            }
            
            $deleted = false;
            if (defined('CURRENT_STORE_ID') && CURRENT_STORE_ID > 0) {
                $deleted = executeRaw(
                    "DELETE FROM wishlist WHERE session_id = :session_id AND product_id = :product_id AND (store_id = :store_id OR store_id IS NULL)",
                    ['session_id' => $sessionId, 'product_id' => $realProductId, 'store_id' => (int) CURRENT_STORE_ID]
                );
            }
            if (!$deleted) {
                $deleted = executeRaw(
                    "DELETE FROM wishlist WHERE session_id = :session_id AND product_id = :product_id",
                    ['session_id' => $sessionId, 'product_id' => $realProductId]
                );
            }
            if ($deleted) {
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

