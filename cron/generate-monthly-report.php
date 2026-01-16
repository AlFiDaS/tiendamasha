<?php
/**
 * ============================================
 * CRON JOB: Generar Reporte Mensual
 * ============================================
 * Este script debe ejecutarse diariamente
 * Genera automáticamente el reporte del mes anterior
 * el último día de cada mes
 * ============================================
 * 
 * Configurar en crontab:
 * 0 2 * * * /usr/bin/php /ruta/al/proyecto/cron/generate-monthly-report.php
 * 
 * O en Windows Task Scheduler:
 * - Programa: php.exe
 * - Argumentos: C:\ruta\al\proyecto\cron\generate-monthly-report.php
 * - Frecuencia: Diario a las 2:00 AM
 */

// Definir LUME_ADMIN para cargar helpers
define('LUME_ADMIN', true);

// Cargar configuración
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/reports.php';

// Ejecutar verificación y generación
$result = checkAndGenerateMonthlyReport();

if ($result) {
    echo "✅ Reporte mensual generado exitosamente\n";
} else {
    echo "ℹ️ No es el último día del mes o ya se generó el reporte\n";
}

