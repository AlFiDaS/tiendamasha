<?php
/**
 * Actualizar estado de orden
 */
require_once '../../config.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/orders.php';
require_once '../../helpers/stock.php';
require_once '../../helpers/upload.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
startSecureSession();
requireAuth();

$ordenId = intval($_POST['order_id'] ?? 0);
$nuevoEstado = sanitize($_POST['status'] ?? '');

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (empty($ordenId) || empty($nuevoEstado)) {
    $_SESSION['error_message'] = 'Datos inválidos';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }
    header('Location: list.php');
    exit;
}

// Validar estado válido
$estadosValidos = ['a_confirmar', 'approved', 'pending', 'rejected', 'cancelled', 'finalizado'];
if (!in_array($nuevoEstado, $estadosValidos)) {
    $_SESSION['error_message'] = 'Estado inválido';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Estado inválido']);
        exit;
    }
    header('Location: detail.php?id=' . $ordenId);
    exit;
}

// Obtener orden actual
$orden = fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $ordenId]);

if (!$orden) {
    $_SESSION['error_message'] = 'Orden no encontrada';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Orden no encontrada']);
        exit;
    }
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
    
    // Descontar stock cuando la orden pasa a aprobada (transferencias confirmadas)
    if ($nuevoEstado === 'approved' && ($orden['status'] ?? '') !== 'approved') {
        $stockResult = processOrderStockOnApproval($ordenId);
        if (!$stockResult['success'] && !empty($stockResult['errors'])) {
            $_SESSION['warning_message'] = 'Estado actualizado, pero hubo problemas al descontar stock: ' . implode('; ', $stockResult['errors']);
        }
    }
    
    // Restaurar stock cuando una orden aprobada pasa a CUALQUIER otro estado (rechazada, cancelada, pendiente, etc.)
    if (($orden['status'] ?? '') === 'approved' && $nuevoEstado !== 'approved') {
        $stockResult = processOrderStockOnRejection($ordenId);
        if (!$stockResult['success'] && !empty($stockResult['errors'])) {
            $_SESSION['warning_message'] = 'Estado actualizado, pero hubo problemas al restaurar stock: ' . implode('; ', $stockResult['errors']);
        }
    }
    
    $_SESSION['success_message'] = 'Estado actualizado correctamente';
}

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'status' => $nuevoEstado]);
    exit;
}

header('Location: detail.php?id=' . $ordenId);
exit;

