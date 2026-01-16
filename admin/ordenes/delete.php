<?php
/**
 * Eliminar orden
 */
require_once '../../config.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/upload.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
startSecureSession();
requireAuth();

$ordenId = intval($_GET['id'] ?? 0);

if (empty($ordenId)) {
    $_SESSION['error_message'] = 'ID de orden no válido';
    header('Location: list.php');
    exit;
}

// Obtener orden
$orden = fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $ordenId]);

if (!$orden) {
    $_SESSION['error_message'] = 'Orden no encontrada';
    header('Location: list.php');
    exit;
}

// Si existe comprobante, eliminarlo
if (!empty($orden['proof_image'])) {
    deleteProofImage($orden['proof_image']);
}

// Eliminar orden de BD
$sql = "DELETE FROM orders WHERE id = :id";
if (executeQuery($sql, ['id' => $ordenId])) {
    $_SESSION['success_message'] = 'Orden eliminada exitosamente';
} else {
    $_SESSION['error_message'] = 'Error al eliminar la orden';
}

header('Location: list.php');
exit;

