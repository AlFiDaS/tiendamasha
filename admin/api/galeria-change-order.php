<?php
/**
 * API para cambiar el orden de imágenes en la galería
 */
define('LUME_ADMIN', true);
require_once '../../config.php';
require_once '../../helpers/db.php';
require_once '../../helpers/auth.php';

// Requerir autenticación
requireAuth();

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$direction = $input['direction'] ?? null; // 'up' o 'down'

// Validar
if (empty($id) || !in_array($direction, ['up', 'down'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos o inválidos']);
    exit;
}

// Iniciar transacción
if (!beginTransaction()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al iniciar transacción']);
    exit;
}

try {
    // Obtener todos los items ordenados exactamente como se muestran en la lista
    $allItems = fetchAll("SELECT id, orden FROM galeria ORDER BY orden ASC, id ASC", []);
    
    if (empty($allItems)) {
        throw new Exception('No hay imágenes en la galería');
    }
    
    // Encontrar la posición del item actual en el array ordenado
    $currentIndex = -1;
    $searchId = (int)$id;
    foreach ($allItems as $index => $item) {
        $itemId = (int)$item['id'];
        if ($itemId === $searchId) {
            $currentIndex = $index;
            break;
        }
    }
    
    if ($currentIndex === -1) {
        rollback();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Imagen no encontrada. ID buscado: ' . $id . ', IDs disponibles: ' . implode(', ', array_column($allItems, 'id'))
        ]);
        exit;
    }
    
    // Determinar el índice objetivo
    if ($direction === 'up') {
        // Mover hacia atrás (menor índice = menor orden visual)
        $targetIndex = $currentIndex - 1;
    } else {
        // Mover hacia adelante (mayor índice = mayor orden visual)
        $targetIndex = $currentIndex + 1;
    }
    
    // Validar que el índice objetivo es válido
    if ($targetIndex < 0 || $targetIndex >= count($allItems)) {
        // Ya está en la primera/última posición
        rollback();
        echo json_encode([
            'success' => false,
            'message' => 'La imagen ya está en la posición límite'
        ]);
        exit;
    }
    
    // Obtener los items actual y objetivo
    $currentItem = $allItems[$currentIndex];
    $targetItem = $allItems[$targetIndex];
    
    $currentOrden = (int)($currentItem['orden'] ?? 0);
    $targetOrden = (int)($targetItem['orden'] ?? 0);
    
    // Si los órdenes son iguales, primero renumeramos todos los items para asegurar órdenes únicos
    if ($currentOrden === $targetOrden) {
        // Renumerar todos los items con órdenes consecutivos empezando desde 1
        // Primero obtener todos los IDs en el orden correcto
        $allIds = fetchAll("SELECT id FROM galeria ORDER BY orden ASC, id ASC", []);
        
        if (!empty($allIds)) {
            // Actualizar cada item con un orden consecutivo
            foreach ($allIds as $idx => $row) {
                $newOrden = $idx + 1;
                executeQuery("UPDATE galeria SET orden = :orden WHERE id = :id", [
                    'orden' => $newOrden,
                    'id' => (int)$row['id']
                ]);
            }
            
            // Volver a obtener los items después de renumerar
            $allItems = fetchAll("SELECT id, orden FROM galeria ORDER BY orden ASC, id ASC", []);
            $currentItem = $allItems[$currentIndex];
            $targetItem = $allItems[$targetIndex];
            $currentOrden = (int)($currentItem['orden'] ?? 0);
            $targetOrden = (int)($targetItem['orden'] ?? 0);
        }
    }
    
    // Intercambiar los órdenes de manera segura usando un valor temporal único
    // Usar un valor temporal muy negativo y único para evitar conflictos
    $tempOrden = -999999 - (int)$id - time();
    
    // Paso 1: Mover el item actual a un orden temporal único
    $result1 = executeQuery("UPDATE galeria SET orden = :orden WHERE id = :id", [
        'orden' => $tempOrden,
        'id' => (int)$id
    ]);
    
    if ($result1 === false) {
        throw new Exception('Error al actualizar orden temporal del item actual');
    }
    
    // Paso 2: Mover el item objetivo al orden del actual
    $result2 = executeQuery("UPDATE galeria SET orden = :orden WHERE id = :id", [
        'orden' => $currentOrden,
        'id' => (int)$targetItem['id']
    ]);
    
    if ($result2 === false) {
        throw new Exception('Error al actualizar orden del item objetivo');
    }
    
    // Paso 3: Mover el item actual al orden del objetivo
    $result3 = executeQuery("UPDATE galeria SET orden = :orden WHERE id = :id", [
        'orden' => $targetOrden,
        'id' => (int)$id
    ]);
    
    if ($result3 === false) {
        throw new Exception('Error al actualizar orden del item actual');
    }
    
    // Confirmar transacción
    if (!commit()) {
        throw new Exception('Error al confirmar transacción');
    }
    
    // Verificar que el intercambio se haya realizado correctamente
    $verifyCurrent = fetchOne("SELECT orden FROM galeria WHERE id = :id", ['id' => (int)$id]);
    $verifyTarget = fetchOne("SELECT orden FROM galeria WHERE id = :id", ['id' => (int)$targetItem['id']]);
    
    if (!$verifyCurrent || !$verifyTarget) {
        throw new Exception('Error al verificar el intercambio');
    }
    
    $newCurrentOrden = (int)($verifyCurrent['orden'] ?? 0);
    $newTargetOrden = (int)($verifyTarget['orden'] ?? 0);
    
    // Verificar que los órdenes se hayan intercambiado correctamente
    if ($newCurrentOrden !== $targetOrden || $newTargetOrden !== $currentOrden) {
        throw new Exception('El intercambio no se completó correctamente. Esperado: actual=' . $targetOrden . ', objetivo=' . $currentOrden . '. Obtenido: actual=' . $newCurrentOrden . ', objetivo=' . $newTargetOrden);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Orden actualizado correctamente',
        'debug' => [
            'currentIndex' => $currentIndex,
            'targetIndex' => $targetIndex,
            'currentOrden' => $currentOrden,
            'targetOrden' => $targetOrden,
            'newCurrentOrden' => $newCurrentOrden,
            'newTargetOrden' => $newTargetOrden
        ]
    ]);
    
} catch (Exception $e) {
    // Revertir transacción
    rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

