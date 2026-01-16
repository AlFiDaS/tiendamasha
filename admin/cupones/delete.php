<?php
/**
 * Eliminar cupón
 */
require_once '../../config.php';
require_once '../../helpers/coupons.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../../helpers/auth.php';
startSecureSession();
requireAuth();

$couponId = (int)($_GET['id'] ?? 0);

if ($couponId > 0) {
    if (deleteCoupon($couponId)) {
        $_SESSION['success_message'] = 'Cupón eliminado exitosamente';
    } else {
        $_SESSION['error_message'] = 'Error al eliminar el cupón';
    }
} else {
    $_SESSION['error_message'] = 'ID de cupón inválido';
}

header('Location: list.php');
exit;

