<?php
/**
 * Router para el servidor PHP integrado
 * Redirige peticiones a archivos estáticos y maneja rutas
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Remover query string para comparar rutas
$path = strtok($requestPath, '?');

// Normalizar la ruta (remover barras iniciales múltiples)
$path = '/' . ltrim($path, '/');

// Si es una petición a /images/, servir desde public/images/
if (preg_match('#^/images/(.+)$#', $path, $matches)) {
    $imagePath = $matches[1];
    // Decodificar URL encoding
    $imagePath = urldecode($imagePath);
    
    // Construir ruta del archivo usando DIRECTORY_SEPARATOR para compatibilidad Windows/Linux
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images';
    $imageFile = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imagePath);
    
    // Normalizar la ruta (resolver .. y .)
    $realImageFile = realpath($imageFile);
    $realBaseDir = realpath($baseDir);
    
    // Verificar seguridad: el archivo debe estar dentro del directorio de imágenes
    if ($realImageFile && $realBaseDir && strpos($realImageFile, $realBaseDir) === 0 && file_exists($realImageFile) && is_file($realImageFile)) {
        // Determinar tipo MIME
        $mimeType = @mime_content_type($realImageFile);
        if (!$mimeType) {
            $ext = strtolower(pathinfo($realImageFile, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon',
                'avif' => 'image/avif'
            ];
            $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        }
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($realImageFile));
        header('Cache-Control: public, max-age=31536000');
        header('Accept-Ranges: bytes');
        readfile($realImageFile);
        exit;
    }
}

// Si es un archivo estático en public/, servirlo directamente
if (preg_match('#^/(js|css|manifest\.json|sw\.js|favicon\.svg)(/.*)?$#', $path)) {
    $publicFile = __DIR__ . DIRECTORY_SEPARATOR . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $path);
    
    if (file_exists($publicFile) && is_file($publicFile)) {
        $mimeType = @mime_content_type($publicFile);
        if (!$mimeType) {
            $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
            $mimeTypes = [
                'js' => 'application/javascript',
                'css' => 'text/css',
                'json' => 'application/json',
                'svg' => 'image/svg+xml'
            ];
            $mimeType = $mimeTypes[$ext] ?? 'text/plain';
        }
        
        header('Content-Type: ' . $mimeType);
        readfile($publicFile);
        exit;
    }
}

// PRIORIDAD 1: Rutas del admin o api - servirlas directamente
if (preg_match('#^/(admin|api)/#', $path)) {
    $requestedFile = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (file_exists($requestedFile) && is_file($requestedFile)) {
        return false; // Dejar que PHP lo maneje normalmente
    }
}

// PRIORIDAD 2: Archivos PHP en la raíz
if (preg_match('#\.php$#', $path)) {
    $requestedFile = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (file_exists($requestedFile) && is_file($requestedFile)) {
        return false; // Dejar que PHP lo maneje normalmente
    }
}

// PRIORIDAD 3: Cualquier otro archivo que exista
$requestedFile = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);
if (file_exists($requestedFile) && is_file($requestedFile)) {
    return false; // Dejar que PHP lo maneje normalmente
}

// Si no existe, devolver 404
http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "404 - Archivo no encontrado: " . htmlspecialchars($path) . "\n";
echo "Ruta buscada: " . htmlspecialchars($requestedFile) . "\n";
exit;
