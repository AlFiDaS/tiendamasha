<?php
/**
 * Eliminar categoría
 */
require_once '../../config.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../../helpers/auth.php';
require_once '../../helpers/categories.php';
startSecureSession();
requireAuth();

// Obtener ID de la categoría
$categoriaId = (int)($_GET['id'] ?? 0);
if (!$categoriaId) {
    $_SESSION['error_message'] = 'ID de categoría no proporcionado';
    header('Location: list.php');
    exit;
}

// Obtener categoría actual
$categoria = getCategoryById($categoriaId);
if (!$categoria) {
    $_SESSION['error_message'] = 'Categoría no encontrada';
    header('Location: list.php');
    exit;
}

// Verificar que no tenga productos asociados
$productCount = countProductsInCategory($categoria['slug']);
if ($productCount > 0) {
    $_SESSION['error_message'] = 'No se puede eliminar la categoría porque tiene ' . $productCount . ' producto(s) asociado(s).';
    header('Location: list.php');
    exit;
}

// Eliminar categoría
$sql = "DELETE FROM categories WHERE id = :id";
$params = ['id' => $categoriaId];

if (executeQuery($sql, $params)) {
    $_SESSION['success_message'] = 'Categoría eliminada exitosamente';
} else {
    $_SESSION['error_message'] = 'Error al eliminar la categoría';
}

header('Location: list.php');
exit;

