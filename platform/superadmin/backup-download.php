<?php
/**
 * Descargar backup - Solo Super Admin
 */
define('TIENDI_PLATFORM', true);
require_once dirname(__DIR__, 2) . '/platform-config.php';
requireSuperAdmin();

$filename = $_GET['file'] ?? '';
if (empty($filename) || !preg_match('/^backup-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.sql(\.gz)?$/', $filename)) {
    header('Location: ' . PLATFORM_PAGES_URL . '/superadmin/backup.php');
    exit;
}

$filepath = dirname(__DIR__, 2) . '/backups/' . basename($filename);
if (!file_exists($filepath) || !is_file($filepath)) {
    header('Location: ' . PLATFORM_PAGES_URL . '/superadmin/backup.php');
    exit;
}

header('Content-Type: ' . (strpos($filename, '.gz') !== false ? 'application/gzip' : 'application/sql'));
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
