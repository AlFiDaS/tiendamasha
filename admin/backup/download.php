<?php
/**
 * Descargar backup
 */
require_once '../../config.php';
require_once '../../helpers/backup.php';
require_once '../../helpers/auth.php';

requireAuth();

$filename = $_GET['file'] ?? '';
if (empty($filename) || !preg_match('/^backup-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.sql(\.gz)?$/', $filename)) {
    die('Archivo inválido');
}

$filepath = BASE_PATH . '/backups/' . basename($filename);
if (!file_exists($filepath)) {
    die('Archivo no encontrado');
}

$mimeType = strpos($filename, '.gz') !== false ? 'application/gzip' : 'application/sql';
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;

