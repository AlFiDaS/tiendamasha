<?php
/**
 * Backups de Base de Datos - Solo Super Admin
 * Hace backup de la BD de la plataforma (stores, usuarios, etc.)
 */
define('TIENDI_PLATFORM', true);
define('LUME_ADMIN', true);
define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/platform-config.php';
requireSuperAdmin();

$pageTitle = 'Backups';
$_saCurrentPage = 'backup';

require_once BASE_PATH . '/helpers/backup.php';

$platformCreds = [
    'host' => PLATFORM_DB_HOST,
    'name' => PLATFORM_DB_NAME,
    'user' => PLATFORM_DB_USER,
    'pass' => PLATFORM_DB_PASS,
];
$backupDir = BASE_PATH . '/backups';

$success = '';
$error = '';

if (isset($_POST['create_backup']) && isset($_POST['csrf_token'])) {
    if (!platformValidateCSRF($_POST['csrf_token'])) {
        die('Token CSRF inválido');
    }
    $compress = isset($_POST['compress']) && $_POST['compress'] === '1';
    $useExec = function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')));
    $backup = false;
    if ($useExec) {
        $backup = createDatabaseBackup($compress, $platformCreds);
    }
    if (!$backup) {
        $backup = createDatabaseBackupPDO($compress, $platformCreds);
    }
    if ($backup) {
        $success = 'Backup creado: ' . basename($backup);
    } else {
        $error = 'Error al crear el backup. Revisá los logs.';
    }
}

if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    if (!platformValidateCSRF($_GET['csrf_token'])) {
        die('Token CSRF inválido');
    }
    if (deleteBackup($_GET['delete'], $backupDir)) {
        $success = 'Backup eliminado';
    } else {
        $error = 'Error al eliminar';
    }
}

if (isset($_POST['clean_backups']) && isset($_POST['csrf_token'])) {
    if (!platformValidateCSRF($_POST['csrf_token'])) {
        die('Token CSRF inválido');
    }
    $keep = (int)($_POST['keep'] ?? 10);
    $deleted = cleanOldBackups($keep);
    $success = "Se eliminaron $deleted backup(s) antiguo(s)";
}

$backups = getBackupsList($backupDir);

require_once __DIR__ . '/_inc/header.php';
?>

<div class="sa-content">
    <h1><?= icon('save', 24) ?> Backups de Base de Datos</h1>
    <p style="color:#666; margin-bottom:1.5rem;">Backup de la base de datos de la plataforma (stores, usuarios). Solo Super Admin.</p>

    <?php if ($success): ?>
        <div class="sa-alert sa-alert-success"><?= icon('check', 18) ?> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="sa-alert sa-alert-error"><?= icon('x', 18) ?> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="sa-card" style="margin-bottom:1.5rem;">
        <h3>Crear Backup</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(platformCSRFToken()) ?>">
            <label><input type="checkbox" name="compress" value="1" checked> Comprimir (recomendado)</label>
            <button type="submit" name="create_backup" class="sa-btn sa-btn-primary">Crear Backup Ahora</button>
        </form>
    </div>

    <div class="sa-card" style="margin-bottom:1.5rem;">
        <h3>Limpiar Backups Antiguos</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(platformCSRFToken()) ?>">
            Mantener los últimos <input type="number" name="keep" value="10" min="1" max="100" style="width:4rem;"> backups
            <button type="submit" name="clean_backups" class="sa-btn sa-btn-secondary">Limpiar</button>
        </form>
    </div>

    <div class="sa-card">
        <h3>Backups Guardados (<?= count($backups) ?>)</h3>
        <?php if (empty($backups)): ?>
            <p>No hay backups. Creá uno ahora.</p>
        <?php else: ?>
            <table class="sa-table">
                <thead>
                    <tr><th>Archivo</th><th>Tamaño</th><th>Fecha</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $b): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($b['filename']) ?></strong><?= $b['compressed'] ? ' <small>(.gz)</small>' : '' ?></td>
                        <td><?= number_format($b['size'] / 1024, 2) ?> KB</td>
                        <td><?= date('d/m/Y H:i', $b['created']) ?></td>
                        <td>
                            <a href="<?= PLATFORM_PAGES_URL ?>/superadmin/backup-download.php?file=<?= urlencode($b['filename']) ?>" class="sa-btn sa-btn-sm sa-btn-primary"><?= icon('download', 16) ?> Descargar</a>
                            <a href="?delete=<?= urlencode($b['filename']) ?>&csrf_token=<?= urlencode(platformCSRFToken()) ?>" class="sa-btn sa-btn-sm" onclick="return confirm('¿Eliminar este backup?')"><?= icon('trash', 16) ?> Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
