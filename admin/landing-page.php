<?php
/**
 * Configuración de Landing Page - Editor visual idéntico al index
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

$settings = fetchOne("SELECT * FROM landing_page_settings WHERE id = 1 LIMIT 1");
if (!$settings) {
    executeQuery("INSERT INTO landing_page_settings (id) VALUES (1)");
    $settings = fetchOne("SELECT * FROM landing_page_settings WHERE id = 1 LIMIT 1");
}

$carouselImages = !empty($settings['carousel_images']) ? json_decode($settings['carousel_images'], true) : [];
$testimonials = !empty($settings['testimonials']) ? json_decode($settings['testimonials'], true) : [];
$galeriaFeatures = !empty($settings['galeria_features']) ? json_decode($settings['galeria_features'], true) : [
    ['icon' => '💡', 'text' => 'Ideas creativas'],
    ['icon' => '🏠', 'text' => 'Decoración hogar'],
    ['icon' => '✨', 'text' => 'Paso a paso']
];

$categories = getAllCategories(true);

// Procesar formulario (misma lógica que antes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
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
                    $carouselData[] = ['image' => $imagePath, 'link' => $link];
                }
            }
        }

        foreach ($originalCarouselImages as $originalItem) {
            $found = false;
            foreach ($carouselData as $newItem) {
                if ($newItem['image'] === $originalItem['image']) { $found = true; break; }
            }
            if (!$found && !empty($originalItem['image'])) {
                $imagesToDelete[] = $originalItem['image'];
            }
        }

        if (!empty($_POST['carousel_change_image_index']) && is_array($_POST['carousel_change_image_index'])) {
            foreach ($_POST['carousel_change_image_index'] as $index => $changeIndex) {
                if (isset($_FILES['carousel_change_image_' . $changeIndex]) && $_FILES['carousel_change_image_' . $changeIndex]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['carousel_change_image_' . $changeIndex];
                    $result = validateUploadedFile($file, ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
                    if ($result['valid'] && isset($carouselData[$changeIndex])) {
                        if (!empty($carouselData[$changeIndex]['image'])) {
                            $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $carouselData[$changeIndex]['image']);
                            if (file_exists($oldImageFullPath)) { @unlink($oldImageFullPath); }
                        }
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $newFilename = 'hero_' . time() . '_' . $changeIndex . '.' . $ext;
                        $uploadDir = IMAGES_PATH . '/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $destination = $uploadDir . $newFilename;
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $carouselData[$changeIndex]['image'] = '/images/' . $newFilename;
                        }
                    }
                }
            }
        }

        $maxCarouselImages = 5;
        $maxNewFromForm = isset($_POST['new_carousel_max']) ? max(0, (int)$_POST['new_carousel_max']) : 999;
        if (isset($_FILES['new_carousel_images']) && !empty($_FILES['new_carousel_images']['name'][0])) {
            foreach ($_FILES['new_carousel_images']['name'] as $index => $filename) {
                if ($index >= $maxNewFromForm || count($carouselData) >= $maxCarouselImages) break;
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
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $destination = $uploadDir . $newFilename;
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $linkType = $_POST['new_carousel_link_type'][$index] ?? 'none';
                            $linkValue = $_POST['new_carousel_link_value'][$index] ?? '';
                            $link = '';
                            if ($linkType === 'category' && !empty($linkValue)) {
                                $category = getCategoryBySlug($linkValue);
                                $link = $category ? '/' . $linkValue : '';
                            } elseif ($linkType === 'ideas' && !empty($linkValue)) { $link = '/galeria'; }
                            $carouselData[] = ['image' => '/images/' . $newFilename, 'link' => $link];
                        }
                    }
                }
            }
        }
        $carouselData = array_slice($carouselData, 0, $maxCarouselImages);

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

        if (isset($_FILES['sobre_image']) && $_FILES['sobre_image']['error'] === UPLOAD_ERR_OK) {
            $result = validateUploadedFile($_FILES['sobre_image'], ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
            if ($result['valid']) {
                if (!empty($settings['sobre_image'])) {
                    $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $settings['sobre_image']);
                    if (file_exists($oldImageFullPath)) { @unlink($oldImageFullPath); }
                }
                $ext = strtolower(pathinfo($_FILES['sobre_image']['name'], PATHINFO_EXTENSION));
                $newFilename = 'sobre_' . time() . '.' . $ext;
                $uploadDir = IMAGES_PATH . '/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $destination = $uploadDir . $newFilename;
                if (move_uploaded_file($_FILES['sobre_image']['tmp_name'], $destination)) {
                    $sobreData['image'] = '/images/' . $newFilename;
                }
            }
        } elseif (!empty($settings['sobre_image'])) {
            $sobreData['image'] = $settings['sobre_image'];
        }
        if (isset($_POST['delete_sobre_image']) && $_POST['delete_sobre_image'] === '1') {
            if (!empty($settings['sobre_image'])) {
                $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $settings['sobre_image']);
                if (file_exists($oldImageFullPath)) { @unlink($oldImageFullPath); }
            }
            $sobreData['image'] = null;
        }

        $validStars = ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'];
        $testimonialsData = [];
        $allTexts = array_merge(
            isset($_POST['testimonial_text']) && is_array($_POST['testimonial_text']) ? $_POST['testimonial_text'] : [],
            isset($_POST['new_testimonial_text']) && is_array($_POST['new_testimonial_text']) ? $_POST['new_testimonial_text'] : []
        );
        $allNames = array_merge(
            isset($_POST['testimonial_client_name']) && is_array($_POST['testimonial_client_name']) ? $_POST['testimonial_client_name'] : [],
            isset($_POST['new_testimonial_client_name']) && is_array($_POST['new_testimonial_client_name']) ? $_POST['new_testimonial_client_name'] : []
        );
        $allStars = array_merge(
            isset($_POST['testimonial_stars']) && is_array($_POST['testimonial_stars']) ? $_POST['testimonial_stars'] : [],
            isset($_POST['new_testimonial_stars']) && is_array($_POST['new_testimonial_stars']) ? $_POST['new_testimonial_stars'] : []
        );
        foreach ($allTexts as $index => $text) {
            if (!empty($text) && count($testimonialsData) < 5) {
                $stars = $allStars[$index] ?? '⭐⭐⭐⭐⭐';
                if (!in_array($stars, $validStars, true)) {
                    $stars = '⭐⭐⭐⭐⭐';
                }
                $testimonialsData[] = [
                    'text' => sanitize($text),
                    'client_name' => sanitize($allNames[$index] ?? ''),
                    'stars' => $stars
                ];
            }
        }

        $featuresData = [];
        if (!empty($_POST['galeria_feature_icon']) && !empty($_POST['galeria_feature_text'])) {
            $icons = $_POST['galeria_feature_icon'];
            $texts = $_POST['galeria_feature_text'];
            foreach ($icons as $index => $icon) {
                if (!empty($icon) && !empty($texts[$index])) {
                    $featuresData[] = ['icon' => sanitize($icon), 'text' => sanitize($texts[$index])];
                }
            }
        }
        if (!empty($_POST['new_galeria_feature_icon']) && !empty($_POST['new_galeria_feature_text'])) {
            $newIcons = $_POST['new_galeria_feature_icon'];
            $newTexts = $_POST['new_galeria_feature_text'];
            foreach ($newIcons as $index => $icon) {
                if (!empty($icon) && !empty($newTexts[$index])) {
                    $featuresData[] = ['icon' => sanitize($icon), 'text' => sanitize($newTexts[$index])];
                }
            }
        }

        $galeriaData = [
            'title' => sanitize($_POST['galeria_title'] ?? ''),
            'description' => sanitize($_POST['galeria_description'] ?? ''),
            'link' => '/galeria',
            'visible' => isset($_POST['galeria_visible']) ? 1 : 0,
            'badge' => sanitize($_POST['galeria_badge'] ?? '✨ Inspiración'),
            'features' => $featuresData,
            'button_text' => sanitize($_POST['galeria_button_text'] ?? 'Galeria de ideas')
        ];

        $primaryColorLight = sanitize($_POST['primary_color_light'] ?? '#ff8c00');
        $primaryColorDark = sanitize($_POST['primary_color_dark'] ?? '#ff8c00');
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $primaryColorLight)) $primaryColorLight = '#ff8c00';
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $primaryColorDark)) $primaryColorDark = '#ff8c00';

        if (isset($_FILES['galeria_image']) && $_FILES['galeria_image']['error'] === UPLOAD_ERR_OK) {
            $result = validateUploadedFile($_FILES['galeria_image'], ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
            if ($result['valid']) {
                if (!empty($settings['galeria_image'])) {
                    $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $settings['galeria_image']);
                    if (file_exists($oldImageFullPath)) { @unlink($oldImageFullPath); }
                }
                $ext = strtolower(pathinfo($_FILES['galeria_image']['name'], PATHINFO_EXTENSION));
                $newFilename = 'galeria_ideas_' . time() . '.' . $ext;
                $uploadDir = IMAGES_PATH . '/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $destination = $uploadDir . $newFilename;
                if (move_uploaded_file($_FILES['galeria_image']['tmp_name'], $destination)) {
                    $galeriaData['image'] = '/images/' . $newFilename;
                }
            }
        } elseif (!empty($settings['galeria_image'])) {
            $galeriaData['image'] = $settings['galeria_image'];
        }
        if (isset($_POST['delete_galeria_image']) && $_POST['delete_galeria_image'] === '1') {
            if (!empty($settings['galeria_image'])) {
                $oldImageFullPath = str_replace('/images/', IMAGES_PATH . '/', $settings['galeria_image']);
                if (file_exists($oldImageFullPath)) { @unlink($oldImageFullPath); }
            }
            $galeriaData['image'] = null;
        }

        foreach ($imagesToDelete as $imagePath) {
            $imageFullPath = str_replace('/images/', IMAGES_PATH . '/', $imagePath);
            if (file_exists($imageFullPath)) { @unlink($imageFullPath); }
        }

        $productosTitle = sanitize($_POST['productos_title'] ?? 'Más Vendidos');
        $productosDescription = sanitize($_POST['productos_description'] ?? '');
        $productosButtonText = sanitize($_POST['productos_button_text'] ?? 'Ver todos los productos');
        $productosButtonLink = sanitize($_POST['productos_button_link'] ?? '');
        if ($productosButtonLink && $categories) {
            $validSlugs = array_map(function($c) { return '/' . $c['slug']; }, $categories);
            if (!in_array($productosButtonLink, $validSlugs, true)) {
                $productosButtonLink = '/' . $categories[0]['slug'];
            }
        } else {
            $productosButtonLink = $categories ? '/' . $categories[0]['slug'] : '';
        }

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
        $hasProductosColumns = false;
        try {
            $col = fetchOne("SHOW COLUMNS FROM landing_page_settings LIKE 'productos_title'");
            $hasProductosColumns = !empty($col);
        } catch (Exception $e) {}
        if ($hasProductosColumns) {
            $updateFields[] = "`productos_title` = :productos_title";
            $updateFields[] = "`productos_description` = :productos_description";
            $updateFields[] = "`productos_button_text` = :productos_button_text";
            $updateFields[] = "`productos_button_link` = :productos_button_link";
            $params['productos_title'] = $productosTitle;
            $params['productos_description'] = $productosDescription;
            $params['productos_button_text'] = $productosButtonText;
            $params['productos_button_link'] = $productosButtonLink;
        }
        $sql = "UPDATE landing_page_settings SET " . implode(', ', $updateFields) . " WHERE id = :id";

        if (executeQuery($sql, $params)) {
            $success = 'Configuración actualizada correctamente';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=1');
            exit;
        } else {
            $error = 'No se pudo actualizar la configuración';
        }
    }
}

if (isset($_GET['updated'])) {
    $success = 'Configuración actualizada correctamente';
    $settings = fetchOne("SELECT * FROM landing_page_settings WHERE id = 1 LIMIT 1");
    $carouselImages = !empty($settings['carousel_images']) ? json_decode($settings['carousel_images'], true) : [];
    $testimonials = !empty($settings['testimonials']) ? json_decode($settings['testimonials'], true) : [];
    $galeriaFeatures = !empty($settings['galeria_features']) ? json_decode($settings['galeria_features'], true) : [
        ['icon' => '💡', 'text' => 'Ideas creativas'],
        ['icon' => '🏠', 'text' => 'Decoración hogar'],
        ['icon' => '✨', 'text' => 'Paso a paso']
    ];
}
if (!isset($categories)) {
    $categories = getAllCategories(true);
}

$primaryLight = $settings['primary_color_light'] ?? '#ff8c00';
$primaryDark = $settings['primary_color_dark'] ?? '#ff8c00';
// Calcular variantes del color primario para el preview (igual que en el index)
$hex = ltrim($primaryLight, '#');
if (strlen($hex) === 6 && ctype_xdigit($hex)) {
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $primaryDarkComputed = '#' . sprintf('%02x%02x%02x', max(0, (int)($r * 0.85)), max(0, (int)($g * 0.85)), max(0, (int)($b * 0.85)));
    $primaryLightComputed = '#' . sprintf('%02x%02x%02x', min(255, (int)($r + (255 - $r) * 0.4)), min(255, (int)($g + (255 - $g) * 0.4)), min(255, (int)($b + (255 - $b) * 0.4)));
} else {
    $primaryDarkComputed = '#cc7000';
    $primaryLightComputed = '#ffb84d';
}

require_once '_inc/header.php';
?>

<style>
/* Topbar fijo en landing para que siempre sea clickeable (Splide/contenido no lo tapan) */
body.landing-editor-page .admin-topbar {
  position: fixed !important;
  top: 0;
  left: 340px;
  right: 0;
  z-index: 9999;
  pointer-events: auto;
}
body.landing-editor-page .admin-topbar * {
  pointer-events: auto;
}
body.landing-editor-page .admin-main-wrapper {
  padding-top: 60px;
}
@media (max-width: 992px) {
  body.landing-editor-page .admin-topbar {
    left: 0;
  }
}

/* Variables iguales al index - colores del preview usan solo los guardados (no los del admin) */
.landing-editor-preview {
  position: relative;
  z-index: 1;
  --primary: <?= htmlspecialchars($primaryLight) ?> !important;
  --primary-color: <?= htmlspecialchars($primaryLight) ?> !important;
  --primary-dark: <?= htmlspecialchars($primaryDarkComputed) ?>;
  --primary-light: <?= htmlspecialchars($primaryLightComputed) ?>;
  --primary-gradient: linear-gradient(135deg, <?= htmlspecialchars($primaryLight) ?>, <?= htmlspecialchars($primaryLightComputed) ?>) !important;
  --secondary: #ffffff;
  --text-primary: #2c2c2c;
  --text-secondary: #666;
  --surface-light: #ffffff;
  --space-xs: 0.5rem;
  --space-sm: 1rem;
  --space-md: 1.5rem;
  --space-lg: 2rem;
  --space-xl: 3rem;
  --font-size-xs: 0.75rem;
  --font-size-sm: 1rem;
  --font-size-base: 1rem;
  --font-size-md: 1.125rem;
  --font-size-lg: 1.25rem;
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
  --radius-lg: 0.75rem;
  --radius-md: 0.5rem;
  --transition-normal: 0.3s ease;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: var(--text-primary);
  line-height: 1.7;
}

/* Controles de edición */
.landing-editor-preview .edit-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity var(--transition-normal);
  z-index: 2;
  border-radius: inherit;
}
.landing-editor-preview .edit-overlay:hover { opacity: 1; }
.landing-editor-preview .edit-overlay .btn-change-img {
  background: #fff;
  color: #333;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.landing-editor-preview .editable-text {
  position: relative;
  display: inline-block;
}
.landing-editor-preview .btn-edit-pencil {
  position: absolute;
  top: 2px;
  right: -28px;
  width: 28px;
  height: 28px;
  padding: 0;
  border: none;
  background: var(--primary);
  color: #fff;
  border-radius: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  opacity: 0.9;
  transition: opacity 0.2s;
}
.landing-editor-preview .btn-edit-pencil svg {
  width: 14px;
  height: 14px;
}
.landing-editor-preview .btn-edit-pencil:hover { opacity: 1; }
.landing-editor-preview .editable-text.edit-mode .text-display { display: none; }
.landing-editor-preview .editable-text.edit-mode .edit-inline { display: block; }
.landing-editor-preview .edit-inline { display: none; margin-top: 4px; }
  .landing-editor-preview .edit-inline input,
  .landing-editor-preview .edit-inline textarea {
    width: 100%;
    max-width: 100%;
    padding: 6px 8px;
    border: 2px solid var(--primary);
    border-radius: 4px;
    font-size: inherit;
    box-sizing: border-box;
  }
.landing-editor-preview .image-wrapper { position: relative; }

/* Splide carrusel (igual que index) */
.landing-editor-preview .splide {
  position: relative;
  width: 100%;
  height: 70vh;
  overflow: hidden;
  margin-bottom: var(--space-xl);
}
.landing-editor-preview .splide__pagination {
  bottom: 1.25rem;
}
.landing-editor-preview .splide__slide { position: relative; }
.landing-editor-preview .splide__slide::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(135deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0.4) 100%);
  z-index: 1;
}
.landing-editor-preview .splide__slide img {
  width: 100% !important;
  height: 100% !important;
  min-height: 500px !important;
  object-fit: cover !important;
  display: block !important;
}
.landing-editor-preview .splide__slide .edit-overlay { z-index: 3; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; justify-content: center; }
.landing-editor-preview .splide__slide .btn-eliminar-slide { padding: 0.35rem 0.75rem; background: #dc3545; color: #fff; border: none; border-radius: 4px; font-size: 0.85rem; cursor: pointer; }
.landing-editor-preview .splide__slide .btn-eliminar-slide:hover { background: #c82333; }
@media (max-width: 768px) {
  .landing-editor-preview .splide { height: 40vh; min-height: 300px; }
  .landing-editor-preview .splide__slide img { min-height: 300px !important; }
}

/* Sección productos - grid igual que index */
.landing-editor-preview .productos {
  padding: var(--space-lg) 0;
  background: linear-gradient(135deg, var(--surface-light) 0%, var(--secondary) 100%);
}
.landing-editor-preview .productos .grid.products-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 1rem;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 var(--space-md);
}
@media (max-width: 768px) {
  .landing-editor-preview .productos .grid.products-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .landing-editor-preview .products-grid[data-destacados="true"] .product-card-last-odd {
    grid-column: 1 / -1;
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
  }
}

/* Product cards - mismos estilos que index (global.css) */
.landing-editor-preview .product-card {
  background: linear-gradient(135deg, #fff 0%, #fefefe 100%);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-normal);
  display: flex;
  flex-direction: column;
  height: 100%;
  position: relative;
  border: 1px solid #f0f0f0;
}
.landing-editor-preview .product-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--primary));
  opacity: 0;
  transition: opacity 0.3s ease;
  z-index: 1;
}
.landing-editor-preview .product-card:hover::before { opacity: 1; }
.landing-editor-preview .product-card::after {
  content: '🌸';
  position: absolute;
  top: 0.5rem; right: 0.5rem;
  font-size: 1.2rem;
  opacity: 0.3;
  transition: all 0.3s ease;
  z-index: 1;
}
.landing-editor-preview .product-card:hover::after {
  opacity: 0.7;
  transform: rotate(15deg) scale(1.1);
}
.landing-editor-preview .product-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-light);
}
.landing-editor-preview .product-card .image-container {
  position: relative;
  overflow: hidden;
  flex-shrink: 0;
  aspect-ratio: 1 / 1;
  background: #f8f9fa;
}
.landing-editor-preview .product-card .card-link {
  text-decoration: none;
  display: block;
  color: inherit;
  height: 100%;
}
.landing-editor-preview .product-card .image-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block !important;
  opacity: 1 !important;
  visibility: visible !important;
  transition: all var(--transition-normal);
}
.landing-editor-preview .product-card .sin-stock {
  position: absolute;
  bottom: var(--space-sm);
  left: var(--space-sm);
  background: linear-gradient(135deg, #ff4757, #ff3742);
  color: #fff;
  padding: var(--space-xs) var(--space-sm);
  font-size: 0.8rem;
  border-radius: var(--radius-sm);
  font-weight: 600;
  box-shadow: var(--shadow-sm);
  z-index: 2;
}
.landing-editor-preview .product-card .discount-badge,
.landing-editor-preview .product-card .discount-percentage-badge {
  position: absolute;
  font-size: 0.75rem;
  border-radius: var(--radius-sm);
  font-weight: 600;
  box-shadow: var(--shadow-sm);
  z-index: 3;
}
.landing-editor-preview .product-card .discount-badge {
  top: var(--space-sm);
  left: var(--space-sm);
  background: rgba(255,255,255,0.95);
  color: #800020;
  padding: 0.4rem 0.6rem;
}
.landing-editor-preview .product-card .discount-percentage-badge {
  bottom: var(--space-sm);
  left: var(--space-sm);
  background: rgba(255,255,255,0.95);
  color: #800020;
  padding: 0.4rem 0.6rem;
}
.landing-editor-preview .product-card .info {
  padding: var(--space-xs);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  text-align: center;
  background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
  flex: 1;
  justify-content: flex-start;
  position: relative;
  border-top: 1px solid #f8f9fa;
}
.landing-editor-preview .product-card .info::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 40px;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--primary), transparent);
  opacity: 0.5;
}
.landing-editor-preview .product-card .info h3 {
  font-family: 'Playfair Display', serif;
  font-size: 1.1rem;
  margin: 0;
  line-height: 1;
  color: var(--text-primary);
  font-weight: 600;
  height: 2.9rem;
  overflow: hidden;
  letter-spacing: 0;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background: linear-gradient(135deg, #2c2c2c, #4a4a4a);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.landing-editor-preview .product-card .info h3::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 50px;
  height: 3px;
  background: linear-gradient(90deg, transparent, var(--primary), transparent);
  border-radius: 1px;
  opacity: 0.7;
}
.landing-editor-preview .product-card .info .price {
  font-weight: 700;
  color: var(--primary);
  font-size: 2.2rem;
  margin: 0;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  position: relative;
}
.landing-editor-preview .product-card .info .price::before {
  content: '✨';
  position: absolute;
  left: -1.5rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1rem;
  opacity: 0.7;
}
.landing-editor-preview .product-card .info .price::after {
  content: '✨';
  position: absolute;
  right: -1.5rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1rem;
  opacity: 0.7;
}
.landing-editor-preview .product-card .info .price-container {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  margin: 0;
}
.landing-editor-preview .product-card .info .price-label {
  font-size: 0.75rem;
  color: #666;
  margin: 0;
  font-weight: 500;
}
.landing-editor-preview .product-card .info .price-card {
  font-size: 0.85rem;
  color: #999;
  margin: 0;
  margin-bottom: 0.05rem;
  font-weight: 500;
  line-height: 1.3;
}
.landing-editor-preview .product-card .info .price-card .price-label {
  font-size: 0.7rem;
  color: #999;
  margin-right: 0.25rem;
}
.landing-editor-preview .product-card .info .price-card-text {
  font-size: 0.75rem;
  color: #999;
  font-weight: 400;
  display: block;
  margin-top: 0;
  line-height: 1.1;
}
.landing-editor-preview .product-card .btn-agregar {
  background: var(--primary-gradient);
  color: #fff;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: var(--radius-md);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-normal);
  box-shadow: var(--shadow-sm);
  white-space: nowrap;
  min-height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  text-transform: uppercase;
}
.landing-editor-preview .product-card .btn-agregar::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}
.landing-editor-preview .product-card .btn-agregar:hover {
  background: var(--primary-gradient);
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}
.landing-editor-preview .product-card .btn-agregar:hover::after { left: 100%; }
.landing-editor-preview .product-card .btn-agregar:disabled {
  background: #e0e0e0;
  color: #999;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
.landing-editor-preview .product-card .imagen-con-transicion {
  transition: all var(--transition-normal);
}
.landing-editor-preview .product-card .imagen-con-transicion:hover {
  transform: scale(1.05);
  filter: brightness(1.1);
}
.landing-editor-preview .product-card .wishlist-btn {
  position: absolute;
  bottom: var(--space-sm);
  right: var(--space-sm);
  background: rgba(255,255,255,0.95);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  font-size: 1.3rem;
  cursor: pointer;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-normal);
  box-shadow: var(--shadow-sm);
}
.landing-editor-preview .product-card .wishlist-btn:hover {
  background: rgba(255,255,255,1);
  transform: scale(1.1);
  box-shadow: var(--shadow-md);
}
@media (max-width: 800px) {
  .landing-editor-preview .product-card .image-container { height: 220px; }
  .landing-editor-preview .product-card .info h3 { font-size: 0.95rem; }
  .landing-editor-preview .product-card .info .price { font-size: 1.6rem; }
  .landing-editor-preview .product-card .btn-agregar { font-size: 0.7rem; min-height: 40px; }
}
@media (max-width: 600px) {
  .landing-editor-preview .product-card .image-container { height: 200px; }
  .landing-editor-preview .product-card .info h3 { font-size: 0.95rem; }
  .landing-editor-preview .product-card .info .price { font-size: 1.5rem; }
  .landing-editor-preview .product-card .btn-agregar { font-size: 0.7rem; min-height: 38px; }
  .landing-editor-preview .product-card .wishlist-btn { width: 36px; height: 36px; font-size: 1.1rem; }
}

.landing-editor-preview .section-header { text-align: center; margin-bottom: var(--space-lg); }
.landing-editor-preview .section-header h2 {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--space-sm);
  font-family: 'Playfair Display', serif;
}
.landing-editor-preview .section-footer { text-align: center; margin-top: var(--space-xl); }
.landing-editor-preview .btn-ver-todos {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
  padding: var(--space-md) var(--space-xl);
  background: var(--primary-gradient);
  color: white;
  text-decoration: none;
  border-radius: var(--radius-lg);
  font-weight: 600;
  font-size: var(--font-size-md);
  transition: all var(--transition-normal);
  box-shadow: var(--shadow-md);
}

/* Sobre */
.landing-editor-preview .sobre {
  padding: var(--space-xl) 0;
  background: linear-gradient(135deg, var(--surface-light) 0%, var(--secondary) 100%);
}
.landing-editor-preview .sobre .container { max-width: 1200px; margin: 0 auto; padding: 0 var(--space-md); }
.landing-editor-preview .sobre-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-xl);
  align-items: center;
}
.landing-editor-preview .sobre-text h2 {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--space-lg);
  font-family: 'Playfair Display', serif;
  position: relative;
}
.landing-editor-preview .sobre-text h2::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 0;
  width: 60px;
  height: 3px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  border-radius: 2px;
}
.landing-editor-preview .sobre-text p {
  font-size: var(--font-size-lg);
  color: var(--text-secondary);
  line-height: 1.7;
  margin-bottom: var(--space-md);
}
.landing-editor-preview .sobre-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-sm);
  margin-top: var(--space-xl);
}
.landing-editor-preview .stat {
  text-align: center;
  padding: var(--space-md);
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid #f0f0f0;
  position: relative;
  overflow: hidden;
}
.landing-editor-preview .stat::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  background: var(--primary-gradient);
}
.landing-editor-preview .stat-number {
  display: block;
  font-size: 2.8rem;
  font-weight: 800;
  color: var(--primary);
  margin-bottom: var(--space-xs);
  line-height: 1;
  white-space: nowrap;
}
.landing-editor-preview .stat-label {
  font-size: var(--font-size-base);
  color: var(--text-primary);
  font-weight: 500;
}
.landing-editor-preview .sobre-image { position: relative; }
.landing-editor-preview .sobre-image::before {
  content: '';
  position: absolute;
  top: -20px; left: -20px; right: 20px; bottom: 20px;
  background: linear-gradient(135deg, var(--primary-light), var(--primary));
  border-radius: var(--radius-lg);
  z-index: -1;
  opacity: 0.3;
}
.landing-editor-preview .sobre-image img {
  width: 100%;
  height: 450px;
  object-fit: cover;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 4px solid white;
}

/* Testimonials */
.landing-editor-preview .testimonials {
  padding: var(--space-xl) 0;
  background: linear-gradient(135deg, var(--secondary) 0%, var(--surface-light) 100%);
}
.landing-editor-preview .testimonials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--space-md);
  margin-top: var(--space-xl);
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
  padding: 0 var(--space-md);
  justify-items: center;
}
.landing-editor-preview .testimonial-card {
  background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
  padding: var(--space-lg) var(--space-md);
  border-radius: var(--radius-lg);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  border: 2px solid #f0f0f0;
  text-align: center;
  display: flex;
  flex-direction: column;
  min-height: 180px;
  max-width: 300px;
  width: 100%;
  justify-content: space-between;
  position: relative;
}
.landing-editor-preview .testimonial-card::before {
  content: '"';
  position: absolute;
  top: 5px; left: 15px;
  font-size: 5rem;
  color: var(--primary);
  opacity: 0.15;
  font-family: 'Playfair Display', serif;
}
.landing-editor-preview .stars {
  font-size: 1.2rem;
  margin-bottom: var(--space-md);
  display: block;
  color: #ffc107;
}
.landing-editor-preview .testimonial-text {
  font-size: var(--font-size-base);
  color: var(--text-primary);
  line-height: 1.6;
  margin-bottom: var(--space-md);
  flex-grow: 1;
}
#btn-agregar-testimonial:disabled,
#btn-agregar-carousel:disabled { opacity: 0.6; cursor: not-allowed; }
.landing-editor-preview .client-name {
  font-weight: 600;
  color: var(--primary);
  font-size: var(--font-size-base);
  padding-top: var(--space-sm);
  border-top: 1px solid #f0f0f0;
}

/* CTA Galería */
.landing-editor-preview .cta {
  padding: var(--space-xl) 0;
  background: #fff;
  border-top: 1px solid #e0e0e0;
  border-bottom: 1px solid #e0e0e0;
}
.landing-editor-preview .cta .container { max-width: 1200px; margin: 0 auto; padding: 0 var(--space-md); }
.landing-editor-preview .cta-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-xl);
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--space-md);
}
/* Orden fijo igual que index: badge → título → descripción → features */
.landing-editor-preview .cta-text {
  flex: 1;
  text-align: left;
  display: flex;
  flex-direction: column;
}
.landing-editor-preview .cta-text .cta-block-badge { order: 1; }
.landing-editor-preview .cta-text .cta-block-title { order: 2; }
.landing-editor-preview .cta-text .cta-block-desc { order: 3; }
.landing-editor-preview .cta-text .cta-features { order: 4; }
.landing-editor-preview .cta-badge {
  display: inline-block;
  background: var(--primary-gradient);
  color: white;
  padding: var(--space-xs) var(--space-sm);
  border-radius: var(--radius-lg);
  font-size: var(--font-size-sm);
  font-weight: 600;
  margin-bottom: var(--space-md);
  box-shadow: var(--shadow-sm);
}
.landing-editor-preview .cta-text h2 {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: var(--space-md);
  font-family: 'Playfair Display', serif;
  color: var(--text-primary);
  background: linear-gradient(135deg, var(--text-primary), var(--primary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.landing-editor-preview .cta-text p {
  font-size: var(--font-size-md);
  color: var(--text-secondary);
  line-height: 1.7;
  margin-bottom: var(--space-lg);
}
.landing-editor-preview .cta-features {
  display: flex;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);
}
.landing-editor-preview .feature {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
  background: #f5f5f5;
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid #e0e0e0;
}
.landing-editor-preview .feature-icon-select {
  font-size: 1.2rem;
  line-height: 1;
  padding: 0.2rem 0.25rem;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  background: #fff;
  cursor: pointer;
  min-width: 2.5rem;
  height: auto;
}
.landing-editor-preview .cta-visual {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-lg);
}
.landing-editor-preview .cta-image {
  position: relative;
  width: 300px;
  height: 200px;
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-lg);
}
.landing-editor-preview .cta-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.landing-editor-preview .image-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(135deg, rgba(0,0,0,0.1), rgba(0,0,0,0.05));
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: all var(--transition-normal);
}
.landing-editor-preview .cta-image:hover .image-overlay { opacity: 1; }
.landing-editor-preview .overlay-content { text-align: center; color: white; }
.landing-editor-preview .overlay-text { display: block; font-size: 1.5rem; font-weight: 700; margin-bottom: var(--space-xs); }
.landing-editor-preview .overlay-subtext { display: block; font-size: var(--font-size-sm); opacity: 0.9; }
.landing-editor-preview .btn-primary {
  background: var(--primary-gradient);
  color: white;
  box-shadow: var(--shadow-md);
  padding: var(--space-md) var(--space-xl);
  border: none;
  border-radius: var(--radius-lg);
  font-weight: 600;
  font-size: var(--font-size-md);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
}
@media (max-width: 768px) {
  .landing-editor-preview .sobre-content { grid-template-columns: 1fr; }
  .landing-editor-preview .cta-content { flex-direction: column; text-align: center; }
  .landing-editor-preview .cta-text { text-align: center; }
  .landing-editor-preview .cta-features { display: none; }
  .landing-editor-preview .sobre-text h2,
  .landing-editor-preview .section-header h2 { font-size: 1.75rem; }
  .landing-editor-preview .cta-text h2 { font-size: 1.75rem; }
  .landing-editor-preview .sobre-stats { grid-template-columns: 1fr; }
  .landing-editor-preview .stat-number { font-size: 2.2rem; }
}
@media (max-width: 500px) {
  .landing-editor-preview .stat-number { font-size: 2rem; }
}

/* Responsive: admin landing page en mobile */
@media (max-width: 768px) {
  .landing-page-header {
    flex-direction: column;
    align-items: stretch !important;
  }
  .landing-page-header a.btn {
    width: 100%;
    text-align: center;
  }
  .landing-editor-preview .section-footer {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }
  .landing-editor-preview .section-footer label {
    width: 100%;
    justify-content: center;
  }
  .landing-editor-preview .section-footer select[name="productos_button_link"] {
    min-width: 0;
    flex: 1;
    max-width: 100%;
  }
  .landing-editor-preview .btn-edit-pencil {
    position: static;
    margin-left: 0.5rem;
    vertical-align: middle;
  }
  .landing-editor-preview .editable-text {
    display: flex !important;
    flex-wrap: wrap;
    align-items: center;
  }
  .landing-editor-preview #landing-save-area > div[style*="grid-template-columns"] {
    grid-template-columns: 1fr !important;
  }
  .landing-editor-preview #landing-save-area .btn {
    width: 100%;
  }
}
@media (max-width: 576px) {
  .landing-editor-preview .testimonials .section-header,
  .landing-editor-preview #galeria-section .section-header {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }
  .landing-editor-preview .testimonials .section-header label span,
  .landing-editor-preview #galeria-section .section-header label span {
    font-size: 0.85rem;
  }
  .landing-editor-preview #carousel-config-details {
    margin: 0 0.5rem;
  }
  .landing-editor-preview #carousel-links-config {
    flex-direction: column;
  }
}

.landing-section-divider {
  margin: 2rem 0;
  border: none;
  border-top: 2px solid #e0e0e0;
  max-width: 100%;
}

/* Botones de sección */
.section-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 1.5rem;
  padding: 1rem 0;
  justify-content: center;
}
.section-actions .btn { margin: 0; }
.section-actions .btn-save-section {
  background: #22c55e;
  color: white;
  border-radius: 8px;
}
.section-actions .btn-save-section:hover {
  background: #16a34a;
}
.section-actions .btn-cancel-section {
  background: #dc3545;
  color: white;
  border-radius: 8px;
}
.section-actions .btn-cancel-section:hover {
  background: #c82333;
}
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.3/dist/css/splide.min.css" />

<div class="admin-content">
    <div class="landing-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div>
            <h2 style="margin: 0;">Editor de Landing Page</h2>
            <p style="color: #666; margin: 0.25rem 0 0 0; font-size: 0.9rem;">Vista idéntica al index. Usá los botones "Cambiar imagen" y el lápiz para editar textos.</p>
        </div>
        <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-secondary" style="text-decoration: none;">← Volver al panel</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" id="landing-form">
        <script>
        (function(){var f=document.getElementById('landing-form');if(f)f.addEventListener('submit',function(){sessionStorage.setItem('landing_scroll_y',String(window.scrollY||document.documentElement.scrollTop));});})();
        </script>
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <div class="landing-editor-preview">
            <!-- CARRUSEL (igual que index) -->
            <div class="landing-section" data-section="carousel">
            <div class="splide" id="hero-slider">
                <div class="splide__track">
                    <ul class="splide__list">
                        <?php foreach ($carouselImages as $index => $item): ?>
                            <li class="splide__slide">
                                <div class="image-wrapper" style="width:100%;height:100%;position:relative;">
                                    <img src="<?= htmlspecialchars($item['image'] ?? '') ?>" alt="Carrusel" width="1920" height="1080" loading="lazy" />
                                    <div class="edit-overlay">
                                        <button type="button" class="btn-change-img" data-slide-index="<?= $index ?>">Cambiar imagen</button>
                                        <button type="button" class="btn-eliminar-slide" data-slide-index="<?= $index ?>" title="Eliminar esta imagen">Eliminar imagen</button>
                                    </div>
                                </div>
                                <input type="file" name="carousel_change_image_<?= $index ?>" accept="image/jpeg,image/png,image/webp" class="carousel-file-input" data-slide-index="<?= $index ?>" style="display:none;">
                                <input type="hidden" name="carousel_change_image_index[]" value="<?= $index ?>">
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div id="carousel-form-groups" style="display: none;">
            <?php foreach ($carouselImages as $index => $item):
                $linkType = empty($item['link']) ? 'none' : ($item['link'] === '/galeria' ? 'ideas' : 'category');
                $linkVal = $item['link'] === '/galeria' ? '/galeria' : ltrim($item['link'] ?? '', '/');
            ?>
                <div class="carousel-item-form-group">
                    <input type="hidden" name="carousel_image[]" value="<?= htmlspecialchars($item['image'] ?? '') ?>">
                    <div class="carousel-link-row" data-index="<?= $index ?>" style="display: none;">
                        <select name="carousel_link_type[]" class="carousel-link-type-select">
                            <option value="none" <?= $linkType === 'none' ? 'selected' : '' ?>>Sin link</option>
                            <option value="category" <?= $linkType === 'category' ? 'selected' : '' ?>>Categoría</option>
                            <option value="ideas" <?= $linkType === 'ideas' ? 'selected' : '' ?>>Galería de Ideas</option>
                        </select>
                        <select name="carousel_link_value[]" class="carousel-link-value-select" style="<?= $linkType !== 'category' ? 'display:none;' : '' ?>">
                            <option value="">Seleccionar categoría</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $linkVal === $cat['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                            <option value="/galeria" <?= $linkVal === '/galeria' ? 'selected' : '' ?>>Galería de ideas</option>
                        </select>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

            <!-- Config links carrusel (colapsable debajo del slider) -->
            <div style="max-width: 1200px; margin: 0 auto var(--space-lg); padding: 0 var(--space-md);">
                <details style="background: #f5f5f5; padding: 0.75rem 1rem; border-radius: 8px;" id="carousel-config-details">
                    <summary style="cursor: pointer; font-weight: 600;">Configurar links del carrusel</summary>
                    <div id="carousel-links-config" style="margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 1rem;"></div>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                        <p style="margin-bottom: 0.5rem; font-weight: 600;">Agregar imagen(s) al carrusel</p>
                        <input type="file" id="new-carousel-images-input" name="new_carousel_images[]" accept="image/jpeg,image/png,image/webp" multiple style="display: none;">
                        <button type="button" id="btn-agregar-carousel" style="padding: 0.5rem 1rem; background: var(--primary); color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">+ Agregar imagen(s)</button>
                        <span style="margin-left: 0.5rem; font-size: 0.85rem; color: var(--text-secondary);">Máximo 5 imágenes en el carrusel. Elige las imágenes, configura el link y guarda el formulario.</span>
                        <div id="new-carousel-rows" style="margin-top: 0.75rem;"></div>
                    </div>
                </details>
            </div>
            <div class="section-actions" style="display: none;">
                <button type="button" class="btn btn-primary btn-save-section" data-section="carousel">Guardar cambios</button>
                <button type="button" class="btn btn-secondary btn-cancel-section" data-section="carousel">Cancelar</button>
            </div>
            </div>

            <hr class="landing-section-divider">

            <!-- PRODUCTOS MÁS VENDIDOS (título, descripción y botón editables; grid cargado desde API) -->
            <div class="landing-section" data-section="productos">
            <section class="productos">
                <div class="section-header">
                    <div class="editable-text" data-input="productos_title" style="display: block;">
                        <h2><span class="text-display"><?= htmlspecialchars($settings['productos_title'] ?? 'Más Vendidos') ?></span></h2>
                        <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                        <div class="edit-inline"><input type="text" value="<?= htmlspecialchars($settings['productos_title'] ?? 'Más Vendidos') ?>"></div>
                    </div>
                    <input type="hidden" name="productos_title" value="<?= htmlspecialchars($settings['productos_title'] ?? 'Más Vendidos') ?>">
                    <div class="editable-text" data-input="productos_description" style="display: block;">
                        <p><span class="text-display"><?= htmlspecialchars($settings['productos_description'] ?? 'Descubre nuestros productos más populares, elegidos por nuestros clientes') ?></span></p>
                        <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                        <div class="edit-inline"><textarea rows="2"><?= htmlspecialchars($settings['productos_description'] ?? 'Descubre nuestros productos más populares, elegidos por nuestros clientes') ?></textarea></div>
                    </div>
                    <input type="hidden" name="productos_description" value="<?= htmlspecialchars($settings['productos_description'] ?? 'Descubre nuestros productos más populares, elegidos por nuestros clientes') ?>">
                </div>
                <div class="grid desktop-grid products-grid" data-destacados="true" data-limit="5">
                    <!-- Los productos destacados se cargan aquí con el script products-loader.js -->
                </div>
                <?php
                $productosLinkSaved = $settings['productos_button_link'] ?? '';
                $productosLinkValid = false;
                if ($productosLinkSaved && $categories) {
                    foreach ($categories as $c) {
                        if ($productosLinkSaved === '/' . $c['slug']) { $productosLinkValid = true; break; }
                    }
                }
                $productosLinkValue = $productosLinkValid ? $productosLinkSaved : ($categories ? '/' . $categories[0]['slug'] : '');
                ?>
                <div class="section-footer">
                    <div class="editable-text" data-input="productos_button_text" style="display: inline-block;">
                        <a href="<?= htmlspecialchars($productosLinkValue ?: '#') ?>" class="btn-ver-todos" id="productos-btn-preview"><span class="text-display"><?= htmlspecialchars($settings['productos_button_text'] ?? 'Ver todos los productos') ?></span></a>
                        <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                        <div class="edit-inline"><input type="text" value="<?= htmlspecialchars($settings['productos_button_text'] ?? 'Ver todos los productos') ?>" placeholder="Ver todos los productos"></div>
                    </div>
                    <input type="hidden" name="productos_button_text" value="<?= htmlspecialchars($settings['productos_button_text'] ?? 'Ver todos los productos') ?>">
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem; margin-left: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
                        <span>Ir a:</span>
                        <select name="productos_button_link" id="productos_button_link_select" style="padding: 0.35rem 0.5rem; border: 1px solid #ddd; border-radius: 4px; min-width: 180px;">
                            <?php if (empty($categories)): ?>
                                <option value="">Sin categorías</option>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <?php $catUrl = '/' . htmlspecialchars($cat['slug']); ?>
                                    <option value="<?= $catUrl ?>" <?= $productosLinkValue === $catUrl ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </label>
                </div>
            </section>
            <div class="section-actions" style="display: none;">
                <button type="button" class="btn btn-primary btn-save-section" data-section="productos">Guardar cambios</button>
                <button type="button" class="btn btn-secondary btn-cancel-section" data-section="productos">Cancelar</button>
            </div>
            </div>

            <hr class="landing-section-divider">

            <!-- SOBRE -->
            <div class="landing-section" data-section="sobre">
            <section class="sobre" id="sobre-section">
                <div class="container">
                    <div class="sobre-content">
                        <div class="sobre-text">
                            <div class="editable-text" data-input="sobre_title">
                                <h2><span class="text-display"><?= htmlspecialchars($settings['sobre_title'] ?? 'Sobre LUME') ?></span></h2>
                                <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                <div class="edit-inline"><input type="text" value="<?= htmlspecialchars($settings['sobre_title'] ?? 'Sobre LUME') ?>"></div>
                            </div>
                            <input type="hidden" name="sobre_title" value="<?= htmlspecialchars($settings['sobre_title'] ?? 'Sobre LUME') ?>">
                            <div class="editable-text" data-input="sobre_text_1" style="display: block;">
                                <p><span class="text-display"><?= htmlspecialchars($settings['sobre_text_1'] ?? '') ?></span></p>
                                <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                <div class="edit-inline"><textarea rows="3"><?= htmlspecialchars($settings['sobre_text_1'] ?? '') ?></textarea></div>
                            </div>
                            <input type="hidden" name="sobre_text_1" value="<?= htmlspecialchars($settings['sobre_text_1'] ?? '') ?>">
                            <div class="editable-text" data-input="sobre_text_2" style="display: block;">
                                <p><span class="text-display"><?= htmlspecialchars($settings['sobre_text_2'] ?? '') ?></span></p>
                                <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                <div class="edit-inline"><textarea rows="3"><?= htmlspecialchars($settings['sobre_text_2'] ?? '') ?></textarea></div>
                            </div>
                            <input type="hidden" name="sobre_text_2" value="<?= htmlspecialchars($settings['sobre_text_2'] ?? '') ?>">
                            <div class="sobre-stats">
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <div class="stat">
                                        <div class="editable-text" data-input="sobre_stat_<?= $i ?>_number" style="display: inline-block;">
                                            <span class="stat-number text-display"><?= htmlspecialchars($settings['sobre_stat_' . $i . '_number'] ?? '') ?></span>
                                            <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                            <span class="edit-inline"><input type="text" value="<?= htmlspecialchars($settings['sobre_stat_' . $i . '_number'] ?? '') ?>"></span>
                                        </div>
                                        <input type="hidden" name="sobre_stat_<?= $i ?>_number" value="<?= htmlspecialchars($settings['sobre_stat_' . $i . '_number'] ?? '') ?>">
                                        <div class="editable-text" data-input="sobre_stat_<?= $i ?>_label" style="display: block;">
                                            <span class="stat-label text-display"><?= htmlspecialchars($settings['sobre_stat_' . $i . '_label'] ?? '') ?></span>
                                            <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                            <span class="edit-inline"><input type="text" value="<?= htmlspecialchars($settings['sobre_stat_' . $i . '_label'] ?? '') ?>"></span>
                                        </div>
                                        <input type="hidden" name="sobre_stat_<?= $i ?>_label" value="<?= htmlspecialchars($settings['sobre_stat_' . $i . '_label'] ?? '') ?>">
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="sobre-image">
                            <div class="image-wrapper" style="border-radius: var(--radius-lg); overflow: hidden;">
                                <img id="sobre-img-preview" src="<?= htmlspecialchars($settings['sobre_image'] ?? '/images/sobre-nosotros.webp') ?>" alt="Sobre" />
                                <div class="edit-overlay">
                                    <button type="button" class="btn-change-img" id="trigger-sobre-image">Cambiar imagen</button>
                                </div>
                                <input type="file" name="sobre_image" id="sobre_image" accept="image/jpeg,image/png,image/webp" style="display:none;">
                                <input type="hidden" name="delete_sobre_image" id="delete_sobre_image" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <div class="section-actions" style="display: none;">
                <button type="button" class="btn btn-primary btn-save-section" data-section="sobre">Guardar cambios</button>
                <button type="button" class="btn btn-secondary btn-cancel-section" data-section="sobre">Cancelar</button>
            </div>
            </div>

            <hr class="landing-section-divider">

            <!-- TESTIMONIALS - Máx 5 comentarios. Estrellas: solo 1-5. Agregar/Eliminar. -->
            <div class="landing-section" data-section="testimonials">
            <?php
            $starOptions = ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'];
            $testimonialsDisplay = array_slice($testimonials, 0, 5);
            ?>
            <section class="testimonials" id="testimonials-section">
                <div class="container">
                    <div class="section-header" style="display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                        <h2>Comentarios de Clientes</h2>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" title="Solo afecta a la página principal (index). En el editor la sección siempre está visible.">
                            <input type="checkbox" name="testimonials_visible" value="1" <?= ($settings['testimonials_visible'] ?? 1) ? 'checked' : '' ?>>
                            <span>Mostrar esta sección en la página principal (index)</span>
                        </label>
                    </div>
                    <div class="testimonials-grid" id="testimonials-grid">
                        <?php foreach ($testimonialsDisplay as $index => $t):
                            $starsVal = $t['stars'] ?? '⭐⭐⭐⭐⭐';
                            if (!in_array($starsVal, $starOptions, true)) {
                                $starsVal = '⭐⭐⭐⭐⭐';
                            }
                        ?>
                            <div class="testimonial-card testimonial-item" data-index="<?= $index ?>">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: var(--space-sm);">
                                    <label style="font-size: 0.85rem; color: var(--text-secondary);">Estrellas:</label>
                                    <select name="testimonial_stars[]" class="testimonial-stars-select" style="padding: 0.25rem 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1.1rem;">
                                        <?php foreach ($starOptions as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>" <?= $starsVal === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="editable-text" data-input="testimonial_text_<?= $index ?>">
                                    <p class="testimonial-text"><span class="text-display"><?= htmlspecialchars($t['text'] ?? '') ?></span></p>
                                    <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                    <div class="edit-inline"><textarea rows="3"><?= htmlspecialchars($t['text'] ?? '') ?></textarea></div>
                                </div>
                                <input type="hidden" name="testimonial_text[]" value="<?= htmlspecialchars($t['text'] ?? '') ?>">
                                <div class="editable-text" data-input="testimonial_client_name_<?= $index ?>">
                                    <span class="client-name text-display"><?= htmlspecialchars($t['client_name'] ?? '') ?></span>
                                    <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                    <span class="edit-inline"><input type="text" value="<?= htmlspecialchars($t['client_name'] ?? '') ?>"></span>
                                </div>
                                <input type="hidden" name="testimonial_client_name[]" value="<?= htmlspecialchars($t['client_name'] ?? '') ?>">
                                <button type="button" class="btn-eliminar-testimonial" style="margin-top: 0.5rem; padding: 0.35rem 0.75rem; background: #dc3545; color: #fff; border: none; border-radius: 4px; font-size: 0.85rem; cursor: pointer;">Eliminar</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 1rem; text-align: center;">
                        <button type="button" id="btn-agregar-testimonial" style="padding: 0.5rem 1.25rem; background: var(--primary); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">+ Agregar comentario</button>
                        <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-secondary);">Máximo 5 comentarios</p>
                    </div>
                </div>
            </section>
            <div id="new-testimonials-container"></div>
            <div class="section-actions" style="display: none;">
                <button type="button" class="btn btn-primary btn-save-section" data-section="testimonials">Guardar cambios</button>
                <button type="button" class="btn btn-secondary btn-cancel-section" data-section="testimonials">Cancelar</button>
            </div>
            </div>

            <hr class="landing-section-divider">

            <!-- CTA GALERÍA - Orden igual que index. En el editor siempre visible; "Mostrar sección" solo afecta al index público. -->
            <div class="landing-section" data-section="galeria">
            <section class="cta" id="galeria-section">
                <div class="container">
                    <div class="section-header" style="display: flex; align-items: center; justify-content: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" title="Solo afecta a la página principal (index). En el editor la sección siempre está visible.">
                            <input type="checkbox" name="galeria_visible" value="1" <?= ($settings['galeria_visible'] ?? 1) ? 'checked' : '' ?>>
                            <span>Mostrar esta sección en la página principal (index)</span>
                        </label>
                    </div>
                    <div class="cta-content">
                        <div class="cta-text">
                            <!-- 1) Badge arriba (como en el index) -->
                            <div class="editable-text cta-block-badge" data-input="galeria_badge">
                                <div class="cta-badge"><span class="text-display"><?= htmlspecialchars($settings['galeria_badge'] ?? '✨ Inspiración') ?></span></div>
                                <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                <div class="edit-inline"><input type="text" value="<?= htmlspecialchars($settings['galeria_badge'] ?? '✨ Inspiración') ?>"></div>
                            </div>
                            <input type="hidden" name="galeria_badge" value="<?= htmlspecialchars($settings['galeria_badge'] ?? '✨ Inspiración') ?>">
                            <!-- 2) Título -->
                            <div class="editable-text cta-block-title" data-input="galeria_title">
                                <h2><span class="text-display"><?= htmlspecialchars($settings['galeria_title'] ?? 'Revisa nuestra galería de ideas') ?></span></h2>
                                <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                <div class="edit-inline"><input type="text" value="<?= htmlspecialchars($settings['galeria_title'] ?? '') ?>"></div>
                            </div>
                            <input type="hidden" name="galeria_title" value="<?= htmlspecialchars($settings['galeria_title'] ?? '') ?>">
                            <!-- 3) Descripción -->
                            <div class="editable-text cta-block-desc" data-input="galeria_description" style="display: block;">
                                <p><span class="text-display"><?= htmlspecialchars($settings['galeria_description'] ?? '') ?></span></p>
                                <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                <div class="edit-inline"><textarea rows="3"><?= htmlspecialchars($settings['galeria_description'] ?? '') ?></textarea></div>
                            </div>
                            <input type="hidden" name="galeria_description" value="<?= htmlspecialchars($settings['galeria_description'] ?? '') ?>">
                            <!-- 4) Botones de características (icono + texto editables; solo lista de iconos) -->
                            <?php
                            $emojiList = ['💡', '🏠', '✨', '🎨', '🎯', '💎', '🔥', '⭐', '🎉', '🎁', '❤️', '🚀', '🌟', '💫', '🎊', '🌈', '🛍️', '📱', '💻', '📸', '🖥️', '🛒'];
                            ?>
                            <div class="cta-features" id="galeria-features">
                                <?php foreach ($galeriaFeatures as $fi => $feat): ?>
                                    <div class="feature editable-feature" data-index="<?= $fi ?>">
                                        <select name="galeria_feature_icon[]" class="feature-icon-select" title="Cambiar icono">
                                            <?php foreach ($emojiList as $emo): ?>
                                                <option value="<?= htmlspecialchars($emo) ?>" <?= ($feat['icon'] ?? '') === $emo ? 'selected' : '' ?>><?= $emo ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="editable-text" data-input="galeria_feature_text_<?= $fi ?>" style="display: inline-block;">
                                            <span class="text-display"><?= htmlspecialchars($feat['text'] ?? '') ?></span>
                                            <button type="button" class="btn-edit-pencil" title="Editar texto"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                            <span class="edit-inline"><input type="text" value="<?= htmlspecialchars($feat['text'] ?? '') ?>"></span>
                                        </div>
                                        <input type="hidden" name="galeria_feature_text[]" value="<?= htmlspecialchars($feat['text'] ?? '') ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="cta-visual">
                            <div class="cta-image">
                                <div class="image-wrapper" style="width:100%;height:100%;">
                                    <img id="galeria-img-preview" src="<?= htmlspecialchars($settings['galeria_image'] ?? '/images/0_galeria/idea28.webp') ?>" alt="Galería" />
                                    <div class="image-overlay">
                                        <div class="overlay-content">
                                            <span class="overlay-text">30+ Ideas</span>
                                            <span class="overlay-subtext">Para inspirarte</span>
                                        </div>
                                    </div>
                                    <div class="edit-overlay">
                                        <button type="button" class="btn-change-img" id="trigger-galeria-image">Cambiar imagen</button>
                                    </div>
                                </div>
                                <input type="file" name="galeria_image" id="galeria_image" accept="image/jpeg,image/png,image/webp" style="display:none;">
                                <input type="hidden" name="delete_galeria_image" id="delete_galeria_image" value="0">
                            </div>
                            <div class="cta-button">
                                <div class="editable-text" data-input="galeria_button_text" style="display: inline-block;">
                                    <span class="btn-primary" style="cursor: default;"><span class="text-display"><?= htmlspecialchars($settings['galeria_button_text'] ?? 'Galeria de ideas') ?></span></span>
                                    <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                    <div class="edit-inline"><input type="text" value="<?= htmlspecialchars($settings['galeria_button_text'] ?? 'Galeria de ideas') ?>"></div>
                                </div>
                                <input type="hidden" name="galeria_button_text" value="<?= htmlspecialchars($settings['galeria_button_text'] ?? 'Galeria de ideas') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <div class="section-actions" style="display: none;">
                <button type="button" class="btn btn-primary btn-save-section" data-section="galeria">Guardar cambios</button>
                <button type="button" class="btn btn-secondary btn-cancel-section" data-section="galeria">Cancelar</button>
            </div>
            </div>

            <!-- COLORES -->
            <div class="landing-section" data-section="colors">
            <section id="landing-save-area" style="padding: var(--space-xl) 0; background: #f5f5f5; border-radius: var(--radius-lg); margin: 2rem auto; max-width: 1200px; padding: 2rem;">
                <h3 style="margin-bottom: 1rem; color: #333;">Configuración de colores</h3>
                <p style="color: #666; margin-bottom: 1.5rem; font-size: 0.9rem;">Estos colores se aplican en la página principal (modo claro y modo oscuro).</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">
                    <div>
                        <label for="primary_color_light" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Color primario - Modo claro</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="color" id="primary_color_light" name="primary_color_light" value="<?= htmlspecialchars($primaryLight) ?>" style="width: 60px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;">
                            <input type="text" id="primary_color_light_hex" value="<?= htmlspecialchars($primaryLight) ?>" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;" placeholder="#ff8c00">
                        </div>
                    </div>
                    <div>
                        <label for="primary_color_dark" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Color primario - Modo oscuro</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="color" id="primary_color_dark" name="primary_color_dark" value="<?= htmlspecialchars($primaryDark) ?>" style="width: 60px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;">
                            <input type="text" id="primary_color_dark_hex" value="<?= htmlspecialchars($primaryDark) ?>" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;" placeholder="#ff8c00">
                        </div>
                    </div>
                </div>
                <div style="margin-top: 2rem; display: flex; flex-direction: column; gap: 1rem;">
                    <?php if (!empty($success) && isset($_GET['updated'])): ?>
                    <div id="landing-success-msg" class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <div class="section-actions" style="display: none; gap: 1rem; flex-wrap: wrap;">
                        <button type="button" class="btn btn-primary btn-save-section" data-section="colors">Guardar cambios</button>
                        <button type="button" class="btn btn-secondary btn-cancel-section" data-section="colors">Cancelar</button>
                    </div>
                </div>
            </section>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.3/dist/js/splide.min.js"></script>
<script src="/js/products-loader.js"></script>
<script>
var carouselCategories = <?= json_encode($categories ?? []) ?>;
document.addEventListener('DOMContentLoaded', function() {
  // Tras guardar: restaurar posición del scroll (evitar salto arriba/abajo) y ocultar mensaje tras 5s
  if (window.location.search.indexOf('updated=1') !== -1) {
    var savedY = sessionStorage.getItem('landing_scroll_y');
    if (savedY !== null) {
      sessionStorage.removeItem('landing_scroll_y');
      window.scrollTo(0, parseInt(savedY, 10));
    }
    var successEl = document.getElementById('landing-success-msg');
    if (successEl) {
      setTimeout(function() {
        successEl.style.transition = 'opacity 0.4s';
        successEl.style.opacity = '0';
        setTimeout(function() { successEl.remove(); }, 400);
      }, 5000);
    }
  }

  var heroSlider = document.getElementById('hero-slider');
  var splideInstance = null;
  function mountCarousel() {
    if (splideInstance) { try { splideInstance.destroy(); } catch (e) {} }
    var slides = document.querySelectorAll('#hero-slider .splide__slide');
    if (slides.length > 0) {
      splideInstance = new Splide('#hero-slider', {
        type: 'fade',
        autoplay: true,
        interval: 4000,
        rewind: true,
        arrows: true,
        pagination: true
      });
      splideInstance.mount();
    }
  }
  mountCarousel();

  // Cambiar imagen: carrusel
  document.querySelectorAll('.btn-change-img[data-slide-index]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var idx = this.getAttribute('data-slide-index');
      var input = document.querySelector('.carousel-file-input[data-slide-index="' + idx + '"]');
      if (input) input.click();
    });
  });
  document.querySelectorAll('.carousel-file-input').forEach(function(input) {
    input.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
          var slide = input.closest('.splide__slide');
          var img = slide && slide.querySelector('img');
          if (img) img.src = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  });

  // Eliminar imagen del carrusel
  document.addEventListener('click', function(e) {
    if (e.target.closest('.admin-topbar') || e.target.closest('.admin-nav-sidebar')) return;
    var btn = e.target.closest('.btn-eliminar-slide');
    if (!btn) return;
    e.preventDefault();
    var slide = btn.closest('.splide__slide');
    if (!slide) return;
    var list = slide.parentNode;
    var idx = Array.prototype.indexOf.call(list.children, slide);
    var formGroups = document.getElementById('carousel-form-groups');
    var linksConfig = document.getElementById('carousel-links-config');
    if (!formGroups || !linksConfig || idx < 0) return;
    slide.remove();
    if (formGroups.children[idx]) formGroups.children[idx].remove();
    if (linksConfig.children[idx]) linksConfig.children[idx].remove();
    for (var i = 0; i < linksConfig.children.length; i++) {
      var label = linksConfig.children[i].querySelector('label');
      if (label) label.textContent = 'Imagen ' + (i + 1) + ':';
    }
    mountCarousel();
    if (typeof updateCarouselAddButton === 'function') updateCarouselAddButton();
  });

  // Agregar imagen(s) al carrusel (máximo 5 en total)
  var MAX_CAROUSEL_IMAGES = 5;
  var newCarouselInput = document.getElementById('new-carousel-images-input');
  var btnAgregarCarousel = document.getElementById('btn-agregar-carousel');
  var newCarouselRows = document.getElementById('new-carousel-rows');
  function getCarouselSlideCount() { return document.querySelectorAll('#hero-slider .splide__slide').length; }
  function updateCarouselAddButton() {
    if (!btnAgregarCarousel) return;
    var n = getCarouselSlideCount();
    btnAgregarCarousel.disabled = n >= MAX_CAROUSEL_IMAGES;
    btnAgregarCarousel.title = n >= MAX_CAROUSEL_IMAGES ? 'Máximo 5 imágenes en el carrusel' : '';
  }
  if (btnAgregarCarousel && newCarouselInput && newCarouselRows) {
    updateCarouselAddButton();
    btnAgregarCarousel.addEventListener('click', function() {
      if (this.disabled) return;
      newCarouselInput.click();
    });
    newCarouselInput.addEventListener('change', function() {
      var files = this.files;
      if (!files || files.length === 0) return;
      var currentCount = getCarouselSlideCount();
      var maxNew = MAX_CAROUSEL_IMAGES - currentCount;
      if (maxNew <= 0) {
        newCarouselRows.innerHTML = '';
        this.value = '';
        return;
      }
      var toAdd = Math.min(files.length, maxNew);
      newCarouselRows.innerHTML = '';
      if (files.length > maxNew) {
        var hint = document.createElement('p');
        hint.className = 'carousel-new-hint';
        hint.style.cssText = 'font-size:0.85rem;color:#856404;margin-bottom:0.5rem;';
        hint.textContent = 'Solo se pueden agregar ' + maxNew + ' más (máximo 5 en total). Se usarán las primeras ' + toAdd + ' imágenes.';
        newCarouselRows.appendChild(hint);
      }
      var maxInput = document.getElementById('new-carousel-max-input');
      if (maxInput) maxInput.remove();
      maxInput = document.createElement('input');
      maxInput.type = 'hidden';
      maxInput.name = 'new_carousel_max';
      maxInput.id = 'new-carousel-max-input';
      maxInput.value = toAdd;
      newCarouselRows.appendChild(maxInput);
      for (var i = 0; i < toAdd; i++) {
        var row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;';
        var label = document.createElement('label');
        label.textContent = 'Nueva imagen ' + (i + 1) + ' (link):';
        label.style.minWidth = '120px';
        var typeSelect = document.createElement('select');
        typeSelect.name = 'new_carousel_link_type[]';
        typeSelect.className = 'carousel-link-type-select';
        typeSelect.innerHTML = '<option value="none">Sin link</option><option value="category">Categoría</option><option value="ideas">Galería de Ideas</option>';
        var valSelect = document.createElement('select');
        valSelect.name = 'new_carousel_link_value[]';
        valSelect.className = 'carousel-link-value-select';
        valSelect.style.display = 'none';
        valSelect.innerHTML = '<option value="">Seleccionar categoría</option>';
        if (carouselCategories && carouselCategories.length) {
          carouselCategories.forEach(function(c) {
            valSelect.appendChild(new Option(c.name, c.slug));
          });
        }
        valSelect.appendChild(new Option('Galería de ideas', '/galeria'));
        typeSelect.addEventListener('change', function() {
          valSelect.style.display = this.value === 'category' ? '' : 'none';
          if (this.value !== 'category') valSelect.value = '/galeria';
        });
        row.appendChild(label);
        row.appendChild(typeSelect);
        row.appendChild(valSelect);
        newCarouselRows.appendChild(row);
      }
      // No vaciar el input: los archivos deben enviarse al guardar el formulario
    });
  }

  // Cambiar imagen: sobre
  var triggerSobre = document.getElementById('trigger-sobre-image');
  var sobreInput = document.getElementById('sobre_image');
  if (triggerSobre && sobreInput) {
    triggerSobre.addEventListener('click', function(e) { e.preventDefault(); sobreInput.click(); });
    sobreInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
          var prev = document.getElementById('sobre-img-preview');
          if (prev) prev.src = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  }

  // Cambiar imagen: galería
  var triggerGaleria = document.getElementById('trigger-galeria-image');
  var galeriaInput = document.getElementById('galeria_image');
  if (triggerGaleria && galeriaInput) {
    triggerGaleria.addEventListener('click', function(e) { e.preventDefault(); galeriaInput.click(); });
    galeriaInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
          var prev = document.getElementById('galeria-img-preview');
          if (prev) prev.src = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  }

  // Editar texto (lápiz)
  document.querySelectorAll('.editable-text').forEach(function(wrap) {
    var inputName = wrap.getAttribute('data-input');
    if (!inputName) return;
    var form = document.getElementById('landing-form');
    var hidden = null;
    var testimonialMatch = inputName.match(/^testimonial_(stars|text|client_name)_(\d+)$/);
    if (testimonialMatch) {
      var card = wrap.closest('.testimonial-card');
      if (card) {
        var name = 'testimonial_' + testimonialMatch[1] + '[]';
        hidden = card.querySelector('input[name="' + name + '"]');
      }
    } else if (wrap.closest('.feature')) {
      hidden = wrap.closest('.feature').querySelector('input[name="galeria_feature_text[]"]');
    } else {
      hidden = form.querySelector('input[name="' + inputName + '"]');
    }
    var display = wrap.querySelector('.text-display');
    var pencil = wrap.querySelector('.btn-edit-pencil');
    var editBlock = wrap.querySelector('.edit-inline');
    var inputEl = editBlock && (editBlock.querySelector('input') || editBlock.querySelector('textarea'));
    if (!display || !pencil || !editBlock || !inputEl || !hidden) return;

    pencil.addEventListener('click', function() {
      wrap.classList.add('edit-mode');
      inputEl.value = hidden.value;
      inputEl.focus();
    });
    function saveAndClose() {
      var val = inputEl.value;
      hidden.value = val;
      if (display) display.textContent = val;
      wrap.classList.remove('edit-mode');
    }
    inputEl.addEventListener('blur', saveAndClose);
    inputEl.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && inputEl.tagName !== 'TEXTAREA') { e.preventDefault(); saveAndClose(); }
      if (e.key === 'Escape') { inputEl.value = hidden.value; wrap.classList.remove('edit-mode'); }
    });
  });

  // Mover bloques de link del carrusel al panel "Configurar links"
  var linksConfig = document.getElementById('carousel-links-config');
  document.querySelectorAll('.carousel-link-row').forEach(function(row) {
    row.style.display = 'flex';
    row.style.gap = '0.5rem';
    row.style.alignItems = 'center';
    row.style.marginBottom = '0.5rem';
    var idx = row.getAttribute('data-index');
    var label = document.createElement('label');
    label.textContent = 'Imagen ' + (parseInt(idx, 10) + 1) + ':';
    label.style.minWidth = '70px';
    row.insertBefore(label, row.firstChild);
    linksConfig.appendChild(row);
    var typeSelect = row.querySelector('.carousel-link-type-select');
    var valSelect = row.querySelector('.carousel-link-value-select');
    if (typeSelect && valSelect) {
      typeSelect.addEventListener('change', function() {
        if (this.value === 'category') { valSelect.style.display = ''; }
        else { valSelect.style.display = 'none'; if (this.value === 'ideas') valSelect.value = '/galeria'; }
      });
    }
  });

  // Al cambiar la categoría del botón "Ver todos los productos", actualizar el href del preview
  var productosLinkSelect = document.getElementById('productos_button_link_select');
  if (productosLinkSelect) {
    productosLinkSelect.addEventListener('change', function() {
      var a = document.getElementById('productos-btn-preview');
      if (a) a.href = this.value || '#';
    });
  }

  // testimonials_visible y galeria_visible: solo guardan el valor para el index; en el editor las secciones siempre visibles

  // Testimonios: eliminar y agregar
  var testimonialContainer = document.getElementById('testimonials-grid');
  var starOptions = ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'];

  document.addEventListener('click', function(e) {
    if (e.target.closest('.admin-topbar') || e.target.closest('.admin-nav-sidebar')) return;
    if (e.target.closest('.btn-eliminar-testimonial')) {
      e.preventDefault();
      var card = e.target.closest('.testimonial-item');
      if (card) card.remove();
      updateTestimonialAddButton();
    }
  });

  var btnAgregar = document.getElementById('btn-agregar-testimonial');
  function updateTestimonialAddButton() {
    if (!btnAgregar) return;
    var count = document.querySelectorAll('.testimonial-item').length;
    btnAgregar.disabled = count >= 5;
    btnAgregar.title = count >= 5 ? 'Máximo 5 comentarios' : '';
  }
  if (btnAgregar && testimonialContainer) {
    updateTestimonialAddButton();
    btnAgregar.addEventListener('click', function(e) {
      e.preventDefault();
      if (this.disabled) return;
      var count = document.querySelectorAll('.testimonial-item').length;
      if (count >= 5) return;
      var card = document.createElement('div');
      card.className = 'testimonial-card testimonial-item';
      card.dataset.index = count;
      var opts = starOptions.map(function(s) { return '<option value="' + s + '"' + (s === '⭐⭐⭐⭐⭐' ? ' selected' : '') + '>' + s + '</option>'; }).join('');
      card.innerHTML =
        '<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:var(--space-sm);">' +
        '<label style="font-size:0.85rem;color:var(--text-secondary);">Estrellas:</label>' +
        '<select name="testimonial_stars[]" class="testimonial-stars-select" style="padding:0.25rem 0.5rem;border:1px solid #ddd;border-radius:4px;font-size:1.1rem;">' + opts + '</select>' +
        '</div>' +
        '<div class="editable-group" style="margin-bottom:0.5rem;">' +
        '<textarea name="testimonial_text[]" rows="2" placeholder="Comentario" style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px;resize:vertical;"></textarea>' +
        '</div>' +
        '<div class="editable-group">' +
        '<input type="text" name="testimonial_client_name[]" placeholder="Nombre del cliente" style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px;">' +
        '</div>' +
        '<button type="button" class="btn-eliminar-testimonial" style="margin-top:0.5rem;padding:0.25rem 0.5rem;font-size:0.8rem;border:1px solid #dc3545;color:#dc3545;background:transparent;border-radius:4px;cursor:pointer;">Eliminar</button>';
      testimonialContainer.appendChild(card);
      updateTestimonialAddButton();
    });
  }

  // Colores: sincronizar picker y hex
  var pl = document.getElementById('primary_color_light');
  var plHex = document.getElementById('primary_color_light_hex');
  if (pl && plHex) {
    pl.addEventListener('input', function() { plHex.value = this.value; });
    plHex.addEventListener('input', function() { if (/^#[a-fA-F0-9]{6}$/.test(this.value)) pl.value = this.value; });
  }
  var pd = document.getElementById('primary_color_dark');
  var pdHex = document.getElementById('primary_color_dark_hex');
  if (pd && pdHex) {
    pd.addEventListener('input', function() { pdHex.value = this.value; });
    pdHex.addEventListener('input', function() { if (/^#[a-fA-F0-9]{6}$/.test(this.value)) pd.value = this.value; });
  }

  // Guardar por sección
  var form = document.getElementById('landing-form');
  var csrfToken = form && form.querySelector('input[name="csrf_token"]') ? form.querySelector('input[name="csrf_token"]').value : '';

  function getSectionFields(sectionName) {
    var sectionEl = document.querySelector('.landing-section[data-section="' + sectionName + '"]');
    if (!sectionEl) return [];
    var fields = [];
    sectionEl.querySelectorAll('input, select, textarea').forEach(function(el) {
      if (el.name) fields.push(el);
    });
    return fields;
  }

  document.querySelectorAll('.btn-save-section').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var section = this.getAttribute('data-section');
      if (!section || !form || !csrfToken) return;
      var sectionEl = document.querySelector('.landing-section[data-section="' + section + '"]');
      if (!sectionEl) return;

      if (section === 'colors') {
        if (plHex && pl && /^#[a-fA-F0-9]{6}$/.test(plHex.value)) pl.value = plHex.value;
        if (pdHex && pd && /^#[a-fA-F0-9]{6}$/.test(pdHex.value)) pd.value = pdHex.value;
      }

      var fd = new FormData();
      fd.append('section', section);
      fd.append('csrf_token', csrfToken);

      getSectionFields(section).forEach(function(el) {
        if (el.type === 'file' && el.files && el.files.length > 0) {
          for (var i = 0; i < el.files.length; i++) fd.append(el.name + (el.multiple ? '[]' : ''), el.files[i]);
        } else if (el.type === 'checkbox') {
          if (el.checked) fd.append(el.name, '1');
        } else if (el.name && el.value !== undefined) {
          fd.append(el.name, el.value);
        }
      });

      var originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Guardando...';

      fetch('<?= ADMIN_URL ?>/api/landing-save-section.php', {
        method: 'POST',
        body: fd
      }).then(function(r) { return r.json(); }).then(function(data) {
        btn.disabled = false;
        btn.textContent = originalText;
        if (data.success) {
          var actions = sectionEl.querySelector('.section-actions');
          if (actions) {
            actions.querySelectorAll('.btn').forEach(function(b) { b.style.display = 'none'; });
            var oldMsg = actions.querySelector('.alert');
            if (oldMsg) oldMsg.remove();
            var msg = document.createElement('div');
            msg.className = 'alert alert-success';
            msg.style.cssText = 'margin:0;';
            msg.textContent = '✅ ' + (data.message || 'Guardado');
            actions.appendChild(msg);
            setTimeout(function() {
              msg.remove();
              actions.querySelectorAll('.btn').forEach(function(b) { b.style.display = ''; });
              hideSectionActions(section);
            }, 4000);
          } else {
            hideSectionActions(section);
          }
          if (section === 'carousel') mountCarousel();
        } else {
          alert('Error: ' + (data.message || 'No se pudo guardar'));
        }
      }).catch(function(err) {
        btn.disabled = false;
        btn.textContent = 'Guardar cambios';
        alert('Error de conexión. Intenta de nuevo.');
      });
    });
  });

  document.querySelectorAll('.btn-cancel-section').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (confirm('¿Descartar los cambios de esta sección? Se recargará la página.')) {
        window.location.reload();
      }
    });
  });

  // Mostrar botones solo cuando hay cambios en la sección
  function showSectionActions(sectionName) {
    var sectionEl = document.querySelector('.landing-section[data-section="' + sectionName + '"]');
    if (sectionEl) {
      var actions = sectionEl.querySelector('.section-actions');
      if (actions) actions.style.display = 'flex';
    }
  }
  function hideSectionActions(sectionName) {
    var sectionEl = document.querySelector('.landing-section[data-section="' + sectionName + '"]');
    if (sectionEl) {
      var actions = sectionEl.querySelector('.section-actions');
      if (actions) actions.style.display = 'none';
    }
  }
  document.querySelectorAll('.landing-section').forEach(function(sectionEl) {
    var sectionName = sectionEl.getAttribute('data-section');
    if (!sectionName) return;
    var handler = function() { showSectionActions(sectionName); };
    sectionEl.addEventListener('input', handler);
    sectionEl.addEventListener('change', handler);
    sectionEl.addEventListener('click', function(e) {
      if (e.target.closest('.btn-eliminar-slide') || e.target.closest('.btn-eliminar-testimonial') ||
          e.target.closest('#btn-agregar-carousel') || e.target.closest('#btn-agregar-testimonial') ||
          e.target.closest('.btn-change-img')) {
        showSectionActions(sectionName);
      }
    });
  });
});
</script>
