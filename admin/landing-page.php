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
$galeriaFeatures = !empty($settings['galeria_features']) ? json_decode($settings['galeria_features'], true) : [
    ['icon' => '💡', 'text' => 'Ideas creativas'],
    ['icon' => '🏠', 'text' => 'Decoración hogar'],
    ['icon' => '✨', 'text' => 'Paso a paso']
];

// Obtener categorías para los links del carrusel
$categories = getAllCategories(true);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        // Procesar carrusel
        // Detectar imágenes eliminadas del carrusel
        $originalCarouselImages = $carouselImages;
        $carouselData = [];
        $imagesToDelete = [];
        
        if (!empty($_POST['carousel_image']) && is_array($_POST['carousel_image'])) {
            foreach ($_POST['carousel_image'] as $index => $imagePath) {
                if (!empty($imagePath)) {
                    $linkType = $_POST['carousel_link_type'][$index] ?? 'none';
                    $linkValue = $_POST['carousel_link_value'][$index] ?? '';
                    
                    $link = '';
                    if ($linkType === 'category' && !empty($linkValue)) {
                        $category = getCategoryBySlug($linkValue);
                        $link = $category ? '/' . $linkValue : '';
                    } elseif ($linkType === 'ideas' && !empty($linkValue)) {
                        $link = '/galeria';
                    }
                    
                    $carouselData[] = [
                        'image' => $imagePath,
                        'link' => $link
                    ];
                }
            }
        }
        
        // Detectar imágenes que fueron eliminadas
        foreach ($originalCarouselImages as $originalItem) {
            $found = false;
            foreach ($carouselData as $newItem) {
                if ($newItem['image'] === $originalItem['image']) {
                    $found = true;
                    break;
                }
            }
            if (!$found && !empty($originalItem['image'])) {
                $imagesToDelete[] = $originalItem['image'];
            }
        }
        
        // Procesar cambios de imagen del carrusel (botón "Cambiar imagen")
        if (!empty($_POST['carousel_change_image_index']) && is_array($_POST['carousel_change_image_index'])) {
            foreach ($_POST['carousel_change_image_index'] as $index => $changeIndex) {
                if (isset($_FILES['carousel_change_image_' . $changeIndex]) && $_FILES['carousel_change_image_' . $changeIndex]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['carousel_change_image_' . $changeIndex];
                    $result = validateUploadedFile($file, ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
                    if ($result['valid']) {
                        // Eliminar imagen antigua si existe
                        if (isset($carouselData[$changeIndex]) && !empty($carouselData[$changeIndex]['image'])) {
                            $oldImagePath = $carouselData[$changeIndex]['image'];
                            $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $oldImagePath);
                            if (file_exists($oldImageFullPath)) {
                                @unlink($oldImageFullPath);
                            }
                        }
                        
                        // Subir nueva imagen
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $newFilename = 'hero_' . time() . '_' . $changeIndex . '.' . $ext;
                        $uploadDir = IMAGES_PATH . '/';
                        
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $destination = $uploadDir . $newFilename;
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $carouselData[$changeIndex]['image'] = '/images/' . $newFilename;
                        }
                    }
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
                                $link = $category ? '/' . $linkValue : '';
                            } elseif ($linkType === 'ideas' && !empty($linkValue)) {
                                $link = '/galeria';
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
                // Eliminar imagen antigua si existe
                if (!empty($settings['sobre_image'])) {
                    $oldImagePath = $settings['sobre_image'];
                    $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $oldImagePath);
                    if (file_exists($oldImageFullPath)) {
                        @unlink($oldImageFullPath);
                    }
                }
                
                // Subir nueva imagen
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
        
        // Procesar eliminación de imagen de Sobre
        if (isset($_POST['delete_sobre_image']) && $_POST['delete_sobre_image'] === '1') {
            if (!empty($settings['sobre_image'])) {
                $oldImagePath = $settings['sobre_image'];
                $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $oldImagePath);
                if (file_exists($oldImageFullPath)) {
                    @unlink($oldImageFullPath);
                }
                $sobreData['image'] = null;
            }
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
        // Procesar features (botones)
        $featuresData = [];
        if (!empty($_POST['galeria_feature_icon']) && !empty($_POST['galeria_feature_text'])) {
            $icons = $_POST['galeria_feature_icon'];
            $texts = $_POST['galeria_feature_text'];
            foreach ($icons as $index => $icon) {
                if (!empty($icon) && !empty($texts[$index])) {
                    $featuresData[] = [
                        'icon' => sanitize($icon),
                        'text' => sanitize($texts[$index])
                    ];
                }
            }
        }
        
        // Procesar nuevas features
        if (!empty($_POST['new_galeria_feature_icon']) && !empty($_POST['new_galeria_feature_text'])) {
            $newIcons = $_POST['new_galeria_feature_icon'];
            $newTexts = $_POST['new_galeria_feature_text'];
            foreach ($newIcons as $index => $icon) {
                if (!empty($icon) && !empty($newTexts[$index])) {
                    $featuresData[] = [
                        'icon' => sanitize($icon),
                        'text' => sanitize($newTexts[$index])
                    ];
                }
            }
        }
        
        $galeriaData = [
            'title' => sanitize($_POST['galeria_title'] ?? ''),
            'description' => sanitize($_POST['galeria_description'] ?? ''),
            'link' => '/galeria', // Link fijo a la galería
            'visible' => isset($_POST['galeria_visible']) ? 1 : 0,
            'badge' => sanitize($_POST['galeria_badge'] ?? '✨ Inspiración'),
            'features' => $featuresData,
            'button_text' => sanitize($_POST['galeria_button_text'] ?? 'Galeria de ideas')
        ];
        
        // Procesar colores (modo claro y oscuro)
        $primaryColorLight = sanitize($_POST['primary_color_light'] ?? '#ff8c00');
        $primaryColorDark = sanitize($_POST['primary_color_dark'] ?? '#ff8c00');
        
        // Validar formato de color hexadecimal
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $primaryColorLight)) {
            $primaryColorLight = '#ff8c00';
        }
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $primaryColorDark)) {
            $primaryColorDark = '#ff8c00';
        }
        
        // Procesar imagen de Galería
        if (isset($_FILES['galeria_image']) && $_FILES['galeria_image']['error'] === UPLOAD_ERR_OK) {
            $result = validateUploadedFile($_FILES['galeria_image'], ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
            if ($result['valid']) {
                // Eliminar imagen antigua si existe
                if (!empty($settings['galeria_image'])) {
                    $oldImagePath = $settings['galeria_image'];
                    $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $oldImagePath);
                    if (file_exists($oldImageFullPath)) {
                        @unlink($oldImageFullPath);
                    }
                }
                
                // Subir nueva imagen
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
        
        // Procesar eliminación de imagen de Galería
        if (isset($_POST['delete_galeria_image']) && $_POST['delete_galeria_image'] === '1') {
            if (!empty($settings['galeria_image'])) {
                $oldImagePath = $settings['galeria_image'];
                $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $oldImagePath);
                if (file_exists($oldImageFullPath)) {
                    @unlink($oldImageFullPath);
                }
                $galeriaData['image'] = null;
            }
        }
        
        // Eliminar imágenes del carrusel que fueron eliminadas
        foreach ($imagesToDelete as $imagePath) {
            $imageFullPath = str_replace('/images/', IMAGES_PATH . '/', $imagePath);
            if (file_exists($imageFullPath)) {
                @unlink($imageFullPath);
            }
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
            'galeria_visible' => $galeriaData['visible'],
            'galeria_badge' => $galeriaData['badge'],
            'galeria_features' => json_encode($galeriaData['features']),
            'galeria_button_text' => $galeriaData['button_text'],
            'primary_color_light' => $primaryColorLight,
            'primary_color_dark' => $primaryColorDark
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
    $galeriaFeatures = !empty($settings['galeria_features']) ? json_decode($settings['galeria_features'], true) : [
        ['icon' => '💡', 'text' => 'Ideas creativas'],
        ['icon' => '🏠', 'text' => 'Decoración hogar'],
        ['icon' => '✨', 'text' => 'Paso a paso']
    ];
}

// Obtener categorías para los links del carrusel
if (!isset($categories)) {
    $categories = getAllCategories(true);
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
                        <input type="hidden" name="carousel_original_image[]" value="<?= htmlspecialchars($item['image'] ?? '') ?>">
                        <div style="display: flex; gap: 1rem; align-items: start;">
                            <div style="flex: 1;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Imagen <?= $index + 1 ?>:</label>
                                <?php if (!empty($item['image'])): ?>
                                    <div style="margin-bottom: 0.5rem;">
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="Carrusel <?= $index + 1 ?>" style="max-width: 200px; max-height: 120px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div style="margin-bottom: 0.5rem;">
                                        <label style="display: block; margin-bottom: 0.25rem; font-size: 0.9rem; color: #666; font-weight: 600;">Cambiar imagen:</label>
                                        <input type="file" name="carousel_change_image_<?= $index ?>" accept="image/jpeg,image/png,image/webp" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                        <input type="hidden" name="carousel_change_image_index[]" value="<?= $index ?>">
                                        <small style="display: block; margin-top: 0.25rem; color: #666;">Formato: JPG, PNG o WEBP. Tamaño máximo: 5MB</small>
                                    </div>
                                <?php else: ?>
                                    <input type="file" name="carousel_change_image_<?= $index ?>" accept="image/jpeg,image/png,image/webp" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                    <input type="hidden" name="carousel_change_image_index[]" value="<?= $index ?>">
                                    <small style="display: block; margin-top: 0.25rem; color: #666;">Formato: JPG, PNG o WEBP. Tamaño máximo: 5MB</small>
                                <?php endif; ?>
                                <input type="hidden" name="carousel_image[]" value="<?= htmlspecialchars($item['image'] ?? '') ?>">
                            </div>
                            <div style="flex: 1;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Link:</label>
                                <select name="carousel_link_type[]" class="carousel-link-type" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 0.5rem;">
                                    <option value="none" <?= empty($item['link']) ? 'selected' : '' ?>>Sin link</option>
                                    <option value="category" <?= !empty($item['link']) && $item['link'] !== '/galeria' && !empty(str_replace('/', '', $item['link'])) ? 'selected' : '' ?>>Categoría</option>
                                    <option value="ideas" <?= !empty($item['link']) && $item['link'] === '/galeria' ? 'selected' : '' ?>>Galería de Ideas</option>
                                </select>
                                <select name="carousel_link_value[]" class="carousel-link-category" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; display: <?= !empty($item['link']) && $item['link'] !== '/galeria' && !empty(str_replace('/', '', $item['link'])) ? 'block' : 'none'; ?>;">
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <?php 
                                        $slug = '';
                                        if (!empty($item['link']) && $item['link'] !== '/galeria') {
                                            $slug = ltrim($item['link'], '/');
                                        }
                                        ?>
                                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $cat['slug'] === $slug ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="carousel_link_value[]" class="carousel-link-ideas" value="/galeria">
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
        
        <!-- SOBRE NOSOTROS -->
        <h3 style="margin-bottom: 1rem; color: #333;">Sección sobre nosotros</h3>
        
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
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Cambiar imagen:</label>
                    <input type="file" id="sobre_image" name="sobre_image" accept="image/jpeg,image/png,image/webp">
                    <small style="display: block; margin-top: 0.25rem;">Formato: JPG, PNG o WEBP. Tamaño máximo: 5MB</small>
                </div>
                <div style="margin-top: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="delete_sobre_image" value="1">
                        <span style="color: #dc3545;">Eliminar imagen</span>
                    </label>
                </div>
            <?php else: ?>
                <input type="file" id="sobre_image" name="sobre_image" accept="image/jpeg,image/png,image/webp">
                <small>Formato: JPG, PNG o WEBP. Tamaño máximo: 5MB</small>
            <?php endif; ?>
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
        <h3 style="margin-bottom: 1rem; color: #333;">Sección de Clientes o Galería</h3>
        
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
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Cambiar imagen:</label>
                    <input type="file" id="galeria_image" name="galeria_image" accept="image/jpeg,image/png,image/webp">
                    <small style="display: block; margin-top: 0.25rem;">Formato: JPG, PNG o WEBP. Tamaño máximo: 5MB</small>
                </div>
                <div style="margin-top: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="delete_galeria_image" value="1">
                        <span style="color: #dc3545;">Eliminar imagen</span>
                    </label>
                </div>
            <?php else: ?>
                <input type="file" id="galeria_image" name="galeria_image" accept="image/jpeg,image/png,image/webp">
                <small>Formato: JPG, PNG o WEBP. Tamaño máximo: 5MB</small>
            <?php endif; ?>
        </div>
        
        <div style="padding: 1rem; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px; margin-bottom: 1rem;">
            <strong>ℹ️ Link:</strong> Esta sección siempre enviará a la galería (<code>/galeria</code>). No es configurable.
        </div>
        
        <div class="form-group">
            <label for="galeria_badge">Badge (puede incluir emoji)</label>
            <input type="text" id="galeria_badge" name="galeria_badge" value="<?= htmlspecialchars($settings['galeria_badge'] ?? '✨ Inspiración') ?>" placeholder="✨ Inspiración">
            <small>Ejemplo: ✨ Inspiración, 🎨 Diseño, etc.</small>
        </div>
        
        <div class="form-group">
            <label for="galeria_button_text">Texto del botón principal</label>
            <input type="text" id="galeria_button_text" name="galeria_button_text" value="<?= htmlspecialchars($settings['galeria_button_text'] ?? 'Galeria de ideas') ?>" placeholder="Galeria de ideas">
        </div>
        
        <h4 style="margin-top: 1.5rem; margin-bottom: 1rem; color: #333;">Botones de características</h4>
        <div id="galeria-features-container">
            <?php if (!empty($galeriaFeatures)): ?>
                <?php foreach ($galeriaFeatures as $index => $feature): ?>
                    <div class="galeria-feature-item" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">
                        <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Icono (emoji)</label>
                                <select name="galeria_feature_icon[]" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1.2rem;">
                                    <option value="">Seleccionar emoji</option>
                                    <option value="💡" <?= ($feature['icon'] ?? '') === '💡' ? 'selected' : '' ?>>💡 Ideas</option>
                                    <option value="🏠" <?= ($feature['icon'] ?? '') === '🏠' ? 'selected' : '' ?>>🏠 Hogar</option>
                                    <option value="✨" <?= ($feature['icon'] ?? '') === '✨' ? 'selected' : '' ?>>✨ Estrella</option>
                                    <option value="🎨" <?= ($feature['icon'] ?? '') === '🎨' ? 'selected' : '' ?>>🎨 Arte</option>
                                    <option value="🎯" <?= ($feature['icon'] ?? '') === '🎯' ? 'selected' : '' ?>>🎯 Objetivo</option>
                                    <option value="💎" <?= ($feature['icon'] ?? '') === '💎' ? 'selected' : '' ?>>💎 Diamante</option>
                                    <option value="🔥" <?= ($feature['icon'] ?? '') === '🔥' ? 'selected' : '' ?>>🔥 Fuego</option>
                                    <option value="⭐" <?= ($feature['icon'] ?? '') === '⭐' ? 'selected' : '' ?>>⭐ Estrella</option>
                                    <option value="🎉" <?= ($feature['icon'] ?? '') === '🎉' ? 'selected' : '' ?>>🎉 Celebración</option>
                                    <option value="🎁" <?= ($feature['icon'] ?? '') === '🎁' ? 'selected' : '' ?>>🎁 Regalo</option>
                                    <option value="❤️" <?= ($feature['icon'] ?? '') === '❤️' ? 'selected' : '' ?>>❤️ Corazón</option>
                                    <option value="🚀" <?= ($feature['icon'] ?? '') === '🚀' ? 'selected' : '' ?>>🚀 Cohete</option>
                                    <option value="🌟" <?= ($feature['icon'] ?? '') === '🌟' ? 'selected' : '' ?>>🌟 Estrella brillante</option>
                                    <option value="💫" <?= ($feature['icon'] ?? '') === '💫' ? 'selected' : '' ?>>💫 Estrella fugaz</option>
                                    <option value="🎊" <?= ($feature['icon'] ?? '') === '🎊' ? 'selected' : '' ?>>🎊 Confeti</option>
                                    <option value="🌈" <?= ($feature['icon'] ?? '') === '🌈' ? 'selected' : '' ?>>🌈 Arcoíris</option>
                                    <option value="🛍️" <?= ($feature['icon'] ?? '') === '🛍️' ? 'selected' : '' ?>>🛍️ Compras</option>
                                    <option value="📱" <?= ($feature['icon'] ?? '') === '📱' ? 'selected' : '' ?>>📱 Móvil</option>
                                    <option value="💻" <?= ($feature['icon'] ?? '') === '💻' ? 'selected' : '' ?>>💻 Computadora</option>
                                    <option value="📸" <?= ($feature['icon'] ?? '') === '📸' ? 'selected' : '' ?>>📸 Foto</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Texto</label>
                                <input type="text" name="galeria_feature_text[]" value="<?= htmlspecialchars($feature['text'] ?? '') ?>" placeholder="Ideas creativas">
                            </div>
                        </div>
                        <button type="button" class="btn-remove-galeria-feature" style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-top: 0.5rem;">Eliminar</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="add-galeria-feature" style="background: var(--primary-color); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; margin-top: 1rem;">
            + Agregar Botón
        </button>
        
        <div id="new-galeria-features"></div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <!-- CONFIGURACIÓN DE COLORES -->
        <h3 style="margin-bottom: 1rem; color: #333;">Configuración de Colores</h3>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 1rem;">
            <div class="form-group">
                <label for="primary_color_light">Color Primario - Modo Claro</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input 
                        type="color" 
                        id="primary_color_light" 
                        name="primary_color_light" 
                        value="<?= htmlspecialchars($settings['primary_color_light'] ?? '#ff8c00') ?>"
                        style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 4px; cursor: pointer;"
                    >
                    <input 
                        type="text" 
                        id="primary_color_light_hex" 
                        value="<?= htmlspecialchars($settings['primary_color_light'] ?? '#ff8c00') ?>"
                        pattern="^#[a-fA-F0-9]{6}$"
                        style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;"
                        placeholder="#ff8c00"
                        onchange="document.getElementById('primary_color_light').value = this.value.toLowerCase();"
                    >
                </div>
                <small style="display: block; margin-top: 0.25rem; color: #666;">Color para páginas en modo claro</small>
            </div>
            
            <div class="form-group">
                <label for="primary_color_dark">Color Primario - Modo Oscuro</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input 
                        type="color" 
                        id="primary_color_dark" 
                        name="primary_color_dark" 
                        value="<?= htmlspecialchars($settings['primary_color_dark'] ?? '#ff8c00') ?>"
                        style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 4px; cursor: pointer;"
                    >
                    <input 
                        type="text" 
                        id="primary_color_dark_hex" 
                        value="<?= htmlspecialchars($settings['primary_color_dark'] ?? '#ff8c00') ?>"
                        pattern="^#[a-fA-F0-9]{6}$"
                        style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;"
                        placeholder="#ff8c00"
                        onchange="document.getElementById('primary_color_dark').value = this.value.toLowerCase();"
                    >
                </div>
                <small style="display: block; margin-top: 0.25rem; color: #666;">Color para páginas en modo oscuro</small>
            </div>
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
                    <input type="hidden" name="new_carousel_link_value[]" class="new-carousel-link-ideas" value="/galeria">
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
    
    // Eliminar features de galería existentes
    document.querySelectorAll('.btn-remove-galeria-feature').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.galeria-feature-item').remove();
        });
    });
    
    // Agregar nueva feature de galería
    document.getElementById('add-galeria-feature').addEventListener('click', function() {
        const container = document.getElementById('new-galeria-features');
        const div = document.createElement('div');
        div.className = 'galeria-feature-item';
        div.style.cssText = 'margin-bottom: 1rem; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;';
        div.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 1rem;">
                <div class="form-group">
                    <label>Icono (emoji)</label>
                    <select name="new_galeria_feature_icon[]" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1.2rem;">
                        <option value="">Seleccionar emoji</option>
                        <option value="💡">💡 Ideas</option>
                        <option value="🏠">🏠 Hogar</option>
                        <option value="✨">✨ Estrella</option>
                        <option value="🎨">🎨 Arte</option>
                        <option value="🎯">🎯 Objetivo</option>
                        <option value="💎">💎 Diamante</option>
                        <option value="🔥">🔥 Fuego</option>
                        <option value="⭐">⭐ Estrella</option>
                        <option value="🎉">🎉 Celebración</option>
                        <option value="🎁">🎁 Regalo</option>
                        <option value="❤️">❤️ Corazón</option>
                        <option value="🚀">🚀 Cohete</option>
                        <option value="🌟">🌟 Estrella brillante</option>
                        <option value="💫">💫 Estrella fugaz</option>
                        <option value="🎊">🎊 Confeti</option>
                        <option value="🌈">🌈 Arcoíris</option>
                        <option value="🛍️">🛍️ Compras</option>
                        <option value="📱">📱 Móvil</option>
                        <option value="💻">💻 Computadora</option>
                        <option value="📸">📸 Foto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Texto</label>
                    <input type="text" name="new_galeria_feature_text[]" placeholder="Ideas creativas">
                </div>
            </div>
            <button type="button" class="btn-remove-new-galeria-feature" style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-top: 0.5rem;">Eliminar</button>
        `;
        container.appendChild(div);
        
        // Manejar eliminación
        div.querySelector('.btn-remove-new-galeria-feature').addEventListener('click', function() {
            div.remove();
        });
    });
    
    // Función para previsualizar imágenes
    function previewImage(input, previewContainer) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Buscar el contenedor de la imagen o crearlo si no existe
                let imgElement = previewContainer.querySelector('img');
                if (!imgElement) {
                    imgElement = document.createElement('img');
                    imgElement.style.cssText = 'max-width: 200px; max-height: 120px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 0.5rem;';
                    previewContainer.insertBefore(imgElement, input.parentElement);
                }
                imgElement.src = e.target.result;
                imgElement.alt = 'Vista previa';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Previsualizar imágenes del carrusel al cambiar
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name && e.target.name.startsWith('carousel_change_image_')) {
            const carouselItem = e.target.closest('.carousel-item');
            if (carouselItem) {
                const imgContainer = carouselItem.querySelector('div[style*="flex: 1"]');
                if (imgContainer) {
                    previewImage(e.target, imgContainer);
                }
            }
        }
    });
    
    // Previsualizar imagen de "Sobre nosotros"
    const sobreImageInput = document.getElementById('sobre_image');
    if (sobreImageInput) {
        sobreImageInput.addEventListener('change', function(e) {
            const formGroup = e.target.closest('.form-group');
            if (formGroup) {
                previewImage(e.target, formGroup);
            }
        });
    }
    
    // Previsualizar imagen de "Galería"
    const galeriaImageInput = document.getElementById('galeria_image');
    if (galeriaImageInput) {
        galeriaImageInput.addEventListener('change', function(e) {
            const formGroup = e.target.closest('.form-group');
            if (formGroup) {
                previewImage(e.target, formGroup);
            }
        });
    }
    
    // Sincronizar selectores de color con inputs de texto hexadecimal
    const colorLightPicker = document.getElementById('primary_color_light');
    const colorLightHex = document.getElementById('primary_color_light_hex');
    const colorDarkPicker = document.getElementById('primary_color_dark');
    const colorDarkHex = document.getElementById('primary_color_dark_hex');
    
    // Sincronizar color picker → input hex (modo claro)
    if (colorLightPicker && colorLightHex) {
        colorLightPicker.addEventListener('input', function() {
            colorLightHex.value = this.value.toUpperCase();
        });
        
        // Sincronizar input hex → color picker (modo claro)
        colorLightHex.addEventListener('input', function() {
            if (/^#[a-fA-F0-9]{6}$/.test(this.value)) {
                colorLightPicker.value = this.value;
            }
        });
    }
    
    // Sincronizar color picker → input hex (modo oscuro)
    if (colorDarkPicker && colorDarkHex) {
        colorDarkPicker.addEventListener('input', function() {
            colorDarkHex.value = this.value.toUpperCase();
        });
        
        // Sincronizar input hex → color picker (modo oscuro)
        colorDarkHex.addEventListener('input', function() {
            if (/^#[a-fA-F0-9]{6}$/.test(this.value)) {
                colorDarkPicker.value = this.value;
            }
        });
        
        // Actualizar el input de color picker antes de enviar el formulario
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                // Asegurar que los valores del color picker estén sincronizados
                if (colorLightHex && /^#[a-fA-F0-9]{6}$/.test(colorLightHex.value)) {
                    colorLightPicker.value = colorLightHex.value.toLowerCase();
                }
                if (colorDarkHex && /^#[a-fA-F0-9]{6}$/.test(colorDarkHex.value)) {
                    colorDarkPicker.value = colorDarkHex.value.toLowerCase();
                }
            });
        }
    }
});
</script>
