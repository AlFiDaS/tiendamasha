<?php
/**
 * API para guardar secciones de la Landing Page por separado
 */
define('LUME_ADMIN', true);
require_once '../../config.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/upload.php';
require_once '../../helpers/categories.php';

startSecureSession();
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
    exit;
}

$section = $_POST['section'] ?? '';
$validSections = ['carousel', 'productos', 'sobre', 'testimonials', 'galeria', 'colors'];
if (!in_array($section, $validSections, true)) {
    echo json_encode(['success' => false, 'message' => 'Sección no válida']);
    exit;
}

$settings = fetchOne("SELECT * FROM landing_page_settings WHERE id = 1 LIMIT 1");
if (!$settings) {
    executeQuery("INSERT INTO landing_page_settings (id) VALUES (1)");
    $settings = fetchOne("SELECT * FROM landing_page_settings WHERE id = 1 LIMIT 1");
}

$categories = getAllCategories(true);
$updateData = [];
$params = ['id' => 1];
$imagesToDelete = [];

try {
    switch ($section) {
        case 'carousel':
            $carouselImages = !empty($settings['carousel_images']) ? json_decode($settings['carousel_images'], true) : [];
            $carouselData = [];
            $originalCarouselImages = $carouselImages;

            if (!empty($_POST['carousel_image']) && is_array($_POST['carousel_image'])) {
                foreach ($_POST['carousel_image'] as $index => $imagePath) {
                    if (!empty($imagePath)) {
                        $linkType = $_POST['carousel_link_type'][$index] ?? 'none';
                        $linkValue = $_POST['carousel_link_value'][$index] ?? '';
                        $link = '';
                        if ($linkType === 'category' && !empty($linkValue)) {
                            $cat = getCategoryBySlug($linkValue);
                            $link = $cat ? '/' . $linkValue : '';
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
                                    $cat = getCategoryBySlug($linkValue);
                                    $link = $cat ? '/' . $linkValue : '';
                                } elseif ($linkType === 'ideas' && !empty($linkValue)) { $link = '/galeria'; }
                                $carouselData[] = ['image' => '/images/' . $newFilename, 'link' => $link];
                            }
                        }
                    }
                }
            }
            $carouselData = array_slice($carouselData, 0, $maxCarouselImages);

            foreach ($imagesToDelete as $imagePath) {
                $imageFullPath = str_replace('/images/', IMAGES_PATH . '/', $imagePath);
                if (file_exists($imageFullPath)) { @unlink($imageFullPath); }
            }

            $updateData['carousel_images'] = json_encode($carouselData);
            break;

        case 'productos':
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
            $updateData['productos_title'] = $productosTitle;
            $updateData['productos_description'] = $productosDescription;
            $updateData['productos_button_text'] = $productosButtonText;
            $updateData['productos_button_link'] = $productosButtonLink;
            break;

        case 'sobre':
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
            $updateData['sobre_title'] = $sobreData['title'];
            $updateData['sobre_text_1'] = $sobreData['text_1'];
            $updateData['sobre_text_2'] = $sobreData['text_2'];
            $updateData['sobre_image'] = $sobreData['image'] ?? null;
            $updateData['sobre_stat_1_number'] = $sobreData['stat_1_number'];
            $updateData['sobre_stat_1_label'] = $sobreData['stat_1_label'];
            $updateData['sobre_stat_2_number'] = $sobreData['stat_2_number'];
            $updateData['sobre_stat_2_label'] = $sobreData['stat_2_label'];
            $updateData['sobre_stat_3_number'] = $sobreData['stat_3_number'];
            $updateData['sobre_stat_3_label'] = $sobreData['stat_3_label'];
            break;

        case 'testimonials':
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
                    if (!in_array($stars, $validStars, true)) $stars = '⭐⭐⭐⭐⭐';
                    $testimonialsData[] = [
                        'text' => sanitize($text),
                        'client_name' => sanitize($allNames[$index] ?? ''),
                        'stars' => $stars
                    ];
                }
            }
            $updateData['testimonials'] = json_encode($testimonialsData);
            $updateData['testimonials_visible'] = isset($_POST['testimonials_visible']) ? 1 : 0;
            break;

        case 'galeria':
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
            $updateData['galeria_title'] = $galeriaData['title'];
            $updateData['galeria_description'] = $galeriaData['description'];
            $updateData['galeria_image'] = $galeriaData['image'] ?? null;
            $updateData['galeria_link'] = $galeriaData['link'];
            $updateData['galeria_visible'] = $galeriaData['visible'];
            $updateData['galeria_badge'] = $galeriaData['badge'];
            $updateData['galeria_features'] = json_encode($galeriaData['features']);
            $updateData['galeria_button_text'] = $galeriaData['button_text'];
            break;

        case 'colors':
            $primaryColorLight = sanitize($_POST['primary_color_light'] ?? '#ff8c00');
            $primaryColorDark = sanitize($_POST['primary_color_dark'] ?? '#ff8c00');
            if (!preg_match('/^#[a-fA-F0-9]{6}$/', $primaryColorLight)) $primaryColorLight = '#ff8c00';
            if (!preg_match('/^#[a-fA-F0-9]{6}$/', $primaryColorDark)) $primaryColorDark = '#ff8c00';
            $updateData['primary_color_light'] = $primaryColorLight;
            $updateData['primary_color_dark'] = $primaryColorDark;
            break;
    }

    if (empty($updateData)) {
        echo json_encode(['success' => false, 'message' => 'No hay datos para actualizar']);
        exit;
    }

    // Verificar si productos tiene columnas (puede no existir en BD antigua)
    if ($section === 'productos') {
        $col = fetchOne("SHOW COLUMNS FROM landing_page_settings LIKE 'productos_title'");
        if (empty($col)) {
            echo json_encode(['success' => false, 'message' => 'La tabla no tiene columnas de productos. Ejecuta la migración.']);
            exit;
        }
    }

    $updateFields = [];
    foreach ($updateData as $field => $value) {
        $updateFields[] = "`{$field}` = :{$field}";
        $params[$field] = $value;
    }
    $sql = "UPDATE landing_page_settings SET " . implode(', ', $updateFields) . " WHERE id = :id";

    if (executeQuery($sql, $params)) {
        echo json_encode(['success' => true, 'message' => 'Sección guardada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
}
