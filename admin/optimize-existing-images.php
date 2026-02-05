<?php
/**
 * Optimizar imágenes existentes
 * Recorre imágenes de productos y galería, las redimensiona y comprime.
 * Actualiza rutas en BD si cambia la extensión (ej. .jpg → .webp).
 * Protegido con autenticación de admin. Incluye modo dry-run.
 */
$pageTitle = 'Optimizar imágenes existentes';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../helpers/auth.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
startSecureSession();
requireAuth();

$dryRun = isset($_GET['dry_run']) || (isset($_POST['confirm']) && empty($_POST['confirm']));
$run = ($_SERVER['REQUEST_METHOD'] === 'POST')
    && isset($_POST['confirm']) && $_POST['confirm'] === 'yes'
    && validateCSRFToken($_POST['csrf_token'] ?? '');
$message = '';
$stats = [
    'products_processed' => 0,
    'products_updated' => 0,
    'galeria_processed' => 0,
    'galeria_updated' => 0,
    'errors' => [],
    'saved_bytes' => 0,
];

$capabilities = canOptimizeImages();
if (!$capabilities['gd']) {
    $message = 'La extensión GD de PHP no está disponible. No se puede optimizar imágenes.';
    $run = false;
}

/**
 * Convierte ruta relativa de BD (/images/...) a ruta física
 */
function relativePathToFull($relativePath) {
    if (empty($relativePath)) {
        return null;
    }
    $relativePath = preg_replace('/\?.*$/', '', trim($relativePath));
    if (strpos($relativePath, '/images/') !== 0) {
        return null;
    }
    $subPath = substr($relativePath, 8); // quitar "/images/"
    return rtrim(IMAGES_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);
}

if ($run && $capabilities['gd']) {
    // ——— Productos (image, hoverImage) ———
    $products = fetchAll("SELECT id, slug, categoria, image, hoverImage FROM products", []);
    if ($products) {
        foreach ($products as $row) {
            foreach (['image' => 'image', 'hoverImage' => 'hoverImage'] as $col => $label) {
                $path = $row[$col] ?? '';
                if (empty($path)) {
                    continue;
                }
                $fullPath = relativePathToFull($path);
                if (!$fullPath || !file_exists($fullPath)) {
                    $stats['errors'][] = "Producto {$row['id']} ($label): archivo no encontrado: $path";
                    continue;
                }
                $beforeSize = filesize($fullPath);
                $optimized = optimizeImage($fullPath, OPTIMIZE_PRODUCT_MAX_DIM, OPTIMIZE_QUALITY);
                if (!$optimized['success']) {
                    $stats['errors'][] = "Producto {$row['id']} ($label): " . ($optimized['error'] ?? 'Error');
                    continue;
                }
                $stats['products_processed']++;
                $stats['saved_bytes'] += $optimized['savedBytes'] ?? 0;
                $newPath = $optimized['relativePath'];
                if ($newPath && $newPath !== $path) {
                    executeQuery(
                        "UPDATE products SET {$col} = :path WHERE id = :id",
                        ['path' => $newPath, 'id' => $row['id']]
                    );
                    $stats['products_updated']++;
                }
            }
        }
    }

    // ——— Galería (imagen) ———
    $galeria = fetchAll("SELECT id, nombre, imagen FROM galeria", []);
    if ($galeria) {
        foreach ($galeria as $row) {
            $path = $row['imagen'] ?? '';
            if (empty($path)) {
                continue;
            }
            $fullPath = relativePathToFull($path);
            if (!$fullPath || !file_exists($fullPath)) {
                $stats['errors'][] = "Galería id {$row['id']} ({$row['nombre']}): archivo no encontrado: $path";
                continue;
            }
            $optimized = optimizeImage($fullPath, OPTIMIZE_GALERIA_MAX_DIM, OPTIMIZE_QUALITY);
            if (!$optimized['success']) {
                $stats['errors'][] = "Galería id {$row['id']}: " . ($optimized['error'] ?? 'Error');
                continue;
            }
            $stats['galeria_processed']++;
            $stats['saved_bytes'] += $optimized['savedBytes'] ?? 0;
            $newPath = $optimized['relativePath'];
            if ($newPath && $newPath !== $path) {
                executeQuery(
                    "UPDATE galeria SET imagen = :path WHERE id = :id",
                    ['path' => $newPath, 'id' => $row['id']]
                );
                $stats['galeria_updated']++;
            }
        }
    }
}

require_once __DIR__ . '/_inc/header.php';
?>
<div class="admin-content">
    <h2>Optimizar imágenes existentes</h2>
    <p style="color: #666; margin-bottom: 1rem;">
        Redimensiona y comprime imágenes ya subidas (productos: <?= OPTIMIZE_PRODUCT_MAX_DIM ?>px, galería: <?= OPTIMIZE_GALERIA_MAX_DIM ?>px, calidad <?= OPTIMIZE_QUALITY ?>%).
        Se usa WebP si está disponible, si no JPEG.
    </p>
    <?php if (!$capabilities['gd']): ?>
        <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
    <?php else: ?>
        <p><strong>GD:</strong> disponible. <strong>WebP:</strong> <?= $capabilities['webp'] ? 'sí' : 'no (se usará JPEG)' ?>.</p>
        <p style="margin: 1rem 0;">Recomendación: haz un <strong>backup de la base de datos y de la carpeta de imágenes</strong> antes de ejecutar.</p>

        <?php if ($run): ?>
            <div class="alert alert-success">
                <strong>Proceso completado</strong><br>
                Productos: <?= $stats['products_processed'] ?> procesadas, <?= $stats['products_updated'] ?> rutas actualizadas en BD.<br>
                Galería: <?= $stats['galeria_processed'] ?> procesadas, <?= $stats['galeria_updated'] ?> rutas actualizadas en BD.<br>
                Espacio ahorrado: <?= number_format($stats['saved_bytes'] / 1024, 1) ?> KB (<?= number_format($stats['saved_bytes'] / 1024 / 1024, 2) ?> MB).<br>
                <?php if (!empty($stats['errors'])): ?>
                    <strong>Errores (<?= count($stats['errors']) ?>):</strong>
                    <ul style="margin-top: 0.5rem;">
                        <?php foreach (array_slice($stats['errors'], 0, 20) as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                        <?php if (count($stats['errors']) > 20): ?>
                            <li>... y <?= count($stats['errors']) - 20 ?> más.</li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" style="margin-top: 1rem;">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <p>
                <label>
                    <input type="checkbox" name="confirm" value="yes" required>
                    Confirmo que tengo (o no necesito) backup y quiero ejecutar la optimización.
                </label>
            </p>
            <button type="submit" class="btn btn-primary">Ejecutar optimización</button>
            <a href="index.php" class="btn btn-secondary">Volver al panel</a>
        </form>

        <p style="margin-top: 1.5rem; font-size: 0.9rem; color: #666;">
            <strong>Modo dry-run:</strong> añade <code>?dry_run=1</code> a la URL para ver esta página sin ejecutar cambios. 
            Al enviar el formulario sin marcar la casilla no se modifica nada.
        </p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/_inc/footer.php'; ?>
