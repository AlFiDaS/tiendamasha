<?php
/**
 * ============================================
 * HELPER: Subida de Archivos
 * ============================================
 * Manejo seguro de subida de imágenes
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Validar archivo subido
 * @param array $file ($_FILES['campo'])
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateUploadedFile($file) {
    // Verificar que no hubo errores
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'error' => 'Parámetros inválidos'];
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['valid' => false, 'error' => 'No se subió ningún archivo'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['valid' => false, 'error' => 'El archivo excede el tamaño máximo permitido'];
        default:
            return ['valid' => false, 'error' => 'Error desconocido al subir el archivo'];
    }
    
    // Verificar tamaño
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['valid' => false, 'error' => 'El archivo es demasiado grande (máx. ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB)'];
    }
    
    // Verificar tipo MIME
    $mimeType = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    
    // Si finfo no está disponible, usar el tipo del archivo subido
    if (!$mimeType && !empty($file['type'])) {
        $mimeType = $file['type'];
    }
    
    // Si todavía no tenemos tipo, intentar con mime_content_type
    if (!$mimeType && function_exists('mime_content_type')) {
        $mimeType = mime_content_type($file['tmp_name']);
    }
    
    if ($mimeType && !in_array($mimeType, UPLOAD_ALLOWED_TYPES)) {
        return ['valid' => false, 'error' => 'Tipo de archivo no permitido. Solo JPG, PNG y WEBP. Tipo detectado: ' . $mimeType];
    }
    
    // Verificar extensión
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_EXTENSIONS)) {
        return ['valid' => false, 'error' => 'Extensión de archivo no permitida'];
    }
    
    // Validación adicional: Verificar que es realmente una imagen usando getimagesize
    // Esto previene ataques donde se cambia la extensión pero el contenido no es una imagen
    require_once __DIR__ . '/security.php';
    $imageValidation = validateImageContent($file['tmp_name'], UPLOAD_ALLOWED_TYPES);
    
    if (!$imageValidation['valid']) {
        return ['valid' => false, 'error' => $imageValidation['error']];
    }
    
    // Verificar que el tipo MIME detectado por getimagesize coincide con el detectado anteriormente
    $realMime = $imageValidation['imageInfo']['mime'] ?? null;
    if ($realMime && $mimeType && $realMime !== $mimeType) {
        // Si hay discrepancia, confiar en getimagesize (más seguro)
        if (!in_array($realMime, UPLOAD_ALLOWED_TYPES)) {
            return ['valid' => false, 'error' => 'El contenido del archivo no coincide con su extensión. Tipo real: ' . $realMime];
        }
        $mimeType = $realMime; // Usar el tipo real
    }
    
    return ['valid' => true, 'error' => null, 'mime' => $mimeType, 'ext' => $ext, 'imageInfo' => $imageValidation['imageInfo']];
}

/**
 * Subir imagen de producto
 * @param array $file ($_FILES['campo'])
 * @param string $slug (slug del producto, ej: 'arbolito-navideno')
 * @param string $categoria (categoría: 'productos', 'souvenirs', 'navidad')
 * @param string $filename (nombre del archivo sin extensión, ej: 'main' o 'hover')
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function uploadProductImage($file, $slug, $categoria, $filename = 'main') {
    // Validar archivo
    $validation = validateUploadedFile($file);
    if (!$validation['valid']) {
        return ['success' => false, 'path' => null, 'error' => $validation['error']];
    }
    
    // Validar que la categoría no esté vacía (la validación de existencia se hace antes de llamar a esta función)
    if (empty($categoria)) {
        return ['success' => false, 'path' => null, 'error' => 'Categoría no especificada'];
    }
    
    // Crear estructura de directorios: images/categoria/slug/
    $uploadDir = IMAGES_PATH . '/' . $categoria . '/' . $slug;
    
    // Verificar que el directorio base de imágenes existe
    if (!is_dir(IMAGES_PATH)) {
        // Intentar crear el directorio base si no existe
        if (!mkdir(IMAGES_PATH, 0755, true)) {
            return ['success' => false, 'path' => null, 'error' => 'No se pudo crear el directorio base de imágenes: ' . IMAGES_PATH];
        }
    }
    
    // Verificar que el directorio de categoría existe
    $categoriaDir = IMAGES_PATH . '/' . $categoria;
    if (!is_dir($categoriaDir)) {
        if (!mkdir($categoriaDir, 0755, true)) {
            return ['success' => false, 'path' => null, 'error' => 'No se pudo crear el directorio de categoría: ' . $categoriaDir];
        }
    }
    
    // Crear directorio del producto si no existe
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $lastError = error_get_last();
            $errorMsg = 'No se pudo crear el directorio de destino: ' . $uploadDir;
            if ($lastError) {
                $errorMsg .= ' - Error: ' . $lastError['message'];
            }
            return ['success' => false, 'path' => null, 'error' => $errorMsg];
        }
    }
    
    // Generar nombre de archivo
    $ext = $validation['ext'];
    $newFilename = $filename . '.' . $ext;
    $destination = $uploadDir . '/' . $newFilename;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'path' => null, 'error' => 'Error al mover el archivo'];
    }
    
    // Asegurar permisos
    chmod($destination, 0644);
    
    // Retornar ruta relativa para la BD: /images/categoria/slug/filename.ext
    $relativePath = '/images/' . $categoria . '/' . $slug . '/' . $newFilename;
    
    return ['success' => true, 'path' => $relativePath, 'error' => null];
}

/**
 * Subir comprobante de pago (transferencia)
 * @param array $file ($_FILES['campo'])
 * @param int $orderId ID de la orden (para nombrar el archivo)
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function uploadProofImage($file, $orderId) {
    // Validar archivo
    $validation = validateUploadedFile($file);
    if (!$validation['valid']) {
        return ['success' => false, 'path' => null, 'error' => $validation['error']];
    }
    
    // Crear directorio de comprobantes si no existe
    $proofsDir = IMAGES_PATH . '/proofs';
    if (!is_dir($proofsDir)) {
        if (!mkdir($proofsDir, 0755, true)) {
            return ['success' => false, 'path' => null, 'error' => 'No se pudo crear el directorio de comprobantes'];
        }
    }
    
    // Generar nombre de archivo: order_{orderId}_{timestamp}.ext
    $ext = $validation['ext'];
    $timestamp = time();
    $filename = 'order_' . $orderId . '_' . $timestamp . '.' . $ext;
    $destination = $proofsDir . '/' . $filename;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'path' => null, 'error' => 'Error al mover el archivo'];
    }
    
    // Asegurar permisos
    chmod($destination, 0644);
    
    // Retornar ruta relativa: /images/proofs/order_{id}_{timestamp}.ext
    $relativePath = '/images/proofs/' . $filename;
    
    return ['success' => true, 'path' => $relativePath, 'error' => null];
}

/**
 * Eliminar imagen
 * @param string $imagePath (ruta relativa desde raíz, ej: '/images/vela-xoxo/main.webp')
 * @return bool
 */
function deleteProductImage($imagePath) {
    if (empty($imagePath)) {
        return true; // No hay imagen que eliminar
    }
    
    // Remover parámetros de cache busting si existen
    $cleanPath = preg_replace('/\?.*$/', '', $imagePath);
    
    // Construir ruta completa usando IMAGES_PATH (ya está configurado correctamente según el entorno)
    // La ruta relativa viene como /images/... así que necesitamos construir la ruta física
    // Remover el prefijo /images/ de la ruta relativa
    $relativePath = preg_replace('#^/images/#', '', $cleanPath);
    $fullPath = IMAGES_PATH . '/' . $relativePath;
    
    // Verificar que el archivo existe y está en el directorio de imágenes
    if (file_exists($fullPath) && strpos($fullPath, IMAGES_PATH) !== false) {
        return @unlink($fullPath);
    }
    
    return false;
}

/**
 * Mover imágenes de producto de una categoría a otra
 * @param string $slug (slug del producto)
 * @param string $categoriaAnterior (categoría actual)
 * @param string $categoriaNueva (nueva categoría)
 * @return array ['success' => bool, 'imagePath' => string|null, 'hoverImagePath' => string|null, 'error' => string|null]
 */
function moveProductImages($slug, $categoriaAnterior, $categoriaNueva) {
    if (empty($slug) || empty($categoriaAnterior) || empty($categoriaNueva)) {
        return ['success' => false, 'imagePath' => null, 'hoverImagePath' => null, 'error' => 'Parámetros inválidos'];
    }
    
    // Si la categoría no cambió, no hacer nada
    if ($categoriaAnterior === $categoriaNueva) {
        return ['success' => true, 'imagePath' => null, 'hoverImagePath' => null, 'error' => null];
    }
    
    // Validar que las categorías no estén vacías (la validación de existencia se hace antes de llamar a esta función)
    if (empty($categoriaAnterior) || empty($categoriaNueva)) {
        return ['success' => false, 'imagePath' => null, 'hoverImagePath' => null, 'error' => 'Categorías no especificadas'];
    }
    
    $oldFolder = IMAGES_PATH . '/' . $categoriaAnterior . '/' . $slug;
    $newFolder = IMAGES_PATH . '/' . $categoriaNueva . '/' . $slug;
    
    // Si la carpeta antigua no existe, no hay nada que mover
    if (!is_dir($oldFolder)) {
        return ['success' => true, 'imagePath' => null, 'hoverImagePath' => null, 'error' => null];
    }
    
    // Crear la nueva carpeta si no existe
    if (!is_dir($newFolder)) {
        if (!mkdir($newFolder, 0755, true)) {
            return ['success' => false, 'imagePath' => null, 'hoverImagePath' => null, 'error' => 'No se pudo crear la nueva carpeta'];
        }
    }
    
    // Obtener todos los archivos de la carpeta antigua
    $files = array_diff(scandir($oldFolder), ['.', '..']);
    
    if (empty($files)) {
        // Si no hay archivos, eliminar la carpeta antigua y terminar
        @rmdir($oldFolder);
        return ['success' => true, 'imagePath' => null, 'hoverImagePath' => null, 'error' => null];
    }
    
    $newImagePath = null;
    $newHoverImagePath = null;
    $allMoved = true;
    
    // Mover cada archivo
    foreach ($files as $file) {
        $oldPath = $oldFolder . '/' . $file;
        $newPath = $newFolder . '/' . $file;
        
        if (is_file($oldPath)) {
            // Intentar mover el archivo
            if (@rename($oldPath, $newPath)) {
                chmod($newPath, 0644);
                
                // Actualizar rutas según el nombre del archivo
                $relativePath = '/images/' . $categoriaNueva . '/' . $slug . '/' . $file;
                if (strpos($file, 'main.') === 0 || $file === 'main.jpg' || $file === 'main.png' || $file === 'main.webp') {
                    $newImagePath = $relativePath;
                } elseif (strpos($file, 'hover.') === 0 || $file === 'hover.jpg' || $file === 'hover.png' || $file === 'hover.webp') {
                    $newHoverImagePath = $relativePath;
                }
            } else {
                $allMoved = false;
            }
        }
    }
    
    // Si todos los archivos se movieron, eliminar la carpeta antigua
    if ($allMoved) {
        @rmdir($oldFolder);
    }
    
    return [
        'success' => $allMoved,
        'imagePath' => $newImagePath,
        'hoverImagePath' => $newHoverImagePath,
        'error' => $allMoved ? null : 'Algunos archivos no se pudieron mover'
    ];
}

/**
 * Limpiar carpeta de imágenes (eliminar carpeta vacía)
 * @param string $slug (slug del producto)
 * @param string $categoria (categoría del producto)
 * @return bool
 */
function cleanupImageFolder($slug, $categoria) {
    if (empty($slug) || empty($categoria)) {
        return true;
    }
    
    // Nueva estructura: images/categoria/slug/
    $folderPath = IMAGES_PATH . '/' . $categoria . '/' . $slug;
    
    if (!is_dir($folderPath)) {
        return true;
    }
    
    // Verificar si la carpeta está vacía
    $files = array_diff(scandir($folderPath), ['.', '..']);
    
    if (empty($files)) {
        // Intentar eliminar la carpeta del slug
        @rmdir($folderPath);
        return true;
    }
    
    // Si hay archivos, intentar eliminarlos todos
    $allDeleted = true;
    foreach ($files as $file) {
        $filePath = $folderPath . '/' . $file;
        if (is_file($filePath)) {
            if (!@unlink($filePath)) {
                $allDeleted = false;
            }
        }
    }
    
    // Si todos los archivos fueron eliminados, eliminar la carpeta del slug
    if ($allDeleted) {
        @rmdir($folderPath);
        return true;
    }
    
    return false;
}

/**
 * Subir imagen de galería
 * @param array $file ($_FILES['campo'])
 * @param string $nombre (nombre del archivo sin extensión, ej: 'idea1', 'idea100')
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function uploadGaleriaImage($file, $nombre) {
    // Validar que el archivo temporal existe
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'path' => null, 'error' => 'Archivo temporal no válido o no subido correctamente'];
    }
    
    // Validar archivo
    $validation = validateUploadedFile($file);
    if (!$validation['valid']) {
        return ['success' => false, 'path' => null, 'error' => $validation['error']];
    }
    
    // Crear directorio si no existe
    $uploadDir = IMAGES_PATH . '/0_galeria';
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            $errorMsg = 'No se pudo crear el directorio de destino: ' . $uploadDir;
            if (!is_writable(dirname($uploadDir))) {
                $errorMsg .= '. El directorio padre no tiene permisos de escritura.';
            }
            return ['success' => false, 'path' => null, 'error' => $errorMsg];
        }
    }
    
    // Verificar que el directorio es escribible
    if (!is_writable($uploadDir)) {
        return ['success' => false, 'path' => null, 'error' => 'El directorio de destino no tiene permisos de escritura'];
    }
    
    // Generar nombre de archivo
    $ext = $validation['ext'];
    $newFilename = $nombre . '.' . $ext;
    $destination = $uploadDir . '/' . $newFilename;
    
    // Si el archivo ya existe, intentar eliminarlo primero (con manejo de errores)
    if (file_exists($destination)) {
        // Intentar eliminar, pero no fallar si no se puede (puede estar en uso)
        @unlink($destination);
        // Esperar un momento para asegurar que el archivo se liberó
        usleep(100000); // 100ms
        
        // Si todavía existe después de esperar, intentar renombrar en lugar de eliminar
        if (file_exists($destination)) {
            $backupName = $destination . '.backup.' . time();
            @rename($destination, $backupName);
        }
    }
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'path' => null, 'error' => 'Error al mover el archivo'];
    }
    
    // Asegurar permisos
    chmod($destination, 0644);
    
    // Retornar ruta relativa para la BD
    $relativePath = '/images/0_galeria/' . $newFilename;
    
    return ['success' => true, 'path' => $relativePath, 'error' => null];
}

/**
 * Eliminar imagen de galería
 * @param string $imagePath (ruta relativa desde raíz, ej: '/images/0_galeria/idea1.webp')
 * @return bool
 */
function deleteGaleriaImage($imagePath) {
    if (empty($imagePath)) {
        return true; // No hay imagen que eliminar
    }
    
    // Construir ruta completa
    $fullPath = BASE_PATH . '/public' . $imagePath;
    
    // Verificar que el archivo existe y está en el directorio de galería
    if (file_exists($fullPath) && strpos($imagePath, '/images/0_galeria/') === 0) {
        // Intentar eliminar con manejo de errores
        $result = @unlink($fullPath);
        // Si falla, esperar un momento y reintentar una vez
        if (!$result && file_exists($fullPath)) {
            usleep(200000); // 200ms
            $result = @unlink($fullPath);
        }
        return $result;
    }
    
    return true; // Si no existe, considerarlo como éxito
}

/**
 * Eliminar imagen de comprobante de pago
 * @param string $proofImagePath (ruta relativa desde raíz, ej: '/images/proofs/order_123_1234567890.jpg')
 * @return bool
 */
function deleteProofImage($proofImagePath) {
    if (empty($proofImagePath)) {
        return true; // No hay imagen que eliminar
    }
    
    // Verificar que la ruta es de comprobantes
    if (strpos($proofImagePath, '/images/proofs/') !== 0) {
        return false; // Ruta inválida
    }
    
    // Remover parámetros de cache busting si existen
    $cleanPath = preg_replace('/\?.*$/', '', $proofImagePath);
    
    // Construir ruta completa usando IMAGES_PATH (ya está configurado correctamente según el entorno)
    // La ruta relativa viene como /images/proofs/... así que necesitamos construir la ruta física
    // Remover el prefijo /images/ de la ruta relativa
    $relativePath = preg_replace('#^/images/#', '', $cleanPath);
    $fullPath = IMAGES_PATH . '/' . $relativePath;
    
    // Verificar que el archivo existe y está en el directorio de comprobantes
    if (file_exists($fullPath)) {
        // Verificar seguridad: asegurar que la ruta está dentro de IMAGES_PATH/proofs/
        $realPath = realpath($fullPath);
        $imagesPathReal = realpath(IMAGES_PATH . '/proofs');
        
        if ($realPath && $imagesPathReal && strpos($realPath, $imagesPathReal) === 0) {
            return @unlink($fullPath);
        }
    }
    
    return false;
}

