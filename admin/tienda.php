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
        $instagram = trim(sanitize($_POST['instagram'] ?? ''));
        $facebook = trim(sanitize($_POST['facebook'] ?? ''));
        if ($instagram && !preg_match('#^https?://#i', $instagram)) {
            $instagram = preg_replace('/^@/', '', $instagram);
            $instagram = 'https://www.instagram.com/' . $instagram . '/';
        }
        if ($facebook && !preg_match('#^https?://#i', $facebook)) {
            $facebook = preg_replace('/^@/', '', $facebook);
            $facebook = 'https://www.facebook.com/' . $facebook . '/';
        }
        $formData = [
            'shop_name' => sanitize($_POST['shop_name'] ?? ''),
            'whatsapp_number' => sanitize($_POST['whatsapp_number'] ?? ''),
            'whatsapp_message' => sanitize($_POST['whatsapp_message'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'instagram' => $instagram,
            'facebook' => $facebook,
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'footer_description' => sanitize($_POST['footer_description'] ?? ''),
            'footer_copyright' => sanitize($_POST['footer_copyright'] ?? ''),
            'creation_year' => !empty($_POST['creation_year']) ? (int)$_POST['creation_year'] : null
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
                    // Subcarpeta por tienda para que cada una tenga su propio logo
                    $storeSlug = defined('CURRENT_STORE_SLUG') ? preg_replace('/[^a-z0-9\-]/', '', strtolower(CURRENT_STORE_SLUG)) : 'default';
                    $logoDir = IMAGES_PATH . '/logo/' . $storeSlug;
                    if (!is_dir($logoDir)) {
                        if (!mkdir($logoDir, 0755, true)) {
                            $error = 'No se pudo crear el directorio de logos';
                        }
                    }
                    
                    if (empty($error)) {
                        $filename = 'logo.' . $ext;
                        $destination = $logoDir . '/' . $filename;
                        
                        // Eliminar solo los logos de ESTA tienda (no de otras)
                        $existingLogos = glob($logoDir . '/logo.*');
                        foreach ($existingLogos as $existingLogo) {
                            @unlink($existingLogo);
                        }
                        
                        if (move_uploaded_file($_FILES['shop_logo']['tmp_name'], $destination)) {
                            chmod($destination, 0644);
                            $formData['shop_logo'] = '/images/logo/' . $storeSlug . '/' . $filename;
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
                $updateError = '';
                if (updateShopSettings($formData, $updateError)) {
                    $success = 'Configuración de la tienda actualizada correctamente';
                    $_SESSION['success_message'] = $success;
                    // Recargar configuración
                    $settings = getShopSettings();
                } else {
                    $error = $updateError ?: 'No se pudo actualizar la configuración';
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
    
    <form method="POST" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>" enctype="multipart/form-data">
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
                type="text" 
                id="instagram" 
                name="instagram" 
                value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>" 
                placeholder="test2 o https://instagram.com/tu_cuenta"
            >
            <small>Usuario (ej: test2) o URL completa. Se guardará como enlace a instagram.com</small>
        </div>
        
        <div class="form-group">
            <label for="facebook">Facebook</label>
            <input 
                type="text" 
                id="facebook" 
                name="facebook" 
                value="<?= htmlspecialchars($settings['facebook'] ?? '') ?>" 
                placeholder="tu_pagina o https://facebook.com/tu_pagina"
            >
            <small>Usuario o URL completa. Se guardará como enlace a facebook.com</small>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <h3 style="margin-bottom: 1rem; color: #333;">Información General</h3>
        
        <div class="form-group">
            <label for="description">Descripción para SEO</label>
            <textarea 
                id="description" 
                name="description" 
                rows="5"
                placeholder="Breve descripción para buscadores (meta description, redes sociales)"
            ><?= htmlspecialchars($settings['description'] ?? '') ?></textarea>
            <small>Se usa en meta description, Open Graph y Twitter. Afecta cómo aparece tu tienda en Google y al compartir en redes.</small>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <h3 style="margin-bottom: 1rem; color: #333;">Configuración del Footer</h3>
        
        <div class="form-group">
            <label for="footer_description">Descripción del Footer</label>
            <textarea 
                id="footer_description" 
                name="footer_description" 
                rows="3"
                placeholder="Iluminando momentos especiales con velas artesanales únicas"
            ><?= htmlspecialchars($settings['footer_description'] ?? '') ?></textarea>
            <small>Texto que aparece debajo del nombre de la tienda en el pie de página del sitio. Es independiente de la descripción SEO.</small>
        </div>
        
        <div class="form-group">
            <label for="creation_year">Año de Creación</label>
            <input 
                type="number" 
                id="creation_year" 
                name="creation_year" 
                value="<?= htmlspecialchars($settings['creation_year'] ?? date('Y')) ?>" 
                placeholder="<?= date('Y') ?>"
                min="1900"
                max="<?= date('Y') ?>"
            >
            <small>El copyright mostrará: © [AÑO] [NOMBRE_DE_TIENDA]. Todos los derechos reservados.</small>
        </div>
        
        <input 
            type="hidden" 
            id="footer_copyright" 
            name="footer_copyright" 
            value="<?= htmlspecialchars($settings['footer_copyright'] ?? '') ?>"
        >
        
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
document.addEventListener('DOMContentLoaded', function() {
    // Previsualizar logo al cambiar
    const shopLogoInput = document.getElementById('shop_logo');
    if (shopLogoInput) {
        shopLogoInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Buscar el contenedor del logo
                    const formGroup = shopLogoInput.closest('.form-group');
                    if (formGroup) {
                        // Buscar la imagen existente o crear el contenedor
                        let logoContainer = formGroup.querySelector('div[style*="margin-bottom: 1rem"]');
                        if (!logoContainer) {
                            // Si no hay contenedor, crear uno nuevo antes del input
                            logoContainer = document.createElement('div');
                            logoContainer.style.cssText = 'margin-bottom: 1rem;';
                            shopLogoInput.parentElement.insertBefore(logoContainer, shopLogoInput);
                        }
                        
                        // Buscar o crear la imagen
                        let imgElement = logoContainer.querySelector('img');
                        if (!imgElement) {
                            imgElement = document.createElement('img');
                            imgElement.style.cssText = 'max-width: 200px; max-height: 100px; display: block; border: 2px solid #ddd; border-radius: 8px; padding: 5px; background: #f5f5f5;';
                            imgElement.alt = 'Vista previa del logo';
                            logoContainer.insertBefore(imgElement, logoContainer.firstChild);
                        }
                        
                        // Actualizar la imagen
                        imgElement.src = e.target.result;
                    }
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }
});
</script>

<?php require_once '_inc/footer.php'; ?>
