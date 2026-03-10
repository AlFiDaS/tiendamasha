<?php
/**
 * Agregar nuevo producto
 */
$pageTitle = 'Agregar Producto';
require_once '../config.php';
require_once '../helpers/upload.php';
require_once '../helpers/slugify.php';
require_once '../helpers/categories.php';
require_once __DIR__ . '/../helpers/plans.php';

// Necesitamos autenticación pero sin incluir el header todavía
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';
startSecureSession();
requireAuth();

$error = '';
$formData = [];

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
            'stock_type' => $_POST['stock_type'] ?? 'unlimited',
            'stock_quantity' => !empty($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : null,
            'stock_minimo' => !empty($_POST['stock_minimo']) ? (int)$_POST['stock_minimo'] : 5,
            'destacado' => isset($_POST['destacado']) ? 1 : 0,
            'visible' => isset($_POST['visible']) ? 1 : 1  // Por defecto visible = 1
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
            // Verificar límite de productos según plan
            $planCheck = canStoreAddProduct(null, null, null);
            if (!$planCheck['allowed']) {
                $error = $planCheck['error'];
            }
        }

        if (empty($error)) {
            // Generar slug si está vacío
            if (empty($formData['slug'])) {
                $formData['slug'] = generateUniqueSlug($formData['name']);
            } else {
                $formData['slug'] = slugify($formData['slug']);
                // Verificar si el slug ya existe
                if (slugExists($formData['slug'])) {
                    $error = 'El slug ya existe. Por favor, elige otro.';
                }
            }
            
            // Validar que el slug no contenga 'ñ' o 'Ñ'
            if (strpos($formData['slug'], 'ñ') !== false || strpos($formData['slug'], 'Ñ') !== false) {
                $error = 'El slug no puede contener la letra "ñ". Se reemplazará automáticamente por "n".';
                $formData['slug'] = str_replace(['ñ', 'Ñ'], 'n', $formData['slug']);
                // Verificar si el slug modificado ya existe
                if (slugExists($formData['slug'])) {
                    $error = 'El slug generado ya existe. Por favor, modifica el nombre o el slug manualmente.';
                }
            }
            
            if (empty($error)) {
                // Generar ID único
                $productId = generateProductId();
                
                // Procesar imágenes
                $imagePath = '';
                $hoverImagePath = '';
                
                // Imagen principal
                if (!empty($_FILES['image']['name'])) {
                    $uploadResult = uploadProductImage($_FILES['image'], $formData['slug'], $formData['categoria'], 'main');
                    if ($uploadResult['success']) {
                        $imagePath = $uploadResult['path'];
                    } else {
                        $error = $uploadResult['error'];
                    }
                }
                
                // Imagen hover (opcional)
                if (empty($error) && !empty($_FILES['hoverImage']['name'])) {
                    $uploadResult = uploadProductImage($_FILES['hoverImage'], $formData['slug'], $formData['categoria'], 'hover');
                    if ($uploadResult['success']) {
                        $hoverImagePath = $uploadResult['path'];
                    }
                    // No fallar si la imagen hover falla
                }
                
                if (empty($error)) {
                    // Determinar stock según el tipo
                    if ($formData['stock_type'] === 'limited' && $formData['stock_quantity'] !== null) {
                        $stockValue = max(0, $formData['stock_quantity']);
                    } else {
                        $stockValue = null; // Ilimitado por defecto
                    }
                    
                    // Insertar en BD
                    $sql = "INSERT INTO products 
                            (id, slug, name, descripcion, price, image, hoverImage, stock, stock_minimo, destacado, categoria, visible) 
                            VALUES 
                            (:id, :slug, :name, :descripcion, :price, :image, :hoverImage, :stock, :stock_minimo, :destacado, :categoria, :visible)";
                    
                    $params = [
                        'id' => $productId,
                        'slug' => $formData['slug'],
                        'name' => $formData['name'],
                        'descripcion' => $formData['descripcion'],
                        'price' => $formData['price'],
                        'image' => $imagePath,
                        'hoverImage' => $hoverImagePath,
                        'stock' => $stockValue,
                        'stock_minimo' => $formData['stock_minimo'],
                        'destacado' => $formData['destacado'],
                        'categoria' => $formData['categoria'],
                        'visible' => $formData['visible']
                    ];
                    
                    if (executeQuery($sql, $params)) {
                        $_SESSION['success_message'] = 'Producto agregado exitosamente';
                        header('Location: list.php');
                        exit;
                    } else {
                        $error = 'Error al guardar el producto en la base de datos';
                    }
                }
            }
        }
    }
}

// Solo ahora incluimos el header (después de todas las redirecciones posibles)
require_once '_inc/header.php';

$csrfToken = generateCSRFToken();
$planCheck = canStoreAddProduct(null, null, null);
$planLimits = getPlanLimits(defined('CURRENT_STORE_PLAN') ? CURRENT_STORE_PLAN : 'free');
?>

<div class="admin-content add-product-page">
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Agregar Producto</h1>
            <p class="page-desc">Crea un nuevo producto para tu catálogo</p>
            <?php if ($planCheck['limit'] !== null): ?>
                <p class="page-desc" style="font-size:0.85rem; color:var(--muted, #666); margin-top:0.25rem;">
                    Plan <?= htmlspecialchars($planLimits['name']) ?>: <?= $planCheck['current'] ?> / <?= $planCheck['limit'] ?> productos
                </p>
            <?php endif; ?>
        </div>
        <div class="page-header-actions">
            <a href="list.php" class="btn btn-secondary">← Volver a lista</a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="add-product-form">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <!-- Sección: Información Básica -->
        <div class="form-section">
            <h3 class="section-title">Información Básica</h3>
            
            <div class="form-group">
                <label for="name">Nombre del Producto *</label>
                <input type="text" id="name" name="name" required 
                       value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                       placeholder="Ej: Vela XOXO"
                       oninput="updateSlug(this.value)">
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
                        // Obtener todas las categorías (incluidas las ocultas, para poder crear productos)
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
                        Las categorías ocultas están disponibles aquí para poder crear productos antes de hacerlas visibles.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Sección: Imágenes -->
        <div class="form-section">
            <h3 class="section-title">🖼️ Imágenes</h3>
            
            <div class="image-upload-group">
                <div class="image-upload-item">
                    <label class="image-label">Imagen Principal</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="file-input">
                        <label for="image" class="file-input-label">
                            <span class="file-icon">📁</span>
                            <span class="file-text">Seleccionar imagen</span>
                        </label>
                    </div>
                    <small>Formatos: JPG, PNG, WEBP. Máximo 5MB</small>
                    <div id="imagePreview" class="image-preview"></div>
                </div>
                
                <div class="image-upload-item">
                    <label class="image-label">Imagen Hover (Opcional)</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="hoverImage" name="hoverImage" accept="image/jpeg,image/png,image/webp" class="file-input">
                        <label for="hoverImage" class="file-input-label">
                            <span class="file-icon">📁</span>
                            <span class="file-text">Seleccionar imagen hover</span>
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
                        <input type="radio" name="stock_type" value="unlimited" checked
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
                <div id="stock-limited-fields" class="stock-limited-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="stock_quantity">Cantidad en Stock</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" 
                                   min="0" step="1"
                                   value="<?= htmlspecialchars($formData['stock_quantity'] ?? '') ?>"
                                   placeholder="0">
                        </div>
                        <div class="form-group">
                            <label for="stock_minimo">Stock Mínimo (alerta)</label>
                            <input type="number" id="stock_minimo" name="stock_minimo" 
                                   min="0" step="1"
                                   value="<?= htmlspecialchars($formData['stock_minimo'] ?? 5) ?>"
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
                               <?= ($formData['visible'] ?? 1) ? 'checked' : '' ?>>
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
                <span>💾 Guardar Producto</span>
            </button>
            <a href="list.php" class="btn btn-secondary btn-large">
                <span>← Cancelar</span>
            </a>
        </div>
    </form>
</div>

<script>
function updateSlug(name) {
    const slugInput = document.getElementById('slug');
    if (!slugInput.value || slugInput.value === '') {
        // Generar slug básico (sin hacer llamada al servidor)
        let slug = name.toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/ñ/g, 'n')  // Reemplazar ñ por n
            .replace(/Ñ/g, 'n')  // Reemplazar Ñ por n
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
        slugInput.value = slug;
    }
}

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
})();

// Preview de imágenes
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = 
                '<img src="' + e.target.result + '" class="preview-image-new" alt="Preview">';
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
                '<img src="' + e.target.result + '" class="preview-image-new" alt="Preview">';
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
.alert-warning {
    margin-top: 0.75rem;
    padding: 0.85rem 1rem;
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 8px;
    font-size: 0.88rem;
    color: #92400e;
}
</style>

<?php require_once '_inc/footer.php'; ?>
