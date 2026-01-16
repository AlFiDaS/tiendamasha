<?php
/**
 * Actualizar estado de orden
 */
require_once '../../config.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/orders.php';
require_once '../../helpers/upload.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
startSecureSession();
requireAuth();

$ordenId = intval($_POST['order_id'] ?? 0);
$nuevoEstado = sanitize($_POST['status'] ?? '');

if (empty($ordenId) || empty($nuevoEstado)) {
    $_SESSION['error_message'] = 'Datos inválidos';
    header('Location: list.php');
    exit;
}

// Validar estado válido
$estadosValidos = ['a_confirmar', 'approved', 'pending', 'rejected', 'cancelled', 'finalizado'];
if (!in_array($nuevoEstado, $estadosValidos)) {
    $_SESSION['error_message'] = 'Estado inválido';
    header('Location: detail.php?id=' . $ordenId);
    exit;
}

// Obtener orden actual
$orden = fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $ordenId]);

if (!$orden) {
    $_SESSION['error_message'] = 'Orden no encontrada';
    header('Location: list.php');
    exit;
}

// Si el nuevo estado es "finalizado" y existe un comprobante, eliminarlo
if ($nuevoEstado === 'finalizado' && !empty($orden['proof_image'])) {
    // Eliminar imagen del comprobante
    $proofPath = BASE_PATH . '/public' . $orden['proof_image'];
    if (file_exists($proofPath)) {
        @unlink($proofPath);
    }
    // Actualizar orden: cambiar estado y eliminar referencia a la imagen
    updateOrder($ordenId, [
        'status' => $nuevoEstado,
        'status_detail' => 'Pedido finalizado y entregado',
        'proof_image' => null // Eliminar referencia a la imagen
    ]);
    $_SESSION['success_message'] = 'Estado actualizado y comprobante eliminado';
} else {
    // Solo actualizar estado
    $statusDetails = [
        'a_confirmar' => 'Esperando confirmación de transferencia',
        'approved' => 'Pago aprobado',
        'pending' => 'Pago pendiente',
        'rejected' => 'Pago rechazado',
        'cancelled' => 'Orden cancelada',
        'finalizado' => 'Pedido finalizado y entregado'
    ];
    
    updateOrder($ordenId, [
        'status' => $nuevoEstado,
        'status_detail' => $statusDetails[$nuevoEstado] ?? ''
    ]);
    $_SESSION['success_message'] = 'Estado actualizado correctamente';
}

header('Location: detail.php?id=' . $ordenId);
exit;

