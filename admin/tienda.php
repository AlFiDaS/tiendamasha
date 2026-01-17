<?php
/**
 * Configuración de la Tienda
 */
$pageTitle = 'Configuración de la Tienda';
require_once '../config.php';
require_once '../helpers/shop-settings.php';
require_once '../helpers/upload.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';
startSecureSession();
requireAuth();

$error = '';
$success = '';

// Obtener configuración actual
$settings = getShopSettings();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        // Obtener y normalizar el color
        $primaryColor = '#FF6B35'; // Valor por defecto
        
        // Priorizar el input de texto si está completo y es válido
        if (!empty($_POST['primary_color_text'])) {
            $colorText = trim($_POST['primary_color_text']);
            if (!str_starts_with($colorText, '#')) {
                $colorText = '#' . $colorText;
            }
            if (preg_match('/^#[0-9A-Fa-f]{6}$/i', $colorText)) {
                $primaryColor = strtoupper($colorText);
            }
        } elseif (!empty($_POST['primary_color'])) {
            // Si no hay texto, usar el color picker
            $colorPicker = trim($_POST['primary_color']);
            if (preg_match('/^#[0-9A-Fa-f]{6}$/i', $colorPicker)) {
                $primaryColor = strtoupper($colorPicker);
            }
        }
        
        $formData = [
            'shop_name' => sanitize($_POST['shop_name'] ?? ''),
            'whatsapp_number' => sanitize($_POST['whatsapp_number'] ?? ''),
            'whatsapp_message' => sanitize($_POST['whatsapp_message'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'instagram' => sanitize($_POST['instagram'] ?? ''),
            'facebook' => sanitize($_POST['facebook'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'primary_color' => $primaryColor
        ];
        
        // Validar email si se proporciona
        if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'El email no es válido';
        } else {
            // Procesar logo si se subió
            if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
                // Validar archivo (permite SVG además de imágenes raster)
                $file = $_FILES['shop_logo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $isValid = true;
                
                // Validar extensión (JPG, PNG, WEBP, SVG)
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
                    $error = 'Formato de archivo no permitido. Solo se permiten JPG, PNG, WEBP o SVG.';
                    $isValid = false;
                } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB
                    $error = 'El archivo es demasiado grande (máx. 2MB)';
                    $isValid = false;
                } else {
                    // Validar SVG de manera especial (no es imagen raster)
                    if ($ext === 'svg') {
                        // Leer contenido del SVG para validar
                        $svgContent = file_get_contents($file['tmp_name']);
                        if ($svgContent === false || (strpos($svgContent, '<svg') === false && strpos($svgContent, '<?xml') === false)) {
                            $error = 'El archivo SVG no es válido';
                            $isValid = false;
                        }
                    } else {
                        // Para imágenes raster, usar la validación normal
                        $validation = validateUploadedFile($file);
                        if (!$validation['valid']) {
                            $error = $validation['error'] ?? 'Error al validar el logo';
                            $isValid = false;
                        }
                    }
                }
                
                if ($isValid && empty($error)) {
                    // Crear directorio de logos si no existe
                    $logoDir = IMAGES_PATH . '/logo';
                    if (!is_dir($logoDir)) {
                        if (!mkdir($logoDir, 0755, true)) {
                            $error = 'No se pudo crear el directorio de logos';
                        }
                    }
                    
                    if (empty($error)) {
                        // Generar nombre de archivo: logo.ext
                        $filename = 'logo.' . $ext;
                        $destination = $logoDir . '/' . $filename;
                        
                        // Eliminar logo anterior si existe (puede tener extensión diferente)
                        $existingLogos = glob($logoDir . '/logo.*');
                        foreach ($existingLogos as $existingLogo) {
                            @unlink($existingLogo);
                        }
                        
                        // Mover archivo
                        if (move_uploaded_file($_FILES['shop_logo']['tmp_name'], $destination)) {
                            chmod($destination, 0644);
                            $formData['shop_logo'] = '/images/logo/' . $filename;
                        } else {
                            $error = 'Error al mover el archivo del logo';
                        }
                    }
                }
            } elseif (!empty($settings['shop_logo'])) {
                // Mantener el logo actual si no se subió uno nuevo
                $formData['shop_logo'] = $settings['shop_logo'];
            }
            
            // Si no hay error, actualizar
            if (empty($error)) {
                if (updateShopSettings($formData)) {
                    $success = 'Configuración de la tienda actualizada correctamente';
                    $_SESSION['success_message'] = $success;
                    // Recargar configuración
                    $settings = getShopSettings();
                } else {
                    $error = 'No se pudo actualizar la configuración';
                }
            }
        }
    }
}

require_once '_inc/header.php';
?>

<div class="admin-content">
    <h2>Configuración de la Tienda</h2>
    <p style="color: #666; margin-bottom: 2rem;">Gestiona la información general de tu tienda que aparecerá en el sitio web</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div class="form-group">
            <label for="shop_name">Nombre de la Tienda <span style="color: red;">*</span></label>
            <input 
                type="text" 
                id="shop_name" 
                name="shop_name" 
                value="<?= htmlspecialchars($settings['shop_name'] ?? '') ?>" 
                required
                placeholder="Ej: LUME - Velas Artesanales"
            >
            <small>Este nombre aparecerá en el sitio web</small>
        </div>
        
        <div class="form-group">
            <label for="shop_logo">Logo de la Tienda</label>
            <?php if (!empty($settings['shop_logo'])): ?>
                <?php 
                // Cache busting: agregar timestamp de última modificación
                $logoPath = $settings['shop_logo'];
                $logoFullPath = str_replace(BASE_URL, '', $logoPath);
                $logoFullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($logoFullPath, '/');
                $logoTime = file_exists($logoFullPath) ? filemtime($logoFullPath) : time();
                $logoUrl = $logoPath . '?v=' . $logoTime;
                ?>
                <div style="margin-bottom: 1rem;">
                    <p style="margin-bottom: 0.5rem; font-weight: 600;">Logo actual:</p>
                    <div style="display: inline-block; background: #f5f5f5; padding: 15px; border-radius: 8px; border: 2px solid #ddd;">
                        <img 
                            src="<?= htmlspecialchars($logoUrl) ?>" 
                            alt="Logo actual" 
                            style="max-width: 200px; max-height: 100px; display: block;"
                            onerror="this.style.display='none'"
                        >
                    </div>
                </div>
            <?php endif; ?>
            <input 
                type="file" 
                id="shop_logo" 
                name="shop_logo" 
                accept="image/jpeg,image/png,image/webp,image/svg+xml,.svg"
            >
            <small>Formato: JPG, PNG, WEBP o SVG. Tamaño máximo: 2MB. Este logo aparecerá en el navbar del sitio web.</small>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <h3 style="margin-bottom: 1rem; color: #333;">Información de Contacto</h3>
        
        <div class="form-group">
            <label for="whatsapp_number">Número de WhatsApp</label>
            <input 
                type="text" 
                id="whatsapp_number" 
                name="whatsapp_number" 
                value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>" 
                placeholder="Ej: +5493795330156"
            >
            <small>Formato: +5491234567890 (con código de país y sin espacios)</small>
        </div>
        
        <div class="form-group">
            <label for="whatsapp_message">Mensaje Predefinido para WhatsApp</label>
            <textarea 
                id="whatsapp_message" 
                name="whatsapp_message" 
                rows="3"
                placeholder="Mensaje que se enviará automáticamente cuando alguien haga clic en WhatsApp"
            ><?= htmlspecialchars($settings['whatsapp_message'] ?? '') ?></textarea>
            <small>Mensaje que aparecerá automáticamente al iniciar una conversación por WhatsApp</small>
        </div>
        
        <div class="form-group">
            <label for="email">Email de Contacto</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="<?= htmlspecialchars($settings['email'] ?? '') ?>" 
                placeholder="contacto@tienda.com"
            >
        </div>
        
        <div class="form-group">
            <label for="phone">Teléfono</label>
            <input 
                type="text" 
                id="phone" 
                name="phone" 
                value="<?= htmlspecialchars($settings['phone'] ?? '') ?>" 
                placeholder="Ej: (0379) 15-1234-5678"
            >
        </div>
        
        <div class="form-group">
            <label for="address">Dirección del Local</label>
            <textarea 
                id="address" 
                name="address" 
                rows="3"
                placeholder="Calle, número, ciudad, provincia"
            ><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
            <small>Dirección física del local (si aplica)</small>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <h3 style="margin-bottom: 1rem; color: #333;">Redes Sociales</h3>
        
        <div class="form-group">
            <label for="instagram">Instagram</label>
            <input 
                type="url" 
                id="instagram" 
                name="instagram" 
                value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>" 
                placeholder="https://instagram.com/tu_cuenta"
            >
            <small>URL completa de tu perfil de Instagram</small>
        </div>
        
        <div class="form-group">
            <label for="facebook">Facebook</label>
            <input 
                type="url" 
                id="facebook" 
                name="facebook" 
                value="<?= htmlspecialchars($settings['facebook'] ?? '') ?>" 
                placeholder="https://facebook.com/tu_pagina"
            >
            <small>URL completa de tu página de Facebook</small>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <h3 style="margin-bottom: 1rem; color: #333;">Personalización del Panel</h3>
        
        <div class="form-group">
            <label for="primary_color">Color Primario del Panel</label>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <input 
                    type="color" 
                    id="primary_color" 
                    name="primary_color" 
                    value="<?= htmlspecialchars(strtoupper($settings['primary_color'] ?? '#FF6B35')) ?>"
                    style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 4px; cursor: pointer;"
                >
                <input 
                    type="text" 
                    id="primary_color_text" 
                    name="primary_color_text"
                    value="<?= htmlspecialchars(strtoupper($settings['primary_color'] ?? '#FF6B35')) ?>"
                    placeholder="#FF6B35"
                    pattern="^#[0-9A-Fa-f]{6}$"
                    style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 4px; font-family: monospace;"
                >
            </div>
            <small>Color que se usará en botones, enlaces y elementos destacados del panel administrativo. Formato: #RRGGBB (ej: #FF6B35 para naranja)</small>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <div class="form-group">
            <label for="description">Descripción de la Tienda</label>
            <textarea 
                id="description" 
                name="description" 
                rows="5"
                placeholder="Breve descripción de tu tienda, productos, historia, etc."
            ><?= htmlspecialchars($settings['description'] ?? '') ?></textarea>
            <small>Descripción que puede aparecer en el sitio web (opcional)</small>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary">
                💾 Guardar Configuración
            </button>
            <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-secondary">
                ↩️ Cancelar
            </a>
        </div>
    </form>
</div>

<script>
// Sincronizar el input de color con el input de texto
document.addEventListener('DOMContentLoaded', function() {
    const colorInput = document.getElementById('primary_color');
    const colorText = document.getElementById('primary_color_text');
    
    if (colorInput && colorText) {
        colorInput.addEventListener('input', function() {
            colorText.value = this.value.toUpperCase();
        });
        
        colorText.addEventListener('input', function() {
            if (this.value.match(/^#[0-9A-Fa-f]{6}$/i)) {
                colorInput.value = this.value.toUpperCase();
            }
        });
    }
});
</script>

<?php require_once '_inc/footer.php'; ?>
