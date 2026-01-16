<?php
/**
 * Editar producto existente
 */
$pageTitle = 'Editar Producto';
require_once '../config.php';
require_once '../helpers/upload.php';
require_once '../helpers/slugify.php';
require_once '../helpers/categories.php';
require_once '../helpers/cache-bust.php';

// Necesitamos autenticación pero sin incluir el header todavía
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';
startSecureSession();
requireAuth();

$error = '';
$productId = sanitize($_GET['id'] ?? '');

// Validar ID antes de procesar POST
if (empty($productId)) {
    $_SESSION['error_message'] = 'ID de producto no válido';
    header('Location: list.php');
    exit;
}

// Obtener producto
$product = fetchOne("SELECT * FROM products WHERE id = :id", ['id' => $productId]);

if (!$product) {
    $_SESSION['error_message'] = 'Producto no encontrado';
    header('Location: list.php');
    exit;
}

$formData = $product;

// Procesar formulario ANTES de incluir el header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $formData = [
            'name' => sanitize($_POST['name'] ?? ''),
            'slug' => sanitize($_POST['slug'] ?? ''),
            'descripcion' => sanitize($_POST['descripcion'] ?? ''),
            'price' => sanitize($_POST['price'] ?? ''),
            'categoria' => sanitize($_POST['categoria'] ?? 'productos'),
            'stock' => null, // Por defecto ilimitado
            'stock_type' => $_POST['stock_type'] ?? 'unlimited',
            'stock_quantity' => !empty($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : null,
            'stock_minimo' => !empty($_POST['stock_minimo']) ? (int)$_POST['stock_minimo'] : 5,
            'destacado' => isset($_POST['destacado']) ? 1 : 0,
            'visible' => isset($_POST['visible']) ? 1 : 0,
            'en_descuento' => isset($_POST['en_descuento']) ? 1 : 0,
            'precio_descuento' => sanitize($_POST['precio_descuento'] ?? ''),
            'image' => $product['image'],
            'hoverImage' => $product['hoverImage']
        ];
        
        // Validaciones
        if (empty($formData['name'])) {
            $error = 'El nombre del producto es requerido';
        } else {
            // Validar que la categoría exista
            $categoriaObj = getCategoryBySlug($formData['categoria']);
            if (!$categoriaObj) {
                $error = 'Categoría inválida';
            }
        }
        
        if (empty($error)) {
            // Generar slug si está vacío
            if (empty($formData['slug'])) {
                $formData['slug'] = generateUniqueSlug($formData['name'], $productId);
            } else {
                $formData['slug'] = slugify($formData['slug']);
                // Verificar si el slug ya existe (excluyendo el producto actual)
                if (slugExists($formData['slug'], $productId)) {
                    $error = 'El slug ya existe. Por favor, elige otro.';
                }
            }
            
            // Validar que el slug no contenga 'ñ' o 'Ñ'
            if (strpos($formData['slug'], 'ñ') !== false || strpos($formData['slug'], 'Ñ') !== false) {
                $error = 'El slug no puede contener la letra "ñ". Se reemplazará automáticamente por "n".';
                $formData['slug'] = str_replace(['ñ', 'Ñ'], 'n', $formData['slug']);
                // Verificar si el slug modificado ya existe (excluyendo el producto actual)
                if (slugExists($formData['slug'], $productId)) {
                    $error = 'El slug generado ya existe. Por favor, modifica el nombre o el slug manualmente.';
                }
            }
            
            // Detectar si cambió la categoría o el slug
            $categoriaCambio = ($product['categoria'] !== $formData['categoria']);
            $slugCambio = ($product['slug'] !== $formData['slug']);
            
            // Si cambió la categoría o el slug, mover/renombrar las imágenes
            if (empty($error) && ($categoriaCambio || $slugCambio)) {
                $categoriaOrigen = $product['categoria'];
                $categoriaDestino = $formData['categoria'];
                $slugOrigen = $product['slug'];
                $slugDestino = $formData['slug'];
                
                // Caso 1: Cambió la categoría (y posiblemente el slug también)
                if ($categoriaCambio) {
                    // Mover imágenes de la categoría antigua a la nueva
                    $moveResult = moveProductImages($slugOrigen, $categoriaOrigen, $categoriaDestino);
                    if ($moveResult['success']) {
                        // Actualizar rutas si se movieron las imágenes
                        if ($moveResult['imagePath']) {
                            $formData['image'] = $moveResult['imagePath'];
                        }
                        if ($moveResult['hoverImagePath']) {
                            $formData['hoverImage'] = $moveResult['hoverImagePath'];
                        }
                        
                        // Si también cambió el slug, renombrar la carpeta en la nueva categoría
                        if ($slugCambio) {
                            $oldFolder = IMAGES_PATH . '/' . $categoriaDestino . '/' . $slugOrigen;
                            $newFolder = IMAGES_PATH . '/' . $categoriaDestino . '/' . $slugDestino;
                            
                            if (is_dir($oldFolder) && !is_dir($newFolder)) {
                                if (@rename($oldFolder, $newFolder)) {
                                    // Actualizar rutas de imágenes con el nuevo slug
                                    if (!empty($formData['image'])) {
                                        $formData['image'] = str_replace('/' . $slugOrigen . '/', '/' . $slugDestino . '/', $formData['image']);
                                    }
                                    if (!empty($formData['hoverImage'])) {
                                        $formData['hoverImage'] = str_replace('/' . $slugOrigen . '/', '/' . $slugDestino . '/', $formData['hoverImage']);
                                    }
                                }
                            }
                        }
                    } else {
                        // Si falla el movimiento, registrar el error pero continuar
                        error_log('Error al mover imágenes: ' . ($moveResult['error'] ?? 'Desconocido'));
                    }
                }
                // Caso 2: Solo cambió el slug (sin cambiar categoría)
                elseif ($slugCambio) {
                    $oldFolder = IMAGES_PATH . '/' . $categoriaDestino . '/' . $slugOrigen;
                    $newFolder = IMAGES_PATH . '/' . $categoriaDestino . '/' . $slugDestino;
                    
                    if (is_dir($oldFolder) && !is_dir($newFolder)) {
                        if (@rename($oldFolder, $newFolder)) {
                            // Actualizar rutas de imágenes
                            if (!empty($formData['image'])) {
                                $formData['image'] = str_replace('/' . $slugOrigen . '/', '/' . $slugDestino . '/', $formData['image']);
                            }
                            if (!empty($formData['hoverImage'])) {
                                $formData['hoverImage'] = str_replace('/' . $slugOrigen . '/', '/' . $slugDestino . '/', $formData['hoverImage']);
                            }
                        }
                    }
                }
            }
            
            if (empty($error)) {
                // Eliminar imagen principal si se solicita
                if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
                    if (!empty($product['image'])) {
                        deleteProductImage($product['image']);
                    }
                    $formData['image'] = '';
                }
                
                // Procesar nueva imagen principal
                if (!empty($_FILES['image']['name'])) {
                    // Eliminar imagen anterior si existe
                    if (!empty($product['image'])) {
                        deleteProductImage($product['image']);
                    }
                    
                    $uploadResult = uploadProductImage($_FILES['image'], $formData['slug'], $formData['categoria'], 'main');
                    if ($uploadResult['success']) {
                        $formData['image'] = $uploadResult['path'];
                    } else {
                        $error = $uploadResult['error'];
                    }
                }
                
                // Eliminar imagen hover si se solicita
                if (isset($_POST['delete_hoverImage']) && $_POST['delete_hoverImage'] == '1') {
                    if (!empty($product['hoverImage'])) {
                        deleteProductImage($product['hoverImage']);
                    }
                    $formData['hoverImage'] = '';
                }
                
                // Procesar nueva imagen hover
                if (empty($error) && !empty($_FILES['hoverImage']['name'])) {
                    // Eliminar imagen anterior si existe
                    if (!empty($product['hoverImage'])) {
                        deleteProductImage($product['hoverImage']);
                    }
                    
                    $uploadResult = uploadProductImage($_FILES['hoverImage'], $formData['slug'], $formData['categoria'], 'hover');
                    if ($uploadResult['success']) {
                        $formData['hoverImage'] = $uploadResult['path'];
                    } else {
                        $error = $uploadResult['error'] ?? 'Error al subir la imagen hover';
                    }
                }
                
                // Si se cambió el slug, mover imágenes a nueva carpeta
                if (empty($error) && $formData['slug'] !== $product['slug']) {
                    // Esto requeriría mover archivos, por simplicidad lo dejamos así
                    // En producción podrías implementar una función para mover imágenes
                }
                
                if (empty($error)) {
                    // Determinar stock según el tipo
                    if ($formData['stock_type'] === 'limited' && $formData['stock_quantity'] !== null) {
                        $stockValue = max(0, $formData['stock_quantity']);
                    } else {
                        $stockValue = null; // Ilimitado
                    }
                    
                    // Actualizar en BD
                    $sql = "UPDATE products SET 
                            slug = :slug,
                            name = :name,
                            descripcion = :descripcion,
                            price = :price,
                            image = :image,
                            hoverImage = :hoverImage,
                            stock = :stock,
                            stock_minimo = :stock_minimo,
                            destacado = :destacado,
                            categoria = :categoria,
                            visible = :visible,
                            en_descuento = :en_descuento,
                            precio_descuento = :precio_descuento,
                            updated_at = NOW()
                            WHERE id = :id";
                    
                    $params = [
                        'id' => $productId,
                        'slug' => $formData['slug'],
                        'name' => $formData['name'],
                        'descripcion' => $formData['descripcion'],
                        'price' => $formData['price'],
                        'image' => $formData['image'],
                        'hoverImage' => $formData['hoverImage'],
                        'stock' => $stockValue,
                        'stock_minimo' => $formData['stock_minimo'],
                        'destacado' => $formData['destacado'],
                        'categoria' => $formData['categoria'],
                        'visible' => $formData['visible'],
                        'en_descuento' => $formData['en_descuento'],
                        'precio_descuento' => !empty($formData['precio_descuento']) ? $formData['precio_descuento'] : null
                    ];
                    
                    if (executeQuery($sql, $params)) {
                        $_SESSION['success_message'] = 'Producto actualizado exitosamente';
                        header('Location: list.php');
                        exit;
                    } else {
                        $error = 'Error al actualizar el producto en la base de datos';
                    }
                }
            }
        }
    }
}

// Solo ahora incluimos el header (después de todas las redirecciones posibles)
require_once '_inc/header.php';

$csrfToken = generateCSRFToken();
?>

<div class="admin-content edit-product-page">
    <div class="page-header-edit">
        <div>
            <h2>✏️ Editar Producto</h2>
            <p class="page-subtitle">Modifica la información del producto</p>
        </div>
        <a href="list.php" class="btn-back">
            ← Volver a la lista
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="edit-product-form">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <!-- Sección: Información Básica -->
        <div class="form-section">
            <h3 class="section-title">📝 Información Básica</h3>
            
            <div class="form-group">
                <label for="name">Nombre del Producto *</label>
                <input type="text" id="name" name="name" required 
                       value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                       placeholder="Ej: Vela XOXO">
            </div>
            
            <div class="form-group">
                <label for="slug">Slug (URL amigable) *</label>
                <input type="text" id="slug" name="slug" required 
                       value="<?= htmlspecialchars($formData['slug'] ?? '') ?>"
                       pattern="[a-z0-9-]+" 
                       title="Solo letras minúsculas, números y guiones. No se permiten la letra 'ñ'"
                       placeholder="ej: vela-xoxo">
                <small>Se genera automáticamente desde el nombre si está vacío</small>
                <div class="alert-warning">
                    <strong>⚠️ Importante:</strong> No se permiten la letra <strong>"ñ"</strong> en el slug. Si el nombre contiene "ñ", se reemplazará automáticamente por "n".
                </div>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="4" 
                          placeholder="Describe el producto..."><?= htmlspecialchars($formData['descripcion'] ?? '') ?></textarea>
            </div>
        </div>
        
        <!-- Sección: Precio y Categoría -->
        <div class="form-section">
            <h3 class="section-title">💰 Precio y Categoría</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price">Precio</label>
                    <div class="input-with-icon">
                        <span class="input-icon">$</span>
                        <input type="text" id="price" name="price" 
                               value="<?= htmlspecialchars(preg_replace('/[^\d]/', '', $formData['price'] ?? '')) ?>"
                               placeholder="2400">
                    </div>
                    <small>Escribe solo el número (ej: 2400), el símbolo $ se agregará automáticamente</small>
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoría *</label>
                    <select id="categoria" name="categoria" required>
                        <?php 
                        // Obtener todas las categorías (incluidas las ocultas, para poder editar productos)
                        $categorias = getAllCategories(false);
                        $selectedCategoria = $formData['categoria'] ?? 'productos';
                        foreach ($categorias as $cat): 
                        ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" 
                                    <?= $selectedCategoria === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                                <?= !$cat['visible'] ? ' (Oculta)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>
                        Las categorías ocultas están disponibles aquí para poder editar productos.
                    </small>
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-modern">
                    <input type="checkbox" id="en_descuento" name="en_descuento"
                           <?= ($formData['en_descuento'] ?? 0) ? 'checked' : '' ?>
                           onchange="togglePrecioDescuento()">
                    <span class="checkmark"></span>
                    <div>
                        <strong>🏷️ En Descuento</strong>
                        <small>Activar precio en descuento para este producto</small>
                    </div>
                </label>
            </div>
            
            <div id="precio-descuento-group" class="form-group" style="display: <?= ($formData['en_descuento'] ?? 0) ? 'block' : 'none' ?>;">
                <label for="precio_descuento">Precio en Descuento</label>
                <div class="input-with-icon">
                    <span class="input-icon">$</span>
                    <input type="text" id="precio_descuento" name="precio_descuento" 
                           value="<?= htmlspecialchars(preg_replace('/[^\d]/', '', $formData['precio_descuento'] ?? '')) ?>"
                           placeholder="2000">
                </div>
                <small>Escribe solo el número del precio en descuento (ej: 2000). El precio anterior se mostrará tachado.</small>
            </div>
        </div>
        
        <!-- Sección: Imágenes -->
        <div class="form-section">
            <h3 class="section-title">🖼️ Imágenes</h3>
            
            <div class="image-upload-group">
                <div class="image-upload-item">
                    <label class="image-label">Imagen Principal</label>
                    <?php if (!empty($formData['image'])): ?>
                        <?php 
                        // Limpiar ruta base (sin parámetros previos)
                        $cleanImagePath = preg_replace('/\?.*$/', '', $formData['image']);
                        // Agregar cache busting para asegurar que se muestre la versión más reciente
                        $imageUrl = addCacheBust($cleanImagePath);
                        $fullImageUrl = BASE_URL . $imageUrl;
                        ?>
                        <div class="current-image">
                            <img src="<?= $fullImageUrl ?>" 
                                 alt="Imagen actual" 
                                 class="preview-image"
                                 onerror="this.onerror=null; this.style.display='none';">
                            <div class="image-actions">
                                <label class="delete-checkbox">
                                    <input type="checkbox" id="delete_image" name="delete_image" value="1">
                                    <span>🗑️ Eliminar</span>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="file-input-wrapper">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="file-input">
                        <label for="image" class="file-input-label">
                            <span class="file-icon">📁</span>
                            <span class="file-text"><?= !empty($formData['image']) ? 'Cambiar imagen' : 'Seleccionar imagen' ?></span>
                        </label>
                    </div>
                    <small>Formatos: JPG, PNG, WEBP. Máximo 5MB</small>
                    <div id="imagePreview" class="image-preview"></div>
                </div>
                
                <div class="image-upload-item">
                    <label class="image-label">Imagen Hover (Opcional)</label>
                    <?php if (!empty($formData['hoverImage'])): ?>
                        <?php 
                        // Limpiar ruta base (sin parámetros previos)
                        $cleanHoverImagePath = preg_replace('/\?.*$/', '', $formData['hoverImage']);
                        // Agregar cache busting para asegurar que se muestre la versión más reciente
                        $hoverImageUrl = addCacheBust($cleanHoverImagePath);
                        $fullHoverImageUrl = BASE_URL . $hoverImageUrl;
                        ?>
                        <div class="current-image">
                            <img src="<?= $fullHoverImageUrl ?>" 
                                 alt="Imagen hover actual" 
                                 class="preview-image"
                                 onerror="this.onerror=null; this.style.display='none';">
                            <div class="image-actions">
                                <label class="delete-checkbox">
                                    <input type="checkbox" id="delete_hoverImage" name="delete_hoverImage" value="1">
                                    <span>🗑️ Eliminar</span>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="file-input-wrapper">
                        <input type="file" id="hoverImage" name="hoverImage" accept="image/jpeg,image/png,image/webp" class="file-input">
                        <label for="hoverImage" class="file-input-label">
                            <span class="file-icon">📁</span>
                            <span class="file-text"><?= !empty($formData['hoverImage']) ? 'Cambiar imagen hover' : 'Seleccionar imagen hover' ?></span>
                        </label>
                    </div>
                    <small>Imagen que se muestra al pasar el mouse</small>
                    <div id="hoverImagePreview" class="image-preview"></div>
                </div>
            </div>
        </div>
        
        <!-- Sección: Stock y Configuración -->
        <div class="form-section">
            <h3 class="section-title">📦 Stock y Configuración</h3>
            
            <div class="form-group">
                <label class="section-subtitle">Tipo de Stock</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="stock_type" value="unlimited" 
                               <?= ($product['stock'] ?? null) === null ? 'checked' : '' ?>
                               onchange="updateStockFields()">
                        <span class="radio-checkmark"></span>
                        <div class="radio-content">
                            <span class="radio-icon">∞</span>
                            <div>
                                <strong>Ilimitado</strong>
                                <small>Producto hecho bajo pedido</small>
                            </div>
                        </div>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="stock_type" value="limited" 
                               <?= ($product['stock'] ?? null) !== null ? 'checked' : '' ?>
                               onchange="updateStockFields()">
                        <span class="radio-checkmark"></span>
                        <div class="radio-content">
                            <span class="radio-icon">📊</span>
                            <div>
                                <strong>Limitado</strong>
                                <small>Cantidad específica en stock</small>
                            </div>
                        </div>
                    </label>
                </div>
                <div id="stock-limited-fields" class="stock-limited-fields" style="display: <?= ($product['stock'] ?? null) !== null ? 'block' : 'none' ?>;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="stock_quantity">Cantidad en Stock</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" 
                                   min="0" step="1"
                                   value="<?= ($product['stock'] ?? null) !== null ? (int)$product['stock'] : '' ?>"
                                   placeholder="0">
                        </div>
                        <div class="form-group">
                            <label for="stock_minimo">Stock Mínimo (alerta)</label>
                            <input type="number" id="stock_minimo" name="stock_minimo" 
                                   min="0" step="1"
                                   value="<?= htmlspecialchars($product['stock_minimo'] ?? 5) ?>"
                                   placeholder="5">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="section-subtitle">Opciones</label>
                <div class="checkbox-group-modern">
                    <label class="checkbox-modern">
                        <input type="checkbox" id="destacado" name="destacado"
                               <?= ($formData['destacado'] ?? 0) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        <div>
                            <strong>⭐ Producto Destacado</strong>
                            <small>Aparecerá en la sección "Más Vendidos"</small>
                        </div>
                    </label>
                    <label class="checkbox-modern">
                        <input type="checkbox" id="visible" name="visible"
                               <?= ($formData['visible'] ?? 0) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        <div>
                            <strong>👁️ Visible en la Web</strong>
                            <small>El producto será visible para los clientes</small>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-large">
                <span>💾 Guardar Cambios</span>
            </button>
            <a href="list.php" class="btn btn-secondary btn-large">
                <span>❌ Cancelar</span>
            </a>
        </div>
    </form>
</div>

<script>
// Validar slug en tiempo real para evitar ñ
document.getElementById('slug').addEventListener('input', function(e) {
    let value = e.target.value;
    if (value.includes('ñ') || value.includes('Ñ')) {
        value = value.replace(/ñ/g, 'n').replace(/Ñ/g, 'n');
        e.target.value = value;
        // Mostrar advertencia visual
        if (!document.getElementById('slug-warning')) {
            const warning = document.createElement('div');
            warning.id = 'slug-warning';
            warning.style.cssText = 'margin-top: 0.5rem; padding: 0.5rem; background: #f8d7da; color: #721c24; border-radius: 4px; font-size: 0.875rem;';
            warning.textContent = '⚠️ La letra "ñ" ha sido reemplazada por "n"';
            e.target.parentNode.appendChild(warning);
            setTimeout(() => {
                if (warning.parentNode) warning.remove();
            }, 3000);
        }
    }
});

// Formatear precio automáticamente - Solo números (el $ es visual)
(function() {
    const priceInput = document.getElementById('price');
    const precioDescuentoInput = document.getElementById('precio_descuento');
    
    // Función para limpiar y dejar solo números
    function cleanPrice(value) {
        return value.replace(/[^\d]/g, '');
    }
    
    // Cuando el usuario escribe, solo permitir números
    priceInput.addEventListener('input', function(e) {
        e.target.value = cleanPrice(e.target.value);
    });
    
    // Cuando el campo pierde el foco, asegurar que solo tenga números
    priceInput.addEventListener('blur', function(e) {
        e.target.value = cleanPrice(e.target.value);
    });
    
    // Al cargar la página, limpiar el valor si tiene $ u otros caracteres
    const currentValue = priceInput.value;
    if (currentValue) {
        const cleaned = cleanPrice(currentValue);
        priceInput.value = cleaned;
    }
    
    // Formatear precio de descuento automáticamente
    if (precioDescuentoInput) {
        precioDescuentoInput.addEventListener('input', function(e) {
            e.target.value = cleanPrice(e.target.value);
        });
        
        precioDescuentoInput.addEventListener('blur', function(e) {
            e.target.value = cleanPrice(e.target.value);
        });
        
        // Limpiar precio de descuento al cargar si tiene caracteres no numéricos
        const currentDescuentoValue = precioDescuentoInput.value;
        if (currentDescuentoValue) {
            const cleanedDescuento = cleanPrice(currentDescuentoValue);
            precioDescuentoInput.value = cleanedDescuento;
        }
    }
})();

// Mostrar/ocultar campo de precio de descuento
function togglePrecioDescuento() {
    const checkbox = document.getElementById('en_descuento');
    const precioDescuentoGroup = document.getElementById('precio-descuento-group');
    const precioDescuentoInput = document.getElementById('precio_descuento');
    if (checkbox && precioDescuentoGroup) {
        precioDescuentoGroup.style.display = checkbox.checked ? 'block' : 'none';
        if (checkbox.checked && precioDescuentoInput) {
            precioDescuentoInput.focus();
        } else if (precioDescuentoInput && !checkbox.checked) {
            precioDescuentoInput.value = '';
        }
    }
}

// Preview de nuevas imágenes
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = 
                '<strong>Vista previa nueva imagen:</strong><br>' +
                '<img src="' + e.target.result + '" class="preview-image-new" style="max-width: 200px; border-radius: 8px; margin-top: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('hoverImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('hoverImagePreview').innerHTML = 
                '<strong>Vista previa nueva imagen:</strong><br>' +
                '<img src="' + e.target.result + '" class="preview-image-new" style="max-width: 200px; border-radius: 8px; margin-top: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        };
        reader.readAsDataURL(file);
    }
});

function updateStockFields() {
    const stockType = document.querySelector('input[name="stock_type"]:checked').value;
    const limitedFields = document.getElementById('stock-limited-fields');
    limitedFields.style.display = stockType === 'limited' ? 'block' : 'none';
}
</script>

<style>
/* ============================================
   ESTILOS PARA EDITAR PRODUCTO
   ============================================ */

.edit-product-page {
    max-width: 1000px;
    margin: 0 auto;
}

.page-header-edit {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e0e0e0;
}

.page-header-edit h2 {
    margin: 0;
    font-size: 2rem;
    color: #333;
    font-weight: 700;
}

.page-subtitle {
    margin: 0.5rem 0 0 0;
    color: #666;
    font-size: 0.95rem;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #f5f5f5;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s;
    border: 1px solid #e0e0e0;
}

.btn-back:hover {
    background: #e0a4ce;
    color: white;
    border-color: #e0a4ce;
    transform: translateX(-3px);
}

.edit-product-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    background: #fafafa;
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid #e8e8e8;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin: 0 0 1.5rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e0a4ce;
}

.section-subtitle {
    font-size: 1rem;
    font-weight: 600;
    color: #555;
    margin-bottom: 1rem;
    display: block;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
    background: white;
    font-family: inherit;
    box-sizing: border-box;
}

.form-group input[type="text"]:focus,
.form-group input[type="number"]:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #e0a4ce;
    box-shadow: 0 0 0 3px rgba(224, 164, 206, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-group small {
    display: block;
    margin-top: 0.5rem;
    color: #666;
    font-size: 0.85rem;
}

.alert-warning {
    margin-top: 0.75rem;
    padding: 1rem;
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #856404;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.input-with-icon {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
    font-weight: 700;
    font-size: 1rem;
    pointer-events: none;
    z-index: 1;
    user-select: none;
}

.input-with-icon input {
    padding-left: 2.5rem !important;
    font-weight: 500;
}

.form-group .input-with-icon input[type="text"] {
    padding-left: 2.5rem !important;
}

/* Imágenes */
.image-upload-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.image-upload-item {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.image-label {
    font-weight: 600;
    color: #333;
    font-size: 1rem;
}

.current-image {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    background: #f5f5f5;
    border: 2px solid #e0e0e0;
}

.preview-image {
    width: 100%;
    height: auto;
    max-height: 300px;
    object-fit: cover;
    display: block;
}

.image-actions {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
    padding: 1rem;
}

.delete-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    cursor: pointer;
    font-size: 0.9rem;
}

.delete-checkbox input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.file-input-wrapper {
    position: relative;
}

.file-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.file-input-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: white;
    border: 2px dashed #e0a4ce;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    color: #e0a4ce;
    font-weight: 500;
}

.file-input-label:hover {
    background: #fef5fc;
    border-color: #d89bc0;
    transform: translateY(-2px);
}

.file-icon {
    font-size: 1.5rem;
}

.image-preview {
    margin-top: 1rem;
}

.preview-image-new {
    max-width: 200px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Stock */
.radio-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.radio-option {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border: 2px solid #e5e5e5;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
    position: relative;
}

.radio-option:hover {
    border-color: #e0a4ce;
    background: #fef5fc;
    box-shadow: 0 2px 8px rgba(224, 164, 206, 0.1);
}

.radio-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.radio-checkmark {
    width: 22px;
    height: 22px;
    border: 2px solid #d0d0d0;
    border-radius: 6px;
    position: relative;
    flex-shrink: 0;
    transition: all 0.2s ease;
    margin-top: 3px;
    background: white;
}

.radio-option input[type="radio"]:checked + .radio-checkmark {
    background: #e0a4ce;
    border-color: #e0a4ce;
}

.radio-option input[type="radio"]:checked + .radio-checkmark::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
}

.radio-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.radio-icon {
    font-size: 1.25rem;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f8f8;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.radio-option input[type="radio"]:checked ~ .radio-content .radio-icon {
    background: #e0a4ce;
    color: white;
}

.radio-option:has(input[type="radio"]:checked) {
    border-color: #e0a4ce;
    background: #fef5fc;
    box-shadow: 0 2px 8px rgba(224, 164, 206, 0.15);
}

.radio-content strong {
    display: block;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.radio-content small {
    display: block;
    font-size: 0.85rem;
    color: #666;
    margin: 0;
}

.stock-limited-fields {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e0e0e0;
}

/* Checkboxes modernos */
.checkbox-group-modern {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.checkbox-modern {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border: 2px solid #e5e5e5;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
    position: relative;
}

.checkbox-modern:hover {
    border-color: #e0a4ce;
    background: #fef5fc;
    box-shadow: 0 2px 8px rgba(224, 164, 206, 0.1);
}

.checkbox-modern:has(input[type="checkbox"]:checked) {
    border-color: #e0a4ce;
    background: #fef5fc;
}

.checkbox-modern input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.checkmark {
    width: 22px;
    height: 22px;
    border: 2px solid #d0d0d0;
    border-radius: 6px;
    position: relative;
    flex-shrink: 0;
    transition: all 0.2s ease;
    margin-top: 3px;
    background: white;
}

.checkbox-modern input[type="checkbox"]:checked + .checkmark {
    background: #e0a4ce;
    border-color: #e0a4ce;
}

.checkbox-modern input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: 700;
    font-size: 13px;
    line-height: 1;
}

.checkbox-modern strong {
    display: block;
    font-size: 1rem;
    margin-bottom: 0.25rem;
    color: #333;
}

.checkbox-modern small {
    display: block;
    font-size: 0.85rem;
    color: #666;
    margin: 0;
}

/* Botones de acción */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e0e0e0;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.btn-primary.btn-large {
    background: linear-gradient(135deg, #e0a4ce, #d89bc0);
    color: white;
    box-shadow: 0 4px 12px rgba(224, 164, 206, 0.3);
}

.btn-primary.btn-large:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(224, 164, 206, 0.4);
}

.btn-secondary.btn-large {
    background: #f5f5f5;
    color: #333;
    border: 2px solid #e0e0e0;
}

.btn-secondary.btn-large:hover {
    background: #e8e8e8;
    border-color: #d0d0d0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header-edit {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-back {
        width: 100%;
        justify-content: center;
    }
    
    .form-section {
        padding: 1.5rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .image-upload-group {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-large {
        width: 100%;
    }
    
    .radio-group {
        gap: 0.75rem;
    }
    
    .radio-option {
        padding: 0.875rem 1rem;
    }
    
    .checkbox-modern {
        padding: 0.875rem 1rem;
    }
}

@media (max-width: 480px) {
    .page-header-edit h2 {
        font-size: 1.5rem;
    }
    
    .form-section {
        padding: 1rem;
    }
    
    .section-title {
        font-size: 1.25rem;
    }
    
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea,
    .form-group select {
        padding: 0.75rem;
        font-size: 16px; /* Evita zoom en iOS */
    }
}
</style>

<?php require_once '_inc/footer.php'; ?>
