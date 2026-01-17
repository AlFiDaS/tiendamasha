<?php
/**
 * Configuración de Landing Page
 */
$pageTitle = 'Landing Page';
require_once '../config.php';
require_once '../helpers/shop-settings.php';
require_once '../helpers/upload.php';
require_once '../helpers/categories.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';
startSecureSession();
requireAuth();

$error = '';
$success = '';

// Obtener configuración actual
$settings = fetchOne("SELECT * FROM landing_page_settings WHERE id = 1 LIMIT 1");
if (!$settings) {
    // Crear registro inicial
    executeQuery("INSERT INTO landing_page_settings (id) VALUES (1)");
    $settings = fetchOne("SELECT * FROM landing_page_settings WHERE id = 1 LIMIT 1");
}

// Decodificar JSONs
$carouselImages = !empty($settings['carousel_images']) ? json_decode($settings['carousel_images'], true) : [];
$testimonials = !empty($settings['testimonials']) ? json_decode($settings['testimonials'], true) : [];

// Obtener categorías para los links del carrusel
$categories = getAllCategories(true);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        // Procesar carrusel
        $carouselData = [];
        if (!empty($_POST['carousel_image']) && is_array($_POST['carousel_image'])) {
            foreach ($_POST['carousel_image'] as $index => $imagePath) {
                if (!empty($imagePath)) {
                    $linkType = $_POST['carousel_link_type'][$index] ?? 'none';
                    $linkValue = $_POST['carousel_link_value'][$index] ?? '';
                    
                    $link = '';
                    if ($linkType === 'category' && !empty($linkValue)) {
                        $category = getCategoryBySlug($linkValue);
                        $link = $category ? '/categoria/' . $linkValue : '';
                    } elseif ($linkType === 'ideas' && !empty($linkValue)) {
                        $link = '/ideas';
                    }
                    
                    $carouselData[] = [
                        'image' => $imagePath,
                        'link' => $link
                    ];
                }
            }
        }
        
        // Procesar nuevas imágenes del carrusel
        if (isset($_FILES['new_carousel_images']) && !empty($_FILES['new_carousel_images']['name'][0])) {
            foreach ($_FILES['new_carousel_images']['name'] as $index => $filename) {
                if ($_FILES['new_carousel_images']['error'][$index] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['new_carousel_images']['name'][$index],
                        'type' => $_FILES['new_carousel_images']['type'][$index],
                        'tmp_name' => $_FILES['new_carousel_images']['tmp_name'][$index],
                        'error' => $_FILES['new_carousel_images']['error'][$index],
                        'size' => $_FILES['new_carousel_images']['size'][$index]
                    ];
                    
                    $result = validateUploadedFile($file, ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
                    if ($result['valid']) {
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $newFilename = 'hero_' . time() . '_' . $index . '.' . $ext;
                        $uploadDir = IMAGES_PATH . '/';
                        
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $destination = $uploadDir . $newFilename;
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $linkType = $_POST['new_carousel_link_type'][$index] ?? 'none';
                            $linkValue = $_POST['new_carousel_link_value'][$index] ?? '';
                            
                            $link = '';
                            if ($linkType === 'category' && !empty($linkValue)) {
                                $category = getCategoryBySlug($linkValue);
                                $link = $category ? '/categoria/' . $linkValue : '';
                            } elseif ($linkType === 'ideas' && !empty($linkValue)) {
                                $link = '/ideas';
                            }
                            
                            $carouselData[] = [
                                'image' => '/images/' . $newFilename,
                                'link' => $link
                            ];
                        }
                    }
                }
            }
        }
        
        // Procesar Sobre Lume
        $sobreData = [
            'title' => sanitize($_POST['sobre_title'] ?? 'Sobre LUME'),
            'text_1' => sanitize($_POST['sobre_text_1'] ?? ''),
            'text_2' => sanitize($_POST['sobre_text_2'] ?? ''),
            'stat_1_number' => sanitize($_POST['sobre_stat_1_number'] ?? ''),
            'stat_1_label' => sanitize($_POST['sobre_stat_1_label'] ?? ''),
            'stat_2_number' => sanitize($_POST['sobre_stat_2_number'] ?? ''),
            'stat_2_label' => sanitize($_POST['sobre_stat_2_label'] ?? ''),
            'stat_3_number' => sanitize($_POST['sobre_stat_3_number'] ?? ''),
            'stat_3_label' => sanitize($_POST['sobre_stat_3_label'] ?? '')
        ];
        
        // Procesar imagen de Sobre
        if (isset($_FILES['sobre_image']) && $_FILES['sobre_image']['error'] === UPLOAD_ERR_OK) {
            $result = validateUploadedFile($_FILES['sobre_image'], ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
            if ($result['valid']) {
                $ext = strtolower(pathinfo($_FILES['sobre_image']['name'], PATHINFO_EXTENSION));
                $newFilename = 'sobre_' . time() . '.' . $ext;
                $uploadDir = IMAGES_PATH . '/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $destination = $uploadDir . $newFilename;
                if (move_uploaded_file($_FILES['sobre_image']['tmp_name'], $destination)) {
                    $sobreData['image'] = '/images/' . $newFilename;
                }
            }
        } elseif (!empty($settings['sobre_image'])) {
            $sobreData['image'] = $settings['sobre_image'];
        }
        
        // Procesar comentarios
        $testimonialsData = [];
        if (!empty($_POST['testimonial_text']) && is_array($_POST['testimonial_text'])) {
            foreach ($_POST['testimonial_text'] as $index => $text) {
                if (!empty($text)) {
                    $testimonialsData[] = [
                        'text' => sanitize($text),
                        'client_name' => sanitize($_POST['testimonial_client_name'][$index] ?? ''),
                        'stars' => sanitize($_POST['testimonial_stars'][$index] ?? '⭐⭐⭐⭐⭐')
                    ];
                }
            }
        }
        
        // Procesar nuevos comentarios
        if (!empty($_POST['new_testimonial_text']) && is_array($_POST['new_testimonial_text'])) {
            foreach ($_POST['new_testimonial_text'] as $index => $text) {
                if (!empty($text)) {
                    $testimonialsData[] = [
                        'text' => sanitize($text),
                        'client_name' => sanitize($_POST['new_testimonial_client_name'][$index] ?? ''),
                        'stars' => sanitize($_POST['new_testimonial_stars'][$index] ?? '⭐⭐⭐⭐⭐')
                    ];
                }
            }
        }
        
        // Procesar Galería de Ideas
        $galeriaData = [
            'title' => sanitize($_POST['galeria_title'] ?? ''),
            'description' => sanitize($_POST['galeria_description'] ?? ''),
            'link' => sanitize($_POST['galeria_link'] ?? '/ideas'),
            'visible' => isset($_POST['galeria_visible']) ? 1 : 0
        ];
        
        // Procesar imagen de Galería
        if (isset($_FILES['galeria_image']) && $_FILES['galeria_image']['error'] === UPLOAD_ERR_OK) {
            $result = validateUploadedFile($_FILES['galeria_image'], ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
            if ($result['valid']) {
                $ext = strtolower(pathinfo($_FILES['galeria_image']['name'], PATHINFO_EXTENSION));
                $newFilename = 'galeria_ideas_' . time() . '.' . $ext;
                $uploadDir = IMAGES_PATH . '/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $destination = $uploadDir . $newFilename;
                if (move_uploaded_file($_FILES['galeria_image']['tmp_name'], $destination)) {
                    $galeriaData['image'] = '/images/' . $newFilename;
                }
            }
        } elseif (!empty($settings['galeria_image'])) {
            $galeriaData['image'] = $settings['galeria_image'];
        }
        
        // Actualizar base de datos
        $updateData = [
            'carousel_images' => json_encode($carouselData),
            'sobre_title' => $sobreData['title'],
            'sobre_text_1' => $sobreData['text_1'],
            'sobre_text_2' => $sobreData['text_2'],
            'sobre_image' => $sobreData['image'] ?? null,
            'sobre_stat_1_number' => $sobreData['stat_1_number'],
            'sobre_stat_1_label' => $sobreData['stat_1_label'],
            'sobre_stat_2_number' => $sobreData['stat_2_number'],
            'sobre_stat_2_label' => $sobreData['stat_2_label'],
            'sobre_stat_3_number' => $sobreData['stat_3_number'],
            'sobre_stat_3_label' => $sobreData['stat_3_label'],
            'testimonials' => json_encode($testimonialsData),
            'testimonials_visible' => isset($_POST['testimonials_visible']) ? 1 : 0,
            'galeria_title' => $galeriaData['title'],
            'galeria_description' => $galeriaData['description'],
            'galeria_image' => $galeriaData['image'] ?? null,
            'galeria_link' => $galeriaData['link'],
            'galeria_visible' => $galeriaData['visible']
        ];
        
        $updateFields = [];
        $params = ['id' => 1];
        
        foreach ($updateData as $field => $value) {
            $updateFields[] = "`{$field}` = :{$field}";
            $params[$field] = $value;
        }
        
        $sql = "UPDATE landing_page_settings SET " . implode(', ', $updateFields) . " WHERE id = :id";
        
        if (executeQuery($sql, $params)) {
            $success = 'Configuración de la landing page actualizada correctamente';
            $_SESSION['success_message'] = $success;
            header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=1');
            exit;
        } else {
            $error = 'No se pudo actualizar la configuración';
        }
    }
}

// Verificar si se actualizó correctamente
if (isset($_GET['updated'])) {
    $success = 'Configuración de la landing page actualizada correctamente';
    $settings = fetchOne("SELECT * FROM landing_page_settings WHERE id = 1 LIMIT 1");
    $carouselImages = !empty($settings['carousel_images']) ? json_decode($settings['carousel_images'], true) : [];
    $testimonials = !empty($settings['testimonials']) ? json_decode($settings['testimonials'], true) : [];
}

require_once '_inc/header.php';
?>

<div class="admin-content">
    <h2>Configuración de Landing Page</h2>
    <p style="color: #666; margin-bottom: 2rem;">Gestiona el contenido de la página principal (index)</p>
    
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
        
        <!-- CARRUSEL -->
        <h3 style="margin-bottom: 1rem; color: #333; margin-top: 2rem;">Carrusel de Imágenes</h3>
        <p style="color: #666; margin-bottom: 1rem; font-size: 0.9rem;">Las imágenes del carrusel aparecen en la parte superior de la página principal. Puedes agregar links a categorías o a la galería de ideas.</p>
        
        <div id="carousel-container">
            <?php if (!empty($carouselImages)): ?>
                <?php foreach ($carouselImages as $index => $item): ?>
                    <div class="carousel-item" style="margin-bottom: 1.5rem; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">
                        <div style="display: flex; gap: 1rem; align-items: start;">
                            <div style="flex: 1;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Imagen <?= $index + 1 ?>:</label>
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="Carrusel" style="max-width: 200px; max-height: 120px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 0.5rem;">
                                <?php endif; ?>
                                <input type="text" name="carousel_image[]" value="<?= htmlspecialchars($item['image'] ?? '') ?>" placeholder="/images/hero.webp" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                <small style="display: block; margin-top: 0.25rem; color: #666;">Ruta de la imagen (ej: /images/hero.webp)</small>
                            </div>
                            <div style="flex: 1;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Link:</label>
                                <select name="carousel_link_type[]" class="carousel-link-type" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 0.5rem;">
                                    <option value="none" <?= empty($item['link']) ? 'selected' : '' ?>>Sin link</option>
                                    <option value="category" <?= !empty($item['link']) && strpos($item['link'], '/categoria/') !== false ? 'selected' : '' ?>>Categoría</option>
                                    <option value="ideas" <?= !empty($item['link']) && $item['link'] === '/ideas' ? 'selected' : '' ?>>Galería de Ideas</option>
                                </select>
                                <select name="carousel_link_value[]" class="carousel-link-category" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; display: <?= !empty($item['link']) && strpos($item['link'], '/categoria/') !== false ? 'block' : 'none'; ?>;">
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <?php 
                                        $slug = '';
                                        if (!empty($item['link']) && strpos($item['link'], '/categoria/') !== false) {
                                            $slug = str_replace('/categoria/', '', $item['link']);
                                        }
                                        ?>
                                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $cat['slug'] === $slug ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="carousel_link_value[]" class="carousel-link-ideas" value="/ideas" style="display: none;">
                            </div>
                            <div>
                                <button type="button" class="btn-remove-carousel" style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Eliminar</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="add-carousel-item" style="background: var(--primary-color); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; margin-top: 1rem;">
            + Agregar Imagen al Carrusel
        </button>
        
        <div id="new-carousel-items" style="margin-top: 1rem;"></div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <!-- SOBRE LUME -->
        <h3 style="margin-bottom: 1rem; color: #333;">Sección "Sobre Lume"</h3>
        
        <div class="form-group">
            <label for="sobre_title">Título</label>
            <input type="text" id="sobre_title" name="sobre_title" value="<?= htmlspecialchars($settings['sobre_title'] ?? 'Sobre LUME') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="sobre_text_1">Primer Párrafo</label>
            <textarea id="sobre_text_1" name="sobre_text_1" rows="3" required><?= htmlspecialchars($settings['sobre_text_1'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="sobre_text_2">Segundo Párrafo</label>
            <textarea id="sobre_text_2" name="sobre_text_2" rows="3" required><?= htmlspecialchars($settings['sobre_text_2'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="sobre_image">Imagen</label>
            <?php if (!empty($settings['sobre_image'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="<?= htmlspecialchars($settings['sobre_image']) ?>" alt="Sobre" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            <?php endif; ?>
            <input type="file" id="sobre_image" name="sobre_image" accept="image/jpeg,image/png,image/webp">
            <small>Formato: JPG, PNG o WEBP. Tamaño máximo: 5MB</small>
        </div>
        
        <h4 style="margin-top: 1.5rem; margin-bottom: 1rem; color: #333;">Estadísticas</h4>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            <div class="form-group">
                <label for="sobre_stat_1_number">Número 1</label>
                <input type="text" id="sobre_stat_1_number" name="sobre_stat_1_number" value="<?= htmlspecialchars($settings['sobre_stat_1_number'] ?? '') ?>" placeholder="500+">
            </div>
            <div class="form-group">
                <label for="sobre_stat_1_label">Label 1</label>
                <input type="text" id="sobre_stat_1_label" name="sobre_stat_1_label" value="<?= htmlspecialchars($settings['sobre_stat_1_label'] ?? '') ?>" placeholder="Clientes felices">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem;">
            <div class="form-group">
                <label for="sobre_stat_2_number">Número 2</label>
                <input type="text" id="sobre_stat_2_number" name="sobre_stat_2_number" value="<?= htmlspecialchars($settings['sobre_stat_2_number'] ?? '') ?>" placeholder="50+">
            </div>
            <div class="form-group">
                <label for="sobre_stat_2_label">Label 2</label>
                <input type="text" id="sobre_stat_2_label" name="sobre_stat_2_label" value="<?= htmlspecialchars($settings['sobre_stat_2_label'] ?? '') ?>" placeholder="Diseños únicos">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem;">
            <div class="form-group">
                <label for="sobre_stat_3_number">Número 3</label>
                <input type="text" id="sobre_stat_3_number" name="sobre_stat_3_number" value="<?= htmlspecialchars($settings['sobre_stat_3_number'] ?? '') ?>" placeholder="2">
            </div>
            <div class="form-group">
                <label for="sobre_stat_3_label">Label 3</label>
                <input type="text" id="sobre_stat_3_label" name="sobre_stat_3_label" value="<?= htmlspecialchars($settings['sobre_stat_3_label'] ?? '') ?>" placeholder="Años de experiencia">
            </div>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <!-- COMENTARIOS -->
        <h3 style="margin-bottom: 1rem; color: #333;">Comentarios de Clientes</h3>
        
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="testimonials_visible" value="1" <?= ($settings['testimonials_visible'] ?? 1) ? 'checked' : '' ?>>
                <span>Mostrar comentarios de clientes</span>
            </label>
        </div>
        
        <div id="testimonials-container">
            <?php if (!empty($testimonials)): ?>
                <?php foreach ($testimonials as $index => $testimonial): ?>
                    <div class="testimonial-item" style="margin-bottom: 1.5rem; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">
                        <div class="form-group">
                            <label>Comentario <?= $index + 1 ?>:</label>
                            <textarea name="testimonial_text[]" rows="3" required><?= htmlspecialchars($testimonial['text'] ?? '') ?></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Nombre del Cliente:</label>
                                <input type="text" name="testimonial_client_name[]" value="<?= htmlspecialchars($testimonial['client_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Estrellas:</label>
                                <input type="text" name="testimonial_stars[]" value="<?= htmlspecialchars($testimonial['stars'] ?? '⭐⭐⭐⭐⭐') ?>" placeholder="⭐⭐⭐⭐⭐">
                            </div>
                        </div>
                        <button type="button" class="btn-remove-testimonial" style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-top: 0.5rem;">Eliminar</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="add-testimonial" style="background: var(--primary-color); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; margin-top: 1rem;">
            + Agregar Comentario
        </button>
        
        <div id="new-testimonials"></div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <!-- GALERÍA DE IDEAS -->
        <h3 style="margin-bottom: 1rem; color: #333;">Galería de Ideas</h3>
        
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="galeria_visible" value="1" <?= ($settings['galeria_visible'] ?? 1) ? 'checked' : '' ?>>
                <span>Mostrar sección de Galería de Ideas</span>
            </label>
        </div>
        
        <div class="form-group">
            <label for="galeria_title">Título</label>
            <input type="text" id="galeria_title" name="galeria_title" value="<?= htmlspecialchars($settings['galeria_title'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="galeria_description">Descripción</label>
            <textarea id="galeria_description" name="galeria_description" rows="3" required><?= htmlspecialchars($settings['galeria_description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="galeria_image">Imagen</label>
            <?php if (!empty($settings['galeria_image'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="<?= htmlspecialchars($settings['galeria_image']) ?>" alt="Galería" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            <?php endif; ?>
            <input type="file" id="galeria_image" name="galeria_image" accept="image/jpeg,image/png,image/webp">
            <small>Formato: JPG, PNG o WEBP. Tamaño máximo: 5MB</small>
        </div>
        
        <div class="form-group">
            <label for="galeria_link">Link</label>
            <input type="text" id="galeria_link" name="galeria_link" value="<?= htmlspecialchars($settings['galeria_link'] ?? '/ideas') ?>" placeholder="/ideas">
            <small>URL de destino (ej: /ideas)</small>
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
document.addEventListener('DOMContentLoaded', function() {
    // Manejar links del carrusel
    document.querySelectorAll('.carousel-link-type').forEach(function(select) {
        select.addEventListener('change', function() {
            const categorySelect = this.parentElement.querySelector('.carousel-link-category');
            const ideasInput = this.parentElement.querySelector('.carousel-link-ideas');
            
            if (this.value === 'category') {
                categorySelect.style.display = 'block';
                ideasInput.style.display = 'none';
            } else if (this.value === 'ideas') {
                categorySelect.style.display = 'none';
                ideasInput.style.display = 'none';
            } else {
                categorySelect.style.display = 'none';
                ideasInput.style.display = 'none';
            }
        });
    });
    
    // Agregar nuevo item al carrusel
    let carouselIndex = <?= count($carouselImages) ?>;
    document.getElementById('add-carousel-item').addEventListener('click', function() {
        const container = document.getElementById('new-carousel-items');
        const div = document.createElement('div');
        div.className = 'new-carousel-item';
        div.style.cssText = 'margin-bottom: 1.5rem; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;';
        div.innerHTML = `
            <div style="display: flex; gap: 1rem; align-items: start;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Nueva Imagen:</label>
                    <input type="file" name="new_carousel_images[]" accept="image/jpeg,image/png,image/webp" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    <small style="display: block; margin-top: 0.25rem; color: #666;">Formato: JPG, PNG o WEBP. Tamaño máximo: 5MB</small>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Link:</label>
                    <select name="new_carousel_link_type[]" class="new-carousel-link-type" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 0.5rem;">
                        <option value="none">Sin link</option>
                        <option value="category">Categoría</option>
                        <option value="ideas">Galería de Ideas</option>
                    </select>
                    <select name="new_carousel_link_value[]" class="new-carousel-link-category" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; display: none;">
                        <option value="">Seleccionar categoría</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="new_carousel_link_value[]" class="new-carousel-link-ideas" value="/ideas">
                </div>
                <div>
                    <button type="button" class="btn-remove-new-carousel" style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Eliminar</button>
                </div>
            </div>
        `;
        container.appendChild(div);
        
        // Manejar cambio de tipo de link
        div.querySelector('.new-carousel-link-type').addEventListener('change', function() {
            const categorySelect = this.parentElement.querySelector('.new-carousel-link-category');
            if (this.value === 'category') {
                categorySelect.style.display = 'block';
            } else {
                categorySelect.style.display = 'none';
            }
        });
        
        // Eliminar item
        div.querySelector('.btn-remove-new-carousel').addEventListener('click', function() {
            div.remove();
        });
    });
    
    // Eliminar items del carrusel existentes
    document.querySelectorAll('.btn-remove-carousel').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.carousel-item').remove();
        });
    });
    
    // Agregar nuevo comentario
    let testimonialIndex = <?= count($testimonials) ?>;
    document.getElementById('add-testimonial').addEventListener('click', function() {
        const container = document.getElementById('new-testimonials');
        const div = document.createElement('div');
        div.className = 'new-testimonial-item';
        div.style.cssText = 'margin-bottom: 1.5rem; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;';
        div.innerHTML = `
            <div class="form-group">
                <label>Nuevo Comentario:</label>
                <textarea name="new_testimonial_text[]" rows="3" required></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Nombre del Cliente:</label>
                    <input type="text" name="new_testimonial_client_name[]" required>
                </div>
                <div class="form-group">
                    <label>Estrellas:</label>
                    <input type="text" name="new_testimonial_stars[]" value="⭐⭐⭐⭐⭐" placeholder="⭐⭐⭐⭐⭐">
                </div>
            </div>
            <button type="button" class="btn-remove-new-testimonial" style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-top: 0.5rem;">Eliminar</button>
        `;
        container.appendChild(div);
        
        div.querySelector('.btn-remove-new-testimonial').addEventListener('click', function() {
            div.remove();
        });
    });
    
    // Eliminar comentarios existentes
    document.querySelectorAll('.btn-remove-testimonial').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.testimonial-item').remove();
        });
    });
});
</script>
