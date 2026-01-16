<?php
/**
 * ============================================
 * CRON JOB: Backup Automático de BD
 * ============================================
 * Este script debe ejecutarse diariamente
 * Crea un backup automático de la base de datos
 * ============================================
 * 
 * Configurar en crontab:
 * 0 3 * * * /usr/bin/php /ruta/al/proyecto/cron/backup-database.php
 * 
 * O en Windows Task Scheduler:
 * - Programa: php.exe
 * - Argumentos: C:\ruta\al\proyecto\cron\backup-database.php
 * - Frecuencia: Diario a las 3:00 AM
 */

// Definir LUME_ADMIN para cargar helpers
define('LUME_ADMIN', true);

// Cargar configuración
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/backup.php';

// Crear backup comprimido
$backup = createDatabaseBackup(true);

// Si falla con mysqldump, intentar con PDO
if (!$backup) {
    $backup = createDatabaseBackupPDO(true);
}

if ($backup) {
    echo "✅ Backup creado exitosamente: " . basename($backup) . "\n";
    
    // Limpiar backups antiguos (mantener solo los últimos 30)
    $deleted = cleanOldBackups(30);
    if ($deleted > 0) {
        echo "🗑️ Se eliminaron $deleted backup(s) antiguo(s)\n";
    }
} else {
    echo "❌ Error al crear el backup\n";
    exit(1);
}

