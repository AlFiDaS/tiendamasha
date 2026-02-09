<?php
/**
 * API: Información de la galería (nombre, slug, título)
 * Para navbar, breadcrumb y título de página
 */
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
}

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

require_once __DIR__ . '/../config.php';

try {
    $row = fetchOne("SELECT name, title FROM gallery_info WHERE id = 1 LIMIT 1");
    if ($row) {
        echo json_encode([
            'success' => true,
            'name' => $row['name'],
            'slug' => 'galeria',
            'title' => $row['title']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'name' => 'Galeria de Ideas',
            'slug' => 'galeria',
            'title' => 'Galería de ideas'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => true,
        'name' => 'Galeria de Ideas',
        'slug' => 'galeria',
        'title' => 'Galería de ideas'
    ], JSON_UNESCAPED_UNICODE);
}
