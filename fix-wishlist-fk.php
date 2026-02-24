<?php
/**
 * Script de corrección única: elimina el FK de wishlist que causa error 1452
 * Ejecutar UNA VEZ desde el navegador: https://tu-dominio.com/fix-wishlist-fk.php
 * Luego ELIMINAR este archivo por seguridad.
 */
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/db.php';

$pdo = getDB();
if (!$pdo) {
    die('<h1>Error</h1><p>No hay conexión a la base de datos.</p>');
}

try {
    $pdo->exec('ALTER TABLE `wishlist` DROP FOREIGN KEY `wishlist_ibfk_1`');
    echo '<h1>OK</h1><p>La restricción de clave foránea de wishlist se eliminó correctamente.</p>';
    echo '<p><strong>Importante:</strong> Eliminá este archivo (fix-wishlist-fk.php) por seguridad.</p>';
} catch (PDOException $e) {
    if (strpos($e->getMessage(), '1091') !== false || strpos($e->getMessage(), "Can't DROP") !== false) {
        echo '<h1>Ya aplicado</h1><p>La restricción ya no existe. La wishlist debería funcionar.</p>';
    } else {
        echo '<h1>Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>Si el nombre del FK es distinto, ejecutá manualmente en phpMyAdmin:</p>';
        echo '<pre>SHOW CREATE TABLE wishlist;</pre>';
        echo '<p>Buscá la línea CONSTRAINT y usá ese nombre en DROP FOREIGN KEY.</p>';
    }
}
