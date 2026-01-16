<?php
/**
 * ============================================
 * HELPER: Funciones de Seguridad
 * ============================================
 * Rate limiting, validaciones adicionales, etc.
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Rate limiting simple basado en IP y sesión
 * @param string $key Identificador único para el rate limit (ej: 'api_products', 'login_attempt')
 * @param int $maxAttempts Número máximo de intentos
 * @param int $timeWindow Ventana de tiempo en segundos
 * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
 */
function checkRateLimit($key, $maxAttempts = 60, $timeWindow = 60) {
    startSecureSession();
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitKey = "rate_limit_{$key}_{$ip}";
    
    $now = time();
    $attempts = $_SESSION[$rateLimitKey] ?? ['count' => 0, 'reset_at' => $now + $timeWindow];
    
    // Si la ventana de tiempo expiró, resetear
    if ($now > $attempts['reset_at']) {
        $attempts = ['count' => 0, 'reset_at' => $now + $timeWindow];
    }
    
    // Incrementar contador
    $attempts['count']++;
    $_SESSION[$rateLimitKey] = $attempts;
    
    $remaining = max(0, $maxAttempts - $attempts['count']);
    $allowed = $attempts['count'] <= $maxAttempts;
    
    return [
        'allowed' => $allowed,
        'remaining' => $remaining,
        'reset_at' => $attempts['reset_at']
    ];
}

/**
 * Validar que un archivo es realmente una imagen verificando su contenido
 * @param string $filePath Ruta al archivo temporal
 * @param array $allowedTypes Tipos MIME permitidos
 * @return array ['valid' => bool, 'error' => string|null, 'imageInfo' => array|null]
 */
function validateImageContent($filePath, $allowedTypes = null) {
    if (!file_exists($filePath)) {
        return ['valid' => false, 'error' => 'Archivo no existe', 'imageInfo' => null];
    }
    
    // Verificar que es realmente una imagen usando getimagesize
    $imageInfo = @getimagesize($filePath);
    
    if ($imageInfo === false) {
        return ['valid' => false, 'error' => 'El archivo no es una imagen válida', 'imageInfo' => null];
    }
    
    // Obtener tipo MIME real
    $realMimeType = $imageInfo['mime'] ?? null;
    
    if (!$realMimeType) {
        return ['valid' => false, 'error' => 'No se pudo determinar el tipo de imagen', 'imageInfo' => null];
    }
    
    // Si se especificaron tipos permitidos, validar
    if ($allowedTypes !== null && !in_array($realMimeType, $allowedTypes)) {
        return [
            'valid' => false, 
            'error' => "Tipo de imagen no permitido. Tipo detectado: {$realMimeType}", 
            'imageInfo' => $imageInfo
        ];
    }
    
    // Verificar dimensiones mínimas (opcional, prevenir imágenes corruptas muy pequeñas)
    if ($imageInfo[0] < 10 || $imageInfo[1] < 10) {
        return ['valid' => false, 'error' => 'La imagen es demasiado pequeña o está corrupta', 'imageInfo' => $imageInfo];
    }
    
    return [
        'valid' => true, 
        'error' => null, 
        'imageInfo' => [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'mime' => $realMimeType,
            'type' => $imageInfo[2] // IMAGETYPE_JPEG, IMAGETYPE_PNG, etc.
        ]
    ];
}

/**
 * Escapar output HTML de forma segura
 * @param string $text Texto a escapar
 * @param bool $allowHtml Si es true, permite HTML pero sanitiza (usar con cuidado)
 * @return string
 */
function escapeHtml($text, $allowHtml = false) {
    if ($allowHtml) {
        // Permitir solo tags HTML seguros
        $allowedTags = '<p><br><strong><em><u><b><i><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';
        $text = strip_tags($text, $allowedTags);
        // Escapar atributos pero mantener estructura
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }
    
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Generar token CSRF seguro
 * @return string
 */
function generateSecureCSRFToken() {
    startSecureSession();
    
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        // Generar token más seguro usando random_bytes
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_created'] = time();
    }
    
    // Regenerar token cada 30 minutos para mayor seguridad
    if (isset($_SESSION['csrf_token_created']) && 
        (time() - $_SESSION['csrf_token_created']) > 1800) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_created'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validar origen de la petición (prevenir CSRF)
 * @param string $referer Referer de la petición
 * @return bool
 */
function validateRequestOrigin($referer = null) {
    if ($referer === null) {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
    }
    
    if (empty($referer)) {
        // En algunos casos legítimos puede no haber referer (ej: navegación directa)
        // Pero para acciones críticas debería existir
        return false;
    }
    
    $baseUrl = BASE_URL;
    $parsedReferer = parse_url($referer);
    $parsedBase = parse_url($baseUrl);
    
    // Verificar que el host coincide
    if (isset($parsedReferer['host']) && isset($parsedBase['host'])) {
        return $parsedReferer['host'] === $parsedBase['host'];
    }
    
    return false;
}

/**
 * Sanitizar nombre de archivo para prevenir path traversal
 * @param string $filename Nombre de archivo
 * @return string
 */
function sanitizeFilename($filename) {
    // Remover caracteres peligrosos
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    // Remover puntos múltiples
    $filename = preg_replace('/\.{2,}/', '.', $filename);
    // Remover paths relativos
    $filename = str_replace(['../', '..\\', '/', '\\'], '', $filename);
    // Limitar longitud
    if (strlen($filename) > 255) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $filename = substr($name, 0, 255 - strlen($ext) - 1) . '.' . $ext;
    }
    
    return $filename;
}

