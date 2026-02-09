<?php
/**
 * API para guardar el orden de las imágenes de la galería (drag and drop)
 */
define('LUME_ADMIN', true);
require_once '../../config.php';
require_once '../../helpers/db.php';
require_once '../../helpers/auth.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order = $input['order'] ?? null;

if (!is_array($order) || empty($order)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

beginTransaction();

try {
    foreach ($order as $item) {
        if (empty($item['id']) || !isset($item['orden'])) {
            continue;
        }

        $sql = "UPDATE galeria SET orden = :orden WHERE id = :id";
        $params = [
            'orden' => (int)$item['orden'],
            'id' => (int)$item['id']
        ];

        if (!executeQuery($sql, $params)) {
            throw new Exception('Error al actualizar el orden de la imagen: ' . $item['id']);
        }
    }

    commit();

    echo json_encode([
        'success' => true,
        'message' => 'Orden guardado correctamente',
        'updated' => count($order)
    ]);

} catch (Exception $e) {
    rollback();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
