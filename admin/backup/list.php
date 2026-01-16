<?php
/**
 * Gestión de Backups
 */
$pageTitle = 'Backups de Base de Datos';
require_once '../../config.php';
require_once '../../helpers/backup.php';
require_once '../../helpers/auth.php';

requireAuth();

// Crear backup manualmente
if (isset($_POST['create_backup']) && isset($_POST['csrf_token'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF inválido');
    }
    
    $compress = isset($_POST['compress']) && $_POST['compress'] === '1';
    
    // Verificar si exec() está disponible
    $useExec = function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')));
    
    // Intentar con mysqldump solo si exec() está disponible
    $backup = false;
    if ($useExec) {
        $backup = createDatabaseBackup($compress);
    }
    
    // Si falla o exec() no está disponible, usar método PDO
    if (!$backup) {
        $backup = createDatabaseBackupPDO($compress);
    }
    
    if ($backup) {
        $success = "Backup creado exitosamente: " . basename($backup);
    } else {
        $error = "Error al crear el backup. Verifica los logs del servidor.";
    }
}

// Eliminar backup
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    if (!validateCSRFToken($_GET['csrf_token'])) {
        die('Token CSRF inválido');
    }
    
    if (deleteBackup($_GET['delete'])) {
        $success = "Backup eliminado exitosamente";
    } else {
        $error = "Error al eliminar el backup";
    }
}

// Limpiar backups antiguos
if (isset($_POST['clean_backups']) && isset($_POST['csrf_token'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF inválido');
    }
    
    $keep = (int)($_POST['keep'] ?? 10);
    $deleted = cleanOldBackups($keep);
    $success = "Se eliminaron $deleted backup(s) antiguo(s)";
}

$backups = getBackupsList();

require_once '../_inc/header.php';
?>

<div class="admin-content">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-secondary">← Volver al Dashboard</a>
    </div>
    <div style="margin-bottom: 2rem;">
        <h2>💾 Backups de Base de Datos</h2>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Crear backup -->
    <div class="card" style="margin-bottom: 2rem;">
        <h3>Crear Backup</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="compress" value="1" checked>
                    Comprimir backup (recomendado)
                </label>
                <button type="submit" name="create_backup" class="btn btn-primary">Crear Backup Ahora</button>
            </div>
        </form>
    </div>
    
    <!-- Limpiar backups antiguos -->
    <div class="card" style="margin-bottom: 2rem;">
        <h3>Limpiar Backups Antiguos</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <label>
                    Mantener los últimos:
                    <input type="number" name="keep" value="10" min="1" max="50" style="width: 80px;">
                </label>
                <button type="submit" name="clean_backups" class="btn btn-secondary">Limpiar</button>
            </div>
        </form>
    </div>
    
    <!-- Lista de backups -->
    <div class="card">
        <h3>Backups Guardados (<?= count($backups) ?>)</h3>
        <?php if (empty($backups)): ?>
            <p style="color: #666; padding: 2rem; text-align: center;">
                No hay backups guardados. Crea tu primer backup ahora.
            </p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Tamaño</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td data-label="Archivo">
                                <strong><?= htmlspecialchars($backup['filename']) ?></strong>
                                <?php if ($backup['compressed']): ?>
                                    <span style="color: #28a745; font-size: 0.85rem; display: block; margin-top: 0.25rem;">(comprimido)</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Tamaño"><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                            <td data-label="Fecha"><?= date('d/m/Y H:i:s', $backup['created']) ?></td>
                            <td data-label="Acciones">
                                <a href="<?= ADMIN_URL ?>/backup/download.php?file=<?= urlencode($backup['filename']) ?>" class="btn btn-small btn-primary">📥 Descargar</a>
                                <a href="?delete=<?= urlencode($backup['filename']) ?>&csrf_token=<?= urlencode(generateCSRFToken()) ?>" 
                                   class="btn btn-small btn-danger" 
                                   onclick="return confirm('¿Estás seguro de eliminar este backup?')">🗑️ Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
    .card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .card h3 {
        margin-top: 0;
        margin-bottom: 1rem;
        color: #333;
        font-size: 1.25rem;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .data-table thead {
        background: #f8f9fa;
    }
    
    .data-table th {
        padding: 0.75rem;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #dee2e6;
    }
    
    .data-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .data-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .btn-small {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        margin-right: 0.5rem;
        display: inline-block;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background: #e0a4ce;
        color: white;
    }
    
    .btn-primary:hover {
        background: #d89bc0;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c82333;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
    
    /* Responsive para mobile */
    @media (max-width: 768px) {
        .admin-content {
            padding: 1rem !important;
        }
        
        .card {
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .card h3 {
            font-size: 1.1rem;
        }
        
        /* Formularios responsive */
        .card form > div {
            flex-direction: column;
            align-items: stretch !important;
            gap: 1rem !important;
        }
        
        .card form label {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .card form input[type="number"] {
            width: 100% !important;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .card form button,
        .card form .btn {
            width: 100%;
            padding: 0.875rem 1.5rem;
            text-align: center;
        }
        
        /* Tabla responsive - convertir a cards */
        .data-table {
            display: block;
            width: 100%;
        }
        
        .data-table thead {
            display: none;
        }
        
        .data-table tbody {
            display: block;
        }
        
        .data-table tbody tr {
            display: block;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            margin-bottom: 1rem;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .data-table tbody td {
            display: block;
            padding: 0.75rem 0;
            border: none;
            text-align: left;
        }
        
        .data-table tbody td:before {
            content: attr(data-label) ": ";
            font-weight: 700;
            color: #666;
            display: inline-block;
            min-width: 80px;
            margin-right: 0.5rem;
        }
        
        .data-table tbody td[data-label="Archivo"] {
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 0.5rem;
        }
        
        .data-table tbody td[data-label="Archivo"]:before {
            display: none;
        }
        
        .data-table tbody td[data-label="Archivo"] strong {
            font-size: 1.1rem;
            color: #333;
            display: block;
            margin-bottom: 0.5rem;
            word-break: break-word;
        }
        
        .data-table tbody td[data-label="Acciones"] {
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 2px solid #f0f0f0;
        }
        
        .data-table tbody td[data-label="Acciones"]:before {
            display: none;
        }
        
        .data-table tbody td[data-label="Acciones"] .btn-small {
            width: 100%;
            margin-right: 0;
            margin-bottom: 0.5rem;
            text-align: center;
            display: block;
        }
        
        .data-table tbody td[data-label="Acciones"] .btn-small:last-child {
            margin-bottom: 0;
        }
    }
    
    @media (max-width: 480px) {
        .admin-content {
            padding: 0.75rem !important;
        }
        
        .card {
            padding: 0.875rem;
        }
        
        .data-table tbody tr {
            padding: 1rem;
        }
        
        .data-table tbody td[data-label="Archivo"] strong {
            font-size: 1rem;
        }
    }
</style>

<?php require_once '../_inc/footer.php'; ?>

