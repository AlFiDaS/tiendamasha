<?php
/**
 * Script para generar JSON con todas las categorías visibles
 * Se ejecuta antes del build de Astro para generar páginas estáticas
 */

// Cargar configuración
require_once __DIR__ . '/../config.php';

// Cargar helpers
require_once __DIR__ . '/../helpers/categories.php';

try {
    // Obtener todas las categorías visibles
    $categorias = getAllCategories(true); // true = solo visibles
    
    if ($categorias === false) {
        throw new Exception('Error al consultar la base de datos');
    }
    
    // Formatear respuesta
    $output = [
        'success' => true,
        'categories' => $categorias,
        'generated_at' => date('Y-m-d H:i:s')
    ];
    
    // Guardar en archivo JSON
    $jsonFile = __DIR__ . '/../public/categories.json';
    $jsonContent = json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
    if (file_put_contents($jsonFile, $jsonContent) === false) {
        throw new Exception('Error al escribir el archivo JSON');
    }
    
    echo "✅ Archivo categories.json generado exitosamente con " . count($categorias) . " categorías.\n";
    exit(0);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

