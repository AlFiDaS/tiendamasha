<?php
/**
 * ============================================
 * API REST: Productos
 * ============================================
 * Endpoint para obtener productos
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
require_once '../helpers/auth.php'; // Para función sanitize()
require_once '../helpers/categories.php';

try {
    // Rate limiting para prevenir abuso
    require_once '../helpers/security.php';
    $rateLimit = checkRateLimit('api_products', 120, 60); // 120 requests por minuto
    
    if (!$rateLimit['allowed']) {
        http_response_code(429); // Too Many Requests
        header('Retry-After: ' . ($rateLimit['reset_at'] - time()));
        echo json_encode([
            'error' => 'Demasiadas solicitudes. Por favor, intenta más tarde.',
            'retry_after' => $rateLimit['reset_at'] - time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Construir consulta con filtros
    $sql = "SELECT id, slug, name, descripcion, price, image, hoverImage, stock, destacado, categoria, en_descuento, precio_descuento 
            FROM products 
            WHERE visible = 1";
    
    $params = [];
    
    // Filtro por categoría
    if (!empty($_GET['categoria'])) {
        $categoria = sanitize($_GET['categoria']);
        // Validar que la categoría exista (solo visibles para la API pública)
        $categoriaObj = getCategoryBySlug($categoria);
        if ($categoriaObj && $categoriaObj['visible']) {
            $sql .= " AND categoria = :categoria";
            $params['categoria'] = $categoria;
        }
    }
    
    // Filtro por destacado
    if (isset($_GET['destacado'])) {
        $destacado = (int)$_GET['destacado'];
        if ($destacado === 1 || $destacado === 0) {
            $sql .= " AND destacado = :destacado";
            $params['destacado'] = $destacado;
        }
    }
    
    // Filtro por stock
    if (isset($_GET['stock'])) {
        $stock = $_GET['stock'];
        if ($stock === 'available') {
            // Solo productos con stock disponible (ilimitado o > 0)
            $sql .= " AND (stock IS NULL OR stock > 0)";
        } elseif ($stock === 'unlimited') {
            $sql .= " AND stock IS NULL";
        } elseif ($stock === 'limited') {
            $sql .= " AND stock IS NOT NULL AND stock > 0";
        } elseif ($stock === '0' || $stock === 0) {
            $sql .= " AND stock IS NOT NULL AND stock = 0";
        }
    }
    
    // Filtro por slug (producto específico)
    if (!empty($_GET['slug'])) {
        $slug = sanitize($_GET['slug']);
        $sql .= " AND slug = :slug";
        $params['slug'] = $slug;
    }
    
    // Ordenamiento: primero por orden (si existe), luego destacado, luego nombre
    // Verificar si la columna orden existe antes de usarla
    try {
        $checkOrden = fetchOne("SHOW COLUMNS FROM products LIKE 'orden'");
        if ($checkOrden) {
            $sql .= " ORDER BY 
                CASE WHEN orden IS NULL THEN 1 ELSE 0 END,
                orden ASC,
                destacado DESC, 
                name ASC";
        } else {
            $sql .= " ORDER BY destacado DESC, name ASC";
        }
    } catch (Exception $e) {
        // Si hay error, usar ordenamiento simple
        $sql .= " ORDER BY destacado DESC, name ASC";
    }
    
    // Límite opcional
    if (!empty($_GET['limit'])) {
        $limit = (int)$_GET['limit'];
        if ($limit > 0 && $limit <= 100) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $limit;
        }
    }
    
    // Ejecutar consulta
    $products = fetchAll($sql, $params);
    
    if ($products === false) {
        throw new Exception('Error al consultar la base de datos');
    }
    
    // Si se busca por slug, devolver objeto único en lugar de array
    if (!empty($_GET['slug'])) {
        if (empty($products)) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Producto no encontrado'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        $product = $products[0];
        
        // Agregar cache busting a imágenes
        if (!empty($product['image'])) {
            $product['image'] = addCacheBust($product['image']);
        }
        if (!empty($product['hoverImage'])) {
            $product['hoverImage'] = addCacheBust($product['hoverImage']);
        }
        
        echo json_encode([
            'success' => true,
            'product' => $product
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // Agregar cache busting a todas las imágenes
    $products = addCacheBustToProducts($products);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'count' => count($products),
        'products' => $products
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

