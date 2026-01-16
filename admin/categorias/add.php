<?php
/**
 * Agregar nueva categoría
 */
$pageTitle = 'Agregar Categoría';
require_once '../../config.php';
require_once '../../helpers/slugify.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../../helpers/auth.php';
require_once '../../helpers/categories.php';
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
            'catalog_title' => sanitize($_POST['catalog_title'] ?? ''),
            'min_quantity' => !empty($_POST['min_quantity']) ? (int)$_POST['min_quantity'] : null,
            'visible' => isset($_POST['visible']) ? 1 : 0,
            'orden' => (int)($_POST['orden'] ?? 0)
        ];
        
        // Validaciones
        if (empty($formData['name'])) {
            $error = 'El nombre de la categoría es requerido';
        } else {
            // Generar slug si está vacío
            if (empty($formData['slug'])) {
                $formData['slug'] = slugify($formData['name']);
            } else {
                $formData['slug'] = slugify($formData['slug']);
            }
            
            // Verificar si el slug ya existe
            if (categorySlugExists($formData['slug'])) {
                $error = 'El slug ya existe. Por favor, elige otro.';
            }
            
            if (empty($error)) {
                // Insertar en BD
                $sql = "INSERT INTO categories (slug, name, catalog_title, min_quantity, visible, orden) 
                        VALUES (:slug, :name, :catalog_title, :min_quantity, :visible, :orden)";
                
                $params = [
                    'slug' => $formData['slug'],
                    'name' => $formData['name'],
                    'catalog_title' => !empty($formData['catalog_title']) ? $formData['catalog_title'] : null,
                    'min_quantity' => $formData['min_quantity'],
                    'visible' => $formData['visible'],
                    'orden' => $formData['orden']
                ];
                
                if (executeQuery($sql, $params)) {
                    $_SESSION['success_message'] = 'Categoría agregada exitosamente';
                    header('Location: list.php');
                    exit;
                } else {
                    $error = 'Error al guardar la categoría en la base de datos';
                }
            }
        }
    }
}

require_once '../_inc/header.php';
$csrfToken = generateCSRFToken();
?>

<div class="admin-content">
    <h2>Agregar Nueva Categoría</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" style="max-width: 600px;">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <div class="form-group">
            <label for="name">Nombre de la Categoría *</label>
            <input type="text" id="name" name="name" required 
                   value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                   oninput="updateSlug(this.value)"
                   placeholder="Ej: Día de la Madre">
            <small>Este nombre aparecerá en el selector al agregar productos</small>
        </div>
        
        <div class="form-group">
            <label for="slug">Slug (URL amigable) *</label>
            <input type="text" id="slug" name="slug" required 
                   value="<?= htmlspecialchars($formData['slug'] ?? '') ?>"
                   pattern="[a-z0-9-]+" 
                   title="Solo letras minúsculas, números y guiones">
            <small>Se genera automáticamente desde el nombre. Se usará en las URLs: /slug/</small>
        </div>
        
        <div class="form-group">
            <label for="catalog_title">Título del Catálogo</label>
            <input type="text" id="catalog_title" name="catalog_title" 
                   value="<?= htmlspecialchars($formData['catalog_title'] ?? '') ?>"
                   placeholder="Dejar vacío para usar: 'Catálogo de (nombre)'">
            <small>Si se deja vacío, el título será "Catálogo de [Nombre]". Este título aparecerá en la página del catálogo.</small>
        </div>
        
        <div class="form-group">
            <label for="min_quantity">Cantidad Mínima de Compra</label>
            <input type="number" id="min_quantity" name="min_quantity" 
                   value="<?= htmlspecialchars($formData['min_quantity'] ?? '') ?>"
                   min="1" 
                   placeholder="Ej: 10">
            <small>Si se establece (ej: 10), al agregar productos al carrito se agregará automáticamente esta cantidad mínima, y no se podrá reducir por debajo de este número. Dejar vacío si no hay mínimo.</small>
        </div>
        
        <div class="form-group">
            <label for="orden">Orden</label>
            <input type="number" id="orden" name="orden" 
                   value="<?= htmlspecialchars($formData['orden'] ?? '0') ?>"
                   min="0">
            <small>Número para ordenar las categorías (menor = aparece primero)</small>
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="visible" name="visible" 
                       <?= ($formData['visible'] ?? 1) ? 'checked' : '' ?>>
                <label for="visible">Visible en la Web</label>
            </div>
            <small>
                Si está marcado, la categoría aparecerá en el sitio web. 
                Si no está marcado, solo aparecerá en el admin (útil para preparar categorías antes de publicarlas).
            </small>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">Guardar Categoría</button>
            <a href="list.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
function updateSlug(name) {
    const slugInput = document.getElementById('slug');
    if (!slugInput.value || slugInput.value === '') {
        // Generar slug básico
        let slug = name.toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/ñ/g, 'n')
            .replace(/Ñ/g, 'n')
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
    }
});
</script>

<?php require_once '../_inc/footer.php'; ?>

