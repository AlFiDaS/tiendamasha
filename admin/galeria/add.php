<?php
/**
 * Agregar nuevas imágenes a la galería (hasta 10 a la vez)
 */
$pageTitle = 'Agregar Imágenes a Galería';
require_once '../../config.php';
require_once '../../helpers/upload.php';

// Necesitamos autenticación pero sin incluir el header todavía
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../../helpers/auth.php';
startSecureSession();
requireAuth();

$error = '';
$successMessages = [];
$formData = [];

/**
 * Obtener el siguiente número disponible para las imágenes
 */
function getNextAvailableNumber($prefix = 'idea') {
    // Obtener todos los nombres existentes que empiezan con el prefijo
    $existing = fetchAll("SELECT nombre FROM galeria WHERE nombre LIKE :pattern ORDER BY nombre DESC", 
        ['pattern' => $prefix . '%']);
    
    // Si fetchAll devuelve false (error) o array vacío, empezar desde 1
    if ($existing === false || empty($existing)) {
        return 1;
    }
    
    $maxNumber = 0;
    foreach ($existing as $item) {
        // Extraer el número del nombre (ej: "idea100" -> 100)
        if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $item['nombre'], $matches)) {
            $num = (int)$matches[1];
            if ($num > $maxNumber) {
                $maxNumber = $num;
            }
        }
    }
    
    return $maxNumber + 1;
}

// Procesar formulario ANTES de incluir el header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $formData = [
            'prefijo' => sanitize($_POST['prefijo'] ?? 'idea'),
            'orden_inicial' => 0, // Se calculará automáticamente
            'visible' => isset($_POST['visible']) ? 1 : 0
        ];
        
        // Validar prefijo - ahora permite letras y números
        if (empty($formData['prefijo'])) {
            $error = 'El prefijo es requerido';
        } elseif (!preg_match('/^[a-z0-9]+$/', $formData['prefijo'])) {
            $error = 'El prefijo debe contener solo letras minúsculas y números (ej: idea, idea2, foto1)';
        } else {
            // Procesar múltiples imágenes
            if (empty($_FILES['imagenes']['name'][0])) {
                $error = 'Debes subir al menos una imagen';
            } else {
                $files = $_FILES['imagenes'];
                $fileCount = count($files['name']);
                
                // Limitar a 10 imágenes
                if ($fileCount > 10) {
                    $error = 'Puedes subir máximo 10 imágenes a la vez';
                } else {
                    // Verificar que la tabla galeria existe
                    $tableExists = fetchOne("SHOW TABLES LIKE 'galeria'");
                    if (!$tableExists) {
                        $error = "La tabla 'galeria' no existe en la base de datos.<br><br>" .
                                 "Por favor, ejecuta uno de estos comandos para crearla:<br>" .
                                 "<ul>" .
                                 "<li>Desde la línea de comandos: <code>php crear-tabla-galeria.php</code></li>" .
                                 "<li>O ejecuta: <code>crear-tabla-galeria.bat</code></li>" .
                                 "<li>O importa manualmente el archivo <code>database-galeria.sql</code> en phpMyAdmin</li>" .
                                 "</ul>";
                    } else {
                        $uploaded = 0;
                        $failed = 0;
                        $uploadedPaths = []; // Para limpiar en caso de error
                        $errorDetails = []; // Detalles de errores
                        $startNumber = getNextAvailableNumber($formData['prefijo']);
                        // Calcular orden para que aparezca PRIMERO (orden 1)
                        // Renumerar todos los items existentes para hacer espacio
                        $allExisting = fetchAll("SELECT id FROM galeria ORDER BY orden ASC, id ASC", []);
                        if (!empty($allExisting)) {
                            // Mover todos los items existentes hacia abajo (aumentar su orden en 1)
                            foreach ($allExisting as $existing) {
                                executeQuery("UPDATE galeria SET orden = orden + 1 WHERE id = :id", [
                                    'id' => (int)$existing['id']
                                ]);
                            }
                        }
                        $ordenBase = 0; // Los nuevos items empezarán desde orden 1
                    
                    // Procesar cada archivo
                    for ($i = 0; $i < $fileCount; $i++) {
                        $fileName = $files['name'][$i] ?? 'archivo ' . ($i + 1);
                        
                        // Verificar que el archivo fue subido correctamente
                        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                            $failed++;
                            $uploadError = $files['error'][$i];
                            $errorMsg = "Error en $fileName: ";
                            
                            switch ($uploadError) {
                                case UPLOAD_ERR_INI_SIZE:
                                case UPLOAD_ERR_FORM_SIZE:
                                    $errorMsg .= "El archivo excede el tamaño máximo";
                                    break;
                                case UPLOAD_ERR_PARTIAL:
                                    $errorMsg .= "El archivo se subió parcialmente";
                                    break;
                                case UPLOAD_ERR_NO_FILE:
                                    $errorMsg .= "No se subió ningún archivo";
                                    break;
                                case UPLOAD_ERR_NO_TMP_DIR:
                                    $errorMsg .= "Falta el directorio temporal";
                                    break;
                                case UPLOAD_ERR_CANT_WRITE:
                                    $errorMsg .= "Error al escribir el archivo";
                                    break;
                                case UPLOAD_ERR_EXTENSION:
                                    $errorMsg .= "Una extensión de PHP detuvo la subida";
                                    break;
                                default:
                                    $errorMsg .= "Error desconocido (código: $uploadError)";
                            }
                            
                            $errorDetails[] = $errorMsg;
                            continue;
                        }
                        
                        // Verificar que el archivo temporal existe
                        if (empty($files['tmp_name'][$i]) || !is_uploaded_file($files['tmp_name'][$i])) {
                            $failed++;
                            $errorDetails[] = "Error en $fileName: Archivo temporal no válido";
                            continue;
                        }
                        
                        // Crear array de archivo individual para la función de upload
                        $file = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        // Generar nombre único
                        $nombre = $formData['prefijo'] . $startNumber;
                        // Calcular orden automáticamente: MAX(orden) + 1 + contador de subidas
                        $orden = $ordenBase + $uploaded + 1;
                        
                        // Subir imagen
                        $uploadResult = uploadGaleriaImage($file, $nombre);
                        if ($uploadResult['success']) {
                            // Insertar en BD
                            $sql = "INSERT INTO galeria (nombre, imagen, alt, orden, visible) 
                                    VALUES (:nombre, :imagen, :alt, :orden, :visible)";
                            
                            $params = [
                                'nombre' => $nombre,
                                'imagen' => $uploadResult['path'],
                                'alt' => ucfirst($nombre), // Alt por defecto
                                'orden' => $orden,
                                'visible' => $formData['visible']
                            ];
                            
                            $result = executeQuery($sql, $params);
                            if ($result) {
                                $uploaded++;
                                $startNumber++;
                            } else {
                                // Si falla la inserción, marcar para eliminar después
                                $uploadedPaths[] = $uploadResult['path'];
                                $failed++;
                                
                                // Obtener el último error de PDO para más detalles
                                $pdo = getDB();
                                $errorInfo = $pdo ? $pdo->errorInfo() : null;
                                $errorMsg = "Error en $fileName: No se pudo guardar en la base de datos";
                                
                                if ($errorInfo && !empty($errorInfo[2])) {
                                    $errorMsg .= " - " . $errorInfo[2];
                                }
                                
                                // Verificar si la tabla existe
                                $tableExists = fetchOne("SHOW TABLES LIKE 'galeria'");
                                if (!$tableExists) {
                                    $errorMsg .= ". <strong>La tabla 'galeria' no existe. Ejecuta: php crear-tabla-galeria.php</strong>";
                                }
                                
                                $errorDetails[] = $errorMsg;
                            }
                        } else {
                            $failed++;
                            $errorDetails[] = "Error en $fileName: " . ($uploadResult['error'] ?? 'Error desconocido');
                        }
                    }
                    
                        // Limpiar imágenes que no se guardaron en BD
                        foreach ($uploadedPaths as $path) {
                            deleteGaleriaImage($path);
                        }
                        
                        if ($uploaded > 0) {
                            $_SESSION['success_message'] = "Se agregaron exitosamente $uploaded imagen(es)" . 
                                ($failed > 0 ? ". $failed imagen(es) fallaron." : "");
                            header('Location: list.php');
                            exit;
                        } elseif ($failed > 0) {
                            $error = "No se pudo subir ninguna imagen.<br><br><strong>Detalles:</strong><ul>";
                            foreach ($errorDetails as $detail) {
                                $error .= "<li>" . htmlspecialchars($detail) . "</li>";
                            }
                            $error .= "</ul>";
                        }
                    }
                }
            }
        }
    }
}

require_once '../_inc/header.php';
?>

<div class="admin-content">
    <h2>Agregar Imágenes a Galería</h2>
    <p style="color: #666; margin-bottom: 1.5rem;">Puedes subir hasta 10 imágenes a la vez. Los nombres se generarán automáticamente.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" style="max-width: 700px;">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div class="form-group">
            <label for="prefijo">Prefijo del nombre *</label>
            <input type="text" 
                   id="prefijo" 
                   name="prefijo" 
                   value="<?= htmlspecialchars($formData['prefijo'] ?? 'idea') ?>" 
                   placeholder="idea"
                   pattern="[a-z0-9]+"
                   required>
            <small>Prefijo para los nombres de las imágenes (ej: "idea" generará idea1, idea2, idea3... o "idea2" generará idea21, idea22...). Letras minúsculas y números.</small>
        </div>
        
        <div class="form-group">
            <label for="imagenes">Imágenes (hasta 10) *</label>
            <input type="file" 
                   id="imagenes" 
                   name="imagenes[]" 
                   accept="image/jpeg,image/png,image/webp"
                   multiple
                   required>
            <small>Formatos permitidos: JPG, PNG, WEBP. Máximo: <?= UPLOAD_MAX_SIZE / 1024 / 1024 ?>MB por imagen. Puedes seleccionar múltiples archivos (Ctrl+Click o Cmd+Click).</small>
            <div id="file-count" style="margin-top: 0.5rem; color: #666; font-size: 0.875rem;"></div>
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" 
                       id="visible" 
                       name="visible" 
                       <?= isset($formData['visible']) && $formData['visible'] ? 'checked' : 'checked' ?>>
                <label for="visible">Visible en la web</label>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">Subir Imágenes</button>
            <a href="list.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Mostrar cantidad de archivos seleccionados
document.getElementById('imagenes').addEventListener('change', function(e) {
    const count = this.files.length;
    const fileCount = document.getElementById('file-count');
    
    if (count > 0) {
        if (count > 10) {
            fileCount.innerHTML = '<span style="color: #dc3545;">⚠️ Has seleccionado ' + count + ' archivos. Solo se procesarán los primeros 10.</span>';
        } else {
            fileCount.innerHTML = '✅ ' + count + ' archivo(s) seleccionado(s)';
        }
    } else {
        fileCount.innerHTML = '';
    }
});
</script>

<?php require_once '../_inc/footer.php'; ?>

