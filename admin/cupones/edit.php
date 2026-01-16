<?php
/**
 * Editar cupón
 */
$pageTitle = 'Editar Cupón';
require_once '../../config.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../../helpers/auth.php';
require_once '../../helpers/coupons.php';
require_once '../../helpers/categories.php';
startSecureSession();
requireAuth();

$couponId = (int)($_GET['id'] ?? 0);
$coupon = getCouponById($couponId);

if (!$coupon) {
    $_SESSION['error_message'] = 'Cupón no encontrado';
    header('Location: list.php');
    exit;
}

$error = '';
$formData = $coupon;

// Procesar formulario ANTES de incluir el header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $formData = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'type' => $_POST['type'] ?? 'percentage',
            'value' => (float)($_POST['value'] ?? 0),
            'min_purchase' => (float)($_POST['min_purchase'] ?? 0),
            'max_discount' => !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null,
            'usage_limit' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null,
            'valid_from' => !empty($_POST['valid_from']) ? $_POST['valid_from'] : null,
            'valid_until' => !empty($_POST['valid_until']) ? $_POST['valid_until'] : null,
            'active' => isset($_POST['active']) ? 1 : 0,
            'applicable_to' => $_POST['applicable_to'] ?? 'all',
            'category_slug' => $_POST['category_slug'] ?? null,
            'product_id' => $_POST['product_id'] ?? null
        ];
        
        if (empty($formData['code'])) {
            $error = 'El código del cupón es requerido';
        } elseif ($formData['value'] <= 0) {
            $error = 'El valor del descuento debe ser mayor a 0';
        } else {
            // Verificar si el código ya existe (excepto el actual)
            $existing = fetchOne("SELECT id FROM coupons WHERE code = :code AND id != :id", 
                                ['code' => $formData['code'], 'id' => $couponId]);
            if ($existing) {
                $error = 'Este código de cupón ya existe';
            } else {
                if (saveCoupon($formData, $couponId)) {
                    $_SESSION['success_message'] = 'Cupón actualizado exitosamente';
                    header('Location: list.php');
                    exit;
                } else {
                    $error = 'Error al actualizar el cupón';
                }
            }
        }
    }
}

// Solo ahora incluimos el header (después de todas las redirecciones posibles)
require_once '../_inc/header.php';
$csrfToken = generateCSRFToken();
$categorias = getAllCategories(false);
?>

<div class="admin-content">
    <h2>Editar Cupón</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" style="max-width: 800px;">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="code">Código del Cupón *</label>
                <input type="text" id="code" name="code" required 
                       value="<?= htmlspecialchars($formData['code'] ?? '') ?>"
                       placeholder="Ej: DESCUENTO20" style="text-transform: uppercase;">
            </div>
            
            <div class="form-group">
                <label for="type">Tipo de Descuento *</label>
                <select id="type" name="type" required onchange="updateDiscountFields()">
                    <option value="percentage" <?= ($formData['type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Porcentaje (%)</option>
                    <option value="fixed" <?= ($formData['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Monto Fijo ($)</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="value" id="value-label">Valor del Descuento *</label>
                <input type="number" id="value" name="value" required step="0.01" min="0.01"
                       value="<?= htmlspecialchars($formData['value'] ?? '') ?>">
                <small id="value-help">Porcentaje de descuento (ej: 20 = 20%)</small>
            </div>
            
            <div class="form-group" id="max-discount-group" style="display: <?= $formData['type'] === 'percentage' ? 'block' : 'none' ?>;">
                <label for="max_discount">Descuento Máximo ($)</label>
                <input type="number" id="max_discount" name="max_discount" step="0.01" min="0"
                       value="<?= htmlspecialchars($formData['max_discount'] ?? '') ?>"
                       placeholder="Opcional">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="min_purchase">Monto Mínimo de Compra ($)</label>
                <input type="number" id="min_purchase" name="min_purchase" step="0.01" min="0"
                       value="<?= htmlspecialchars($formData['min_purchase'] ?? '0') ?>">
            </div>
            
            <div class="form-group">
                <label for="usage_limit">Límite de Usos</label>
                <input type="number" id="usage_limit" name="usage_limit" min="1"
                       value="<?= htmlspecialchars($formData['usage_limit'] ?? '') ?>"
                       placeholder="Dejar vacío = sin límite">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="valid_from">Válido Desde</label>
                <input type="datetime-local" id="valid_from" name="valid_from"
                       value="<?= $formData['valid_from'] ? date('Y-m-d\TH:i', strtotime($formData['valid_from'])) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="valid_until">Válido Hasta</label>
                <input type="datetime-local" id="valid_until" name="valid_until"
                       value="<?= $formData['valid_until'] ? date('Y-m-d\TH:i', strtotime($formData['valid_until'])) : '' ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="applicable_to">Aplicable A *</label>
            <select id="applicable_to" name="applicable_to" required onchange="updateApplicableFields()">
                <option value="all" <?= ($formData['applicable_to'] ?? 'all') === 'all' ? 'selected' : '' ?>>Todos los productos</option>
                <option value="category" <?= ($formData['applicable_to'] ?? '') === 'category' ? 'selected' : '' ?>>Categoría específica</option>
                <option value="product" <?= ($formData['applicable_to'] ?? '') === 'product' ? 'selected' : '' ?>>Producto específico</option>
            </select>
        </div>
        
        <div class="form-group" id="category-group" style="display: <?= $formData['applicable_to'] === 'category' ? 'block' : 'none' ?>;">
            <label for="category_slug">Categoría</label>
            <select id="category_slug" name="category_slug">
                <option value="">Seleccionar categoría</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['slug']) ?>" 
                            <?= ($formData['category_slug'] ?? '') === $cat['slug'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group" id="product-group" style="display: <?= $formData['applicable_to'] === 'product' ? 'block' : 'none' ?>;">
            <label for="product_id">ID del Producto</label>
            <input type="text" id="product_id" name="product_id"
                   value="<?= htmlspecialchars($formData['product_id'] ?? '') ?>"
                   placeholder="Ej: vela-gatito">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="active" value="1" 
                       <?= isset($formData['active']) && $formData['active'] ? 'checked' : '' ?>>
                Cupón activo
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar Cupón</button>
            <a href="list.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
function updateDiscountFields() {
    const type = document.getElementById('type').value;
    const valueLabel = document.getElementById('value-label');
    const valueHelp = document.getElementById('value-help');
    const maxDiscountGroup = document.getElementById('max-discount-group');
    
    if (type === 'percentage') {
        valueLabel.textContent = 'Porcentaje de Descuento (%) *';
        valueHelp.textContent = 'Porcentaje de descuento (ej: 20 = 20%)';
        maxDiscountGroup.style.display = 'block';
    } else {
        valueLabel.textContent = 'Monto del Descuento ($) *';
        valueHelp.textContent = 'Monto fijo en pesos (ej: 5000)';
        maxDiscountGroup.style.display = 'none';
    }
}

function updateApplicableFields() {
    const applicableTo = document.getElementById('applicable_to').value;
    const categoryGroup = document.getElementById('category-group');
    const productGroup = document.getElementById('product-group');
    
    categoryGroup.style.display = applicableTo === 'category' ? 'block' : 'none';
    productGroup.style.display = applicableTo === 'product' ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    updateDiscountFields();
    updateApplicableFields();
});
</script>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../_inc/footer.php'; ?>

