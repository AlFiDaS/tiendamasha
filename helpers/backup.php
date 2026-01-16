<?php
/**
 * ============================================
 * HELPER: Sistema de Backup
 * ============================================
 * Funciones para crear y restaurar backups de la BD
 * Compatible: PHP 7.4+
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

// Asegurar que db.php esté cargado
if (!function_exists('getDB')) {
    require_once __DIR__ . '/db.php';
}

/**
 * Crear backup de la base de datos
 * @param bool $compress Si true, comprime el backup en .gz
 * @return string|false Ruta del archivo de backup o false en error
 */
function createDatabaseBackup($compress = true) {
    $backupDir = BASE_PATH . '/backups';
    
    // Crear directorio si no existe
    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0755, true)) {
            error_log('Error al crear directorio de backups: ' . $backupDir);
            return false;
        }
    }
    
    // Nombre del archivo: backup-YYYY-MM-DD-HH-MM-SS.sql
    $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
    $filepath = $backupDir . '/' . $filename;
    
    // Comando mysqldump
    $command = sprintf(
        'mysqldump -h%s -u%s -p%s %s > %s 2>&1',
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME),
        escapeshellarg($filepath)
    );
    
    // Ejecutar backup
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
        error_log('Error al crear backup: ' . implode("\n", $output));
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        return false;
    }
    
    // Comprimir si se solicita
    if ($compress) {
        $compressedFile = $filepath . '.gz';
        if (function_exists('gzencode')) {
            $data = file_get_contents($filepath);
            $compressed = gzencode($data, 9);
            if (file_put_contents($compressedFile, $compressed, LOCK_EX) !== false) {
                unlink($filepath); // Eliminar archivo sin comprimir
                $filepath = $compressedFile;
            }
        }
    }
    
    return $filepath;
}

/**
 * Crear backup usando PDO (alternativa si mysqldump no está disponible)
 * @param bool $compress
 * @return string|false
 */
function createDatabaseBackupPDO($compress = true) {
    $backupDir = BASE_PATH . '/backups';
    
    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0755, true)) {
            return false;
        }
    }
    
    $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
    $filepath = $backupDir . '/' . $filename;
    
    $pdo = getDB();
    if (!$pdo) {
        return false;
    }
    
    $output = "-- Backup de base de datos\n";
    $output .= "-- Generado: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Base de datos: " . DB_NAME . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    // Obtener todas las tablas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $output .= "-- Estructura de tabla: $table\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $output .= $createTable['Create Table'] . ";\n\n";
        
        // Datos de la tabla
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $output .= "-- Datos de tabla: $table\n";
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = $pdo->quote($value);
                    }
                }
                $output .= "INSERT INTO `$table` ($columnList) VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }
    }
    
    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Guardar archivo
    if (file_put_contents($filepath, $output, LOCK_EX) === false) {
        return false;
    }
    
    // Comprimir si se solicita
    if ($compress) {
        $compressedFile = $filepath . '.gz';
        if (function_exists('gzencode')) {
            $compressed = gzencode($output, 9);
            if (file_put_contents($compressedFile, $compressed, LOCK_EX) !== false) {
                unlink($filepath);
                $filepath = $compressedFile;
            }
        }
    }
    
    return $filepath;
}

/**
 * Obtener lista de backups guardados
 * @return array
 */
function getBackupsList() {
    $backupDir = BASE_PATH . '/backups';
    
    if (!is_dir($backupDir)) {
        return [];
    }
    
    $files = glob($backupDir . '/backup-*.{sql,sql.gz}', GLOB_BRACE);
    $backups = [];
    
    foreach ($files as $file) {
        $basename = basename($file);
        if (preg_match('/backup-(\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2})\.sql(\.gz)?$/', $basename, $matches)) {
            $backups[] = [
                'filename' => $basename,
                'filepath' => $file,
                'size' => filesize($file),
                'created' => filemtime($file),
                'compressed' => strpos($basename, '.gz') !== false
            ];
        }
    }
    
    // Ordenar por fecha (más reciente primero)
    usort($backups, function($a, $b) {
        return $b['created'] - $a['created'];
    });
    
    return $backups;
}

/**
 * Eliminar backup antiguo
 * @param string $filename
 * @return bool
 */
function deleteBackup($filename) {
    $backupDir = BASE_PATH . '/backups';
    $filepath = $backupDir . '/' . basename($filename);
    
    if (file_exists($filepath) && strpos($filepath, $backupDir) === 0) {
        return unlink($filepath);
    }
    
    return false;
}

/**
 * Limpiar backups antiguos (mantener solo los últimos N)
 * @param int $keep Número de backups a mantener
 * @return int Número de backups eliminados
 */
function cleanOldBackups($keep = 10) {
    $backups = getBackupsList();
    
    if (count($backups) <= $keep) {
        return 0;
    }
    
    $toDelete = array_slice($backups, $keep);
    $deleted = 0;
    
    foreach ($toDelete as $backup) {
        if (deleteBackup($backup['filename'])) {
            $deleted++;
        }
    }
    
    return $deleted;
}

