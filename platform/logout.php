<?php
/**
 * Cierre de sesión de la plataforma Somos Tiendi
 */
require_once __DIR__ . '/../platform-config.php';
platformLogout();
header('Location: ' . PLATFORM_PAGES_URL . '/login.php');
exit;
