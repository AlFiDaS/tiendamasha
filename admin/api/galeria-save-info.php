<?php
/**
 * API para guardar nombre y título de la galería (gallery_info)
 */
define('LUME_ADMIN', true);
require_once '../../config.php';
require_once '../../helpers/db.php';
require_once '../../helpers/auth.php';

requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
    exit;
}

$newName = trim(sanitize($_POST['galeria_name'] ?? ''));
$newTitle = trim(sanitize($_POST['galeria_title'] ?? ''));

if (empty($newName)) {
    echo json_encode(['success' => false, 'message' => 'El nombre no puede estar vacío']);
    exit;
}
if (empty($newTitle)) {
    echo json_encode(['success' => false, 'message' => 'El título no puede estar vacío']);
    exit;
}

try {
    $updated = executeQuery(
        "UPDATE gallery_info SET name = :name, title = :title, slug = 'galeria' WHERE id = 1",
        ['name' => $newName, 'title' => $newTitle]
    );
    if ($updated) {
        echo json_encode([
            'success' => true,
            'message' => 'Datos actualizados correctamente',
            'name' => $newName,
            'title' => $newTitle
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar. Verifica la tabla gallery_info.']);
}
