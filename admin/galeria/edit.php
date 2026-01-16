<?php
/**
 * Editar imagen de la galería
 */
$pageTitle = 'Editar Imagen de Galería';
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
$itemId = (int)($_GET['id'] ?? 0);

// Validar ID antes de procesar POST
if (empty($itemId)) {
    $_SESSION['error_message'] = 'ID de imagen no válido';
    header('Location: list.php');
    exit;
}

// Obtener imagen
$item = fetchOne("SELECT * FROM galeria WHERE id = :id", ['id' => $itemId]);

if (!$item) {
    $_SESSION['error_message'] = 'Imagen no encontrada';
    header('Location: list.php');
    exit;
}

$formData = $item;

// Procesar formulario ANTES de incluir el header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $formData = [
            'alt' => sanitize($_POST['alt'] ?? ''),
            // 'orden' ya no se puede editar desde aquí, solo con las flechas
            'orden' => $item['orden'], // Mantener el orden actual
            'visible' => isset($_POST['visible']) ? 1 : 0,
            'imagen' => $item['imagen']
        ];
        
        // Procesar nueva imagen si se subió
        if (!empty($_FILES['imagen']['name'])) {
            // Eliminar imagen anterior
            deleteGaleriaImage($item['imagen']);
            
            // Subir nueva imagen con el mismo nombre
            $uploadResult = uploadGaleriaImage($_FILES['imagen'], $item['nombre']);
            if ($uploadResult['success']) {
                $formData['imagen'] = $uploadResult['path'];
            } else {
                $error = $uploadResult['error'];
            }
        }
        
        if (empty($error)) {
            // Actualizar en BD
            $sql = "UPDATE galeria SET 
                    imagen = :imagen,
                    alt = :alt,
                    orden = :orden,
                    visible = :visible
                    WHERE id = :id";
            
            $params = [
                'id' => $itemId,
                'imagen' => $formData['imagen'],
                'alt' => $formData['alt'],
                'orden' => $formData['orden'],
                'visible' => $formData['visible']
            ];
            
            if (executeQuery($sql, $params)) {
                $_SESSION['success_message'] = 'Imagen actualizada exitosamente';
                header('Location: list.php');
                exit;
            } else {
                $error = 'Error al actualizar en la base de datos';
            }
        }
    }
}

require_once '../_inc/header.php';
?>

<div class="admin-content">
    <h2>Editar Imagen de Galería</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" style="max-width: 600px;">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div class="form-group">
            <label>Nombre (no editable)</label>
            <input type="text" 
                   value="<?= htmlspecialchars($item['nombre']) ?>" 
                   disabled
                   style="background: #f0f0f0;">
            <small>El nombre no se puede cambiar después de crear la imagen.</small>
        </div>
        
        <div class="form-group">
            <label>Imagen actual</label>
            <?php 
            $imageUrl = preg_replace('/\?.*$/', '', $item['imagen']);
            $fullImageUrl = BASE_URL . $imageUrl;
            ?>
            <img src="<?= $fullImageUrl ?>" 
                 alt="<?= htmlspecialchars($item['alt'] ?? $item['nombre']) ?>" 
                 style="max-width: 300px; height: auto; border-radius: 4px; margin-bottom: 1rem;"
                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\'%3E%3Crect fill=\'%23f0f0f0\' width=\'300\' height=\'200\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ESin img%3C/text%3E%3C/svg%3E';">
        </div>
        
        <div class="form-group">
            <label for="imagen">Nueva Imagen (opcional)</label>
            <input type="file" 
                   id="imagen" 
                   name="imagen" 
                   accept="image/jpeg,image/png,image/webp">
            <small>Deja vacío para mantener la imagen actual. Formatos: JPG, PNG, WEBP. Máximo: <?= UPLOAD_MAX_SIZE / 1024 / 1024 ?>MB</small>
        </div>
        
        <div class="form-group">
            <label for="alt">Texto alternativo (Alt)</label>
            <input type="text" 
                   id="alt" 
                   name="alt" 
                   value="<?= htmlspecialchars($formData['alt'] ?? '') ?>" 
                   placeholder="Descripción de la imagen">
            <small>Texto que se mostrará si la imagen no carga o para accesibilidad.</small>
        </div>
        
        <div class="form-group">
            <div style="padding: 1rem; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px; margin-bottom: 1rem;">
                <strong>ℹ️ Orden:</strong> Para cambiar el orden de esta imagen, usa las flechas (← → o ↑ ↓) en la página de <a href="list.php" style="color: #1976d2; text-decoration: underline;">lista de imágenes</a>.
            </div>
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" 
                       id="visible" 
                       name="visible" 
                       <?= isset($formData['visible']) && $formData['visible'] ? 'checked' : '' ?>>
                <label for="visible">Visible en la web</label>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="list.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../_inc/footer.php'; ?>

