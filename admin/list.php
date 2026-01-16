<?php
/**
 * Lista de productos con filtros
 */
$pageTitle = 'Lista de Productos';
require_once '../config.php';
require_once '../helpers/categories.php';
require_once '../helpers/cache-bust.php';
require_once '_inc/header.php';

// Filtros
$categoria = $_GET['categoria'] ?? '';
$visible = $_GET['visible'] ?? '';
$stock = $_GET['stock'] ?? '';
$buscar = $_GET['buscar'] ?? '';

// Construir consulta
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($buscar)) {
    // Búsqueda case-insensitive (MySQL utf8mb4_unicode_ci ya es case-insensitive)
    // Buscar en nombre, slug y descripción
    // Usar parámetros separados para evitar problemas con wildcards
    $buscarTerm = '%' . trim($buscar) . '%';
    $sql .= " AND (name LIKE :buscar1 OR slug LIKE :buscar2 OR descripcion LIKE :buscar3)";
    $params['buscar1'] = $buscarTerm;
    $params['buscar2'] = $buscarTerm;
    $params['buscar3'] = $buscarTerm;
}

if (!empty($categoria)) {
    $sql .= " AND categoria = :categoria";
    $params['categoria'] = $categoria;
}

if ($visible !== '') {
    $sql .= " AND visible = :visible";
    $params['visible'] = (int)$visible;
}

if ($stock !== '') {
    if ($stock === 'unlimited') {
        $sql .= " AND stock IS NULL";
    } elseif ($stock === 'limited') {
        $sql .= " AND stock IS NOT NULL AND stock > 0";
    } else {
        $sql .= " AND stock = :stock";
        $params['stock'] = (int)$stock;
    }
}

// Ordenamiento: primero por orden (si existe), luego destacado, luego nombre
// Mismo orden que se usa en la página web
try {
    $checkOrden = fetchOne("SHOW COLUMNS FROM products LIKE 'orden'");
    if ($checkOrden) {
        $sql .= " ORDER BY 
            CASE WHEN orden IS NULL THEN 1 ELSE 0 END,
            orden ASC,
            destacado DESC, 
            name ASC";
    } else {
        $sql .= " ORDER BY destacado DESC, name ASC";
    }
} catch (Exception $e) {
    // Si hay error, usar ordenamiento simple
    $sql .= " ORDER BY destacado DESC, name ASC";
}

$products = fetchAll($sql, $params);
// Asegurar que siempre sea un array
if (!is_array($products)) {
    $products = [];
}

// Debug temporal - verificar búsqueda si no hay resultados
if (!empty($buscar)) {
    // Verificar si hay productos con ese texto sin otros filtros
    $buscarTerm = '%' . trim($buscar) . '%';
    $debugSql = "SELECT COUNT(*) as total FROM products WHERE (name LIKE :buscar1 OR slug LIKE :buscar2 OR descripcion LIKE :buscar3)";
    $debugResult = fetchOne($debugSql, [
        'buscar1' => $buscarTerm,
        'buscar2' => $buscarTerm,
        'buscar3' => $buscarTerm
    ]);
    $debugCount = $debugResult ? $debugResult['total'] : 0;
    
    // Si la búsqueda no devolvió resultados pero debería haberlos
    if (empty($products) && $debugCount > 0) {
        error_log("DEBUG BÚSQUEDA: Se encontraron {$debugCount} productos con '{$buscar}' pero la consulta con filtros no los devolvió");
        error_log("DEBUG SQL: " . $sql);
        error_log("DEBUG Params: " . print_r($params, true));
    }
}
?>

<div class="admin-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h2>Lista de Productos</h2>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="ordenar.php" class="btn btn-secondary">📋 Ordenar Productos</a>
            <a href="add.php" class="btn btn-primary">➕ Agregar Producto</a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="filters-container">
        <form method="GET" class="filters-form">
            <div class="form-group search-group">
                <label>Buscar por nombre</label>
                <input type="text" 
                       name="buscar" 
                       placeholder="Buscar producto..." 
                       value="<?= htmlspecialchars($buscar) ?>">
            </div>
            
            <div class="form-group">
                <label>Categoría</label>
                <select name="categoria">
                    <option value="">Todas</option>
                    <?php 
                    $categorias = getAllCategories(false);
                    foreach ($categorias as $cat): 
                    ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>" 
                                <?= $categoria === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Visible</label>
                <select name="visible">
                    <option value="">Todas</option>
                    <option value="1" <?= $visible === '1' ? 'selected' : '' ?>>Visible</option>
                    <option value="0" <?= $visible === '0' ? 'selected' : '' ?>>Oculta</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Stock</label>
                <select name="stock">
                    <option value="">Todos</option>
                    <option value="unlimited" <?= $stock === 'unlimited' ? 'selected' : '' ?>>Ilimitado</option>
                    <option value="limited" <?= $stock === 'limited' ? 'selected' : '' ?>>Limitado</option>
                    <option value="0" <?= $stock === '0' ? 'selected' : '' ?>>Sin Stock</option>
                </select>
            </div>
            
            <div class="filters-actions">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="list.php" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>
    
    <!-- Tabla de productos -->
    <table>
        <thead>
            <tr>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Estado</th>
                <th>Stock</th>
                <th>Destacado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: #666;">
                        <?php if (!empty($buscar)): ?>
                            No se encontraron productos con "<?= htmlspecialchars($buscar) ?>". 
                            <?php 
                            // Debug: verificar si hay productos sin filtros
                            $buscarTerm = '%' . trim($buscar) . '%';
                            $testSql = "SELECT COUNT(*) as total FROM products WHERE (name LIKE :buscar1 OR slug LIKE :buscar2 OR descripcion LIKE :buscar3)";
                            $testResult = fetchOne($testSql, [
                                'buscar1' => $buscarTerm,
                                'buscar2' => $buscarTerm,
                                'buscar3' => $buscarTerm
                            ]);
                            $testCount = $testResult ? $testResult['total'] : 0;
                            if ($testCount > 0): ?>
                                <br><small style="color: #999;">(Se encontraron <?= $testCount ?> productos con este texto, pero fueron filtrados por otros criterios)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            No se encontraron productos.
                        <?php endif; ?>
                        <br><a href="add.php">Agregar producto</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td data-label="Imagen">
                            <?php if (!empty($product['image'])): ?>
                                <?php 
                                // Limpiar ruta base (sin parámetros previos)
                                $cleanImagePath = preg_replace('/\?.*$/', '', $product['image']);
                                // Agregar cache busting para asegurar que se muestre la versión más reciente
                                $imageUrl = addCacheBust($cleanImagePath);
                                // Construir URL completa
                                $fullImageUrl = BASE_URL . $imageUrl;
                                ?>
                                <img src="<?= $fullImageUrl ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\'%3E%3Crect fill=\'%23f0f0f0\' width=\'60\' height=\'60\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-size=\'10\'%3ESin img%3C/text%3E%3C/svg%3E';">
                            <?php else: ?>
                                <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999;">
                                    Sin img
                                </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Nombre">
                            <strong><?= htmlspecialchars($product['name']) ?></strong><br class="desktop-only">
                            <small style="color: #666;"><?= htmlspecialchars($product['slug']) ?></small>
                        </td>
                        <td data-label="Categoría">
                            <span class="badge badge-info"><?= htmlspecialchars($product['categoria']) ?></span>
                        </td>
                        <td data-label="Precio">
                            <div class="price-editor" data-id="<?= htmlspecialchars($product['id']) ?>">
                                <span class="price-display"><?= htmlspecialchars($product['price'] ?? 'N/A') ?></span>
                                <button type="button" class="btn-edit-price" title="Editar precio" style="background: none; border: none; cursor: pointer; padding: 0.25rem 0.5rem; color: #666; font-size: 0.9rem;">
                                    ✏️
                                </button>
                                <div class="price-edit-form" style="display: none;">
                                    <input type="text" 
                                           class="editable-price-input" 
                                           value="<?= htmlspecialchars($product['price'] ?? '') ?>"
                                           style="width: 100px; padding: 0.25rem 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; margin-right: 0.25rem;">
                                    <button type="button" class="btn-save-price" title="Guardar" style="background: #28a745; border: none; color: white; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem; margin-right: 0.25rem;">
                                        ✓
                                    </button>
                                    <button type="button" class="btn-cancel-price" title="Cancelar" style="background: #dc3545; border: none; color: white; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem;">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td data-label="Estado-Stock-Destacado" class="mobile-info">
                            <label class="toggle-visible" style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" 
                                       class="editable-visible" 
                                       data-id="<?= htmlspecialchars($product['id']) ?>"
                                       <?= $product['visible'] ? 'checked' : '' ?>
                                       style="display: none;">
                                <?php if ($product['visible']): ?>
                                    <span class="badge badge-success">✓ Visible</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">○ Oculta</span>
                                <?php endif; ?>
                            </label>
                            <label class="toggle-stock" style="cursor: pointer; display: inline-block; margin-right: 0.3rem;">
                                <input type="checkbox" 
                                       class="editable-stock" 
                                       data-id="<?= htmlspecialchars($product['id']) ?>"
                                       <?= ($product['stock'] === null || $product['stock'] > 0) ? 'checked' : '' ?>
                                       style="display: none;">
                                <?php if ($product['stock'] === null): ?>
                                    <span class="badge badge-info">Ilimitado</span>
                                <?php elseif ($product['stock'] > 0): ?>
                                    <span class="badge badge-success"><?= (int)$product['stock'] ?> unidades</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Sin Stock</span>
                                <?php endif; ?>
                            </label>
                            <label class="toggle-destacado" style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" 
                                       class="editable-destacado" 
                                       data-id="<?= htmlspecialchars($product['id']) ?>"
                                       <?= $product['destacado'] ? 'checked' : '' ?>
                                       style="display: none;">
                                <?php if ($product['destacado']): ?>
                                    <span class="badge badge-warning">⭐ Destacado</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #e0e0e0; color: #666;">⭐ Destacado</span>
                                <?php endif; ?>
                            </label>
                        </td>
                        <td data-label="Estado" class="desktop-only">
                            <label class="toggle-visible" style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" 
                                       class="editable-visible" 
                                       data-id="<?= htmlspecialchars($product['id']) ?>"
                                       <?= $product['visible'] ? 'checked' : '' ?>
                                       style="display: none;">
                                <?php if ($product['visible']): ?>
                                    <span class="badge badge-success">✓ Visible</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">○ Oculta</span>
                                <?php endif; ?>
                            </label>
                        </td>
                        <td data-label="Stock" class="desktop-only">
                            <label class="toggle-stock" style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" 
                                       class="editable-stock" 
                                       data-id="<?= htmlspecialchars($product['id']) ?>"
                                       <?= ($product['stock'] === null || $product['stock'] > 0) ? 'checked' : '' ?>
                                       style="display: none;">
                                <?php if ($product['stock'] === null): ?>
                                    <span class="badge badge-info">Ilimitado</span>
                                <?php elseif ($product['stock'] > 0): ?>
                                    <span class="badge badge-success"><?= (int)$product['stock'] ?> unidades</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Sin Stock</span>
                                <?php endif; ?>
                            </label>
                        </td>
                        <td data-label="Destacado" class="desktop-only">
                            <label class="toggle-destacado" style="cursor: pointer; display: inline-block;">
                                <input type="checkbox" 
                                       class="editable-destacado" 
                                       data-id="<?= htmlspecialchars($product['id']) ?>"
                                       <?= $product['destacado'] ? 'checked' : '' ?>
                                       style="display: none;">
                                <?php if ($product['destacado']): ?>
                                    <span class="badge badge-warning">⭐ Destacado</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #e0e0e0; color: #666;">⭐ Destacado</span>
                                <?php endif; ?>
                            </label>
                        </td>
                        <td data-label="Acciones">
                            <div class="actions">
                                <a href="edit.php?id=<?= $product['id'] ?>" class="btn-edit">Editar</a>
                                <a href="delete.php?id=<?= $product['id'] ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 2rem; color: #666;">
        Total: <?= is_array($products) ? count($products) : 0 ?> producto(s)
    </div>
</div>

<script>
(function() {
    // Función para formatear precio
    function formatPrice(value) {
        // Remover todo excepto números
        let cleaned = value.replace(/[^\d]/g, '');
        // Si hay números, agregar $
        return cleaned ? '$' + cleaned : '';
    }
    
    // Función para obtener solo el número (sin $)
    function getPriceNumber(value) {
        return value.replace(/[^\d]/g, '');
    }
    
    // Editor de precio con botones
    document.querySelectorAll('.price-editor').forEach(function(editor) {
        const productId = editor.getAttribute('data-id');
        const display = editor.querySelector('.price-display');
        const editBtn = editor.querySelector('.btn-edit-price');
        const editForm = editor.querySelector('.price-edit-form');
        const input = editor.querySelector('.editable-price-input');
        const saveBtn = editor.querySelector('.btn-save-price');
        const cancelBtn = editor.querySelector('.btn-cancel-price');
        
        // Obtener valor original sin $ para comparación
        let originalValue = input.value;
        
        // Formatear precio automáticamente mientras escribe
        input.addEventListener('input', function(e) {
            const cursorPos = e.target.selectionStart;
            const oldValue = e.target.value;
            const oldLength = oldValue.length;
            
            // Formatear
            const formatted = formatPrice(e.target.value);
            e.target.value = formatted;
            
            // Ajustar posición del cursor
            const newLength = formatted.length;
            const lengthDiff = newLength - oldLength;
            const newCursorPos = Math.max(0, cursorPos + lengthDiff);
            e.target.setSelectionRange(newCursorPos, newCursorPos);
        });
        
        // Cuando pierde el foco, asegurar formato
        input.addEventListener('blur', function(e) {
            const numValue = getPriceNumber(e.target.value);
            e.target.value = formatPrice(numValue);
        });
        
        // Mostrar formulario de edición
        editBtn.addEventListener('click', function() {
            display.style.display = 'none';
            editBtn.style.display = 'none';
            editForm.style.display = 'flex';
            editForm.style.alignItems = 'center';
            
            // Obtener solo el número del precio actual (sin $)
            const currentPrice = input.value;
            const numOnly = getPriceNumber(currentPrice);
            input.value = formatPrice(numOnly);
            
            input.focus();
            // Seleccionar solo el número (después del $)
            if (input.value.length > 1) {
                input.setSelectionRange(1, input.value.length);
            } else {
                input.select();
            }
        });
        
        // Guardar cambios
        saveBtn.addEventListener('click', function() {
            // Asegurar formato antes de guardar
            const numValue = getPriceNumber(input.value);
            const formattedValue = formatPrice(numValue);
            input.value = formattedValue;
            
            // Comparar con valor original formateado
            const originalFormatted = formatPrice(originalValue);
            
            if (formattedValue === originalFormatted) {
                // No cambió, solo cancelar
                cancelEdit();
                return;
            }
            
            // Actualizar
            updateField(productId, 'price', formattedValue, function(success) {
                if (success) {
                    originalValue = formattedValue;
                    display.textContent = formattedValue;
                    cancelEdit();
                    
                    // Feedback visual
                    display.style.color = '#28a745';
                    setTimeout(function() {
                        display.style.color = '';
                    }, 1000);
                } else {
                    input.style.borderColor = '#dc3545';
                    setTimeout(function() {
                        input.style.borderColor = '#ddd';
                    }, 2000);
                    alert('Error al actualizar el precio. Intenta nuevamente.');
                }
            });
        });
        
        // Cancelar edición
        cancelBtn.addEventListener('click', function() {
            cancelEdit();
        });
        
        // Enter para guardar, Escape para cancelar
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveBtn.click();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelBtn.click();
            }
        });
        
        function cancelEdit() {
            // Restaurar valor original formateado
            input.value = formatPrice(originalValue);
            editForm.style.display = 'none';
            display.style.display = 'inline';
            editBtn.style.display = 'inline';
        }
    });
    
    // Actualizar visible
    document.querySelectorAll('.editable-visible').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const productId = this.getAttribute('data-id');
            const newValue = this.checked ? 1 : 0;
            const label = this.closest('label');
            const badge = label.querySelector('.badge');
            
            updateField(productId, 'visible', newValue, function(success) {
                if (success) {
                    if (newValue) {
                        badge.className = 'badge badge-success';
                        badge.textContent = '✓ Visible';
                    } else {
                        badge.className = 'badge badge-warning';
                        badge.textContent = '○ Oculta';
                    }
                } else {
                    // Revertir checkbox
                    this.checked = !this.checked;
                    alert('Error al actualizar. Intenta nuevamente.');
                }
            });
        });
    });
    
    // Actualizar stock
    document.querySelectorAll('.editable-stock').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const productId = this.getAttribute('data-id');
            const newValue = this.checked ? 1 : 0;
            const label = this.closest('label');
            const badge = label.querySelector('.badge');
            
            updateField(productId, 'stock', newValue, function(success) {
                if (success) {
                    // El backend devuelve el nuevo valor de stock
                    // Si es null, mostrar "Ilimitado", si > 0 mostrar cantidad, si 0 mostrar "Sin Stock"
                    // Por ahora, simplificamos: si está checked = disponible
                    if (newValue) {
                        badge.className = 'badge badge-info';
                        badge.textContent = 'Ilimitado';
                    } else {
                        badge.className = 'badge badge-danger';
                        badge.textContent = 'Sin Stock';
                    }
                } else {
                    // Revertir checkbox
                    this.checked = !this.checked;
                    alert('Error al actualizar. Intenta nuevamente.');
                }
            });
        });
    });
    
    // Actualizar destacado
    document.querySelectorAll('.editable-destacado').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const productId = this.getAttribute('data-id');
            const newValue = this.checked ? 1 : 0;
            const label = this.closest('label');
            const badge = label.querySelector('.badge');
            
            if (!badge) {
                console.error('Badge no encontrado');
                this.checked = !this.checked;
                return;
            }
            
            updateField(productId, 'destacado', newValue, function(success) {
                if (success) {
                    if (newValue) {
                        // Activar destacado: badge amarillo
                        badge.className = 'badge badge-warning';
                        badge.textContent = '⭐ Destacado';
                        badge.removeAttribute('style');
                    } else {
                        // Desactivar destacado: badge gris
                        badge.className = 'badge';
                        badge.style.cssText = 'background: #e0e0e0; color: #666;';
                        badge.textContent = '⭐ Destacado';
                    }
                } else {
                    // Revertir checkbox
                    this.checked = !this.checked;
                    alert('Error al actualizar. Intenta nuevamente.');
                }
            });
        });
    });
    
    // Función para actualizar campo
    function updateField(productId, field, value, callback) {
        fetch('<?= ADMIN_URL ?>/api/quick-update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: productId,
                field: field,
                value: value
            })
        })
        .then(response => response.json())
        .then(data => {
            callback(data.success === true);
        })
        .catch(error => {
            console.error('Error:', error);
            callback(false);
        });
    }
})();
</script>

<?php require_once '_inc/footer.php'; ?>

