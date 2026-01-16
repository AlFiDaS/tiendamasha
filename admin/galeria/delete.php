<?php
/**
 * Eliminar imagen de la galería
 */
require_once '../../config.php';
require_once '../../helpers/upload.php';

// Necesitamos autenticación pero sin incluir el header todavía
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../../helpers/auth.php';
startSecureSession();
requireAuth();

$itemId = (int)($_GET['id'] ?? 0);

if (empty($itemId)) {
    $_SESSION['error_message'] = 'ID de imagen no válido';
    header('Location: list.php');
    exit;
}

// Obtener imagen
$item = fetchOne("SELECT * FROM galeria WHERE id = :id", ['id' => $itemId]);

if (!$item) {
    $_SESSION['error_message'] = 'Imagen no encontrada';
    header('Location: list.php');
    exit;
}

// Eliminar imagen del servidor
if (!empty($item['imagen'])) {
    deleteGaleriaImage($item['imagen']);
}

// Eliminar de la base de datos
$sql = "DELETE FROM galeria WHERE id = :id";
if (executeQuery($sql, ['id' => $itemId])) {
    $_SESSION['success_message'] = 'Imagen eliminada exitosamente';
} else {
    $_SESSION['error_message'] = 'Error al eliminar la imagen';
}

header('Location: list.php');
exit;

