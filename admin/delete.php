<?php
/**
 * Eliminar producto
 */
require_once '../config.php';
require_once '../helpers/upload.php';

// Necesitamos autenticación pero sin incluir el header todavía
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';
startSecureSession();
requireAuth();

$productId = sanitize($_GET['id'] ?? '');

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

// Procesar eliminación ANTES de incluir el header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Token de seguridad inválido';
    } else {
        // Eliminar imágenes
        if (!empty($product['image'])) {
            deleteProductImage($product['image']);
        }
        if (!empty($product['hoverImage'])) {
            deleteProductImage($product['hoverImage']);
        }
        
        // Eliminar toda la carpeta del producto
        if (!empty($product['slug'])) {
            cleanupImageFolder($product['slug'], $product['categoria']);
        }
        
        // Eliminar de BD
        $sql = "DELETE FROM products WHERE id = :id";
        if (executeQuery($sql, ['id' => $productId])) {
            $_SESSION['success_message'] = 'Producto eliminado exitosamente';
            header('Location: list.php');
            exit;
        } else {
            $_SESSION['error_message'] = 'Error al eliminar el producto';
        }
    }
}

// Solo ahora incluimos el header (después de todas las redirecciones posibles)
require_once '_inc/header.php';

$csrfToken = generateCSRFToken();
?>

<div class="admin-content">
    <h2>Eliminar Producto</h2>
    
    <div class="alert alert-error">
        <strong>¿Estás seguro de eliminar este producto?</strong>
        <p>Esta acción no se puede deshacer.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 2rem 0;">
        <h3><?= htmlspecialchars($product['name']) ?></h3>
        <p><strong>Categoría:</strong> <?= htmlspecialchars($product['categoria']) ?></p>
        <p><strong>Slug:</strong> <?= htmlspecialchars($product['slug']) ?></p>
        <?php if (!empty($product['image'])): ?>
            <div style="margin-top: 1rem;">
                <img src="<?= BASE_URL . $product['image'] ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     style="max-width: 200px; border-radius: 4px;">
            </div>
        <?php endif; ?>
    </div>
    
    <form method="POST" style="display: flex; gap: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <button type="submit" class="btn btn-danger">Sí, Eliminar Producto</button>
        <a href="list.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once '_inc/footer.php'; ?>

