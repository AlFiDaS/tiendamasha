<?php
/**
 * Cerrar sesión
 */
require_once '../config.php';

startSecureSession();
logout();

header('Location: ' . ADMIN_URL . '/login.php');
exit;

