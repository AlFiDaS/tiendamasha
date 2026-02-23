<?php
/**
 * Lista de imágenes de la galería - Vista idéntica a Galería de ideas con controles de edición
 * Incluye edición de nombre (navbar) y título de la galería (tabla gallery_info). La URL es siempre /galeria/
 */
$pageTitle = 'Galería de Ideas';
require_once '../../config.php';
require_once '../_inc/header.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/shop-settings.php';

$error = '';
$success = '';

// Cargar datos de gallery_info (nombre y título; slug siempre es galeria)
$galleryInfo = ['name' => 'Galeria de Ideas', 'title' => 'Galería de ideas'];
try {
    $row = fetchOne("SELECT name, title FROM gallery_info WHERE id = 1 LIMIT 1");
    if ($row) {
        $galleryInfo = $row;
    }
} catch (Throwable $e) {
    // Tabla gallery_info puede no existir aún
}
$currentTitle = $galleryInfo['title'];
$currentName = $galleryInfo['name'];

// Subtítulo fijo (no editable)
$subtitleFijo = 'Tocá una imagen para verla en grande. Usá flechas o deslizá para avanzar.';

// Procesar actualización de nombre y título (gallery_info) - solo para envío tradicional (ya no usado; se usa API)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_gallery_info'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $newName = trim(sanitize($_POST['galeria_name'] ?? ''));
        $newTitle = trim(sanitize($_POST['galeria_title'] ?? ''));
        if (empty($newName)) {
            $error = 'El nombre no puede estar vacío';
        } elseif (empty($newTitle)) {
            $error = 'El título no puede estar vacío';
        } else {
            try {
                $updated = executeQuery(
                    "UPDATE gallery_info SET name = :name, title = :title, slug = 'galeria' WHERE id = 1",
                    ['name' => $newName, 'title' => $newTitle]
                );
                if ($updated) {
                    $success = 'Datos de la galería actualizados correctamente';
                    $currentName = $newName;
                    $currentTitle = $newTitle;
                    $galleryInfo = ['name' => $currentName, 'title' => $currentTitle];
                } else {
                    $error = 'Error al actualizar. ¿Ejecutaste database-gallery-info.sql?';
                }
            } catch (Throwable $e) {
                $error = 'Error al guardar. Asegúrate de tener la tabla gallery_info (ejecuta database-gallery-info.sql).';
            }
        }
    }
}

// Obtener todas las imágenes (incluidas ocultas para el admin)
$sql = "SELECT * FROM galeria ORDER BY orden ASC, id ASC";
$items = fetchAll($sql, []);

// Logo de la tienda para el preview del navbar (mismo que "Logo actual" en tienda.php)
$settings = getShopSettings();
$logoUrl = '';
if (!empty($settings['shop_logo'])) {
    $logoPath = $settings['shop_logo'];
    $logoFullPath = str_replace(BASE_URL, '', $logoPath);
    $logoFullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($logoFullPath, '/');
    $logoTime = file_exists($logoFullPath) ? filemtime($logoFullPath) : time();
    $logoUrl = $logoPath . '?v=' . $logoTime;
}
?>

<div class="admin-content">
    <div class="galeria-admin-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-secondary" style="text-decoration: none;">← Volver al panel</a>
        <a href="add.php" class="btn btn-primary"><span style="color:white;font-weight:bold;">+</span> Agregar Imagen</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="galeria-form">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="update_gallery_info" value="1">
        <input type="hidden" name="galeria_name" value="<?= htmlspecialchars($currentName) ?>">
        <input type="hidden" name="galeria_title" value="<?= htmlspecialchars($currentTitle) ?>">

        <!-- Mini navbar: réplica del navbar de la web, solo link galería editable -->
        <div class="content-card galeria-admin-navbar-preview" style="margin-bottom: 1.5rem;">
            <h3 class="section-title">Nombre en el menú</h3>
            <p style="color: #666; margin-bottom: 1rem;">Así se verá en la barra de navegación.</p>
            <nav class="navbar-preview-fake">
                <div class="nav-container-fake">
                    <span class="logo-preview-wrap">
                    <?php if ($logoUrl): ?>
                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($settings['shop_name'] ?? 'Logo') ?>" class="logo-preview-img" onerror="this.style.display='none';this.nextElementSibling.style.display='inline';">
                        <span class="logo-fake logo-fallback" style="display:none"><?= htmlspecialchars($settings['shop_name'] ?? 'Logo') ?></span>
                    <?php else: ?>
                        <span class="logo-fake"><?= htmlspecialchars($settings['shop_name'] ?? 'Logo') ?></span>
                    <?php endif; ?>
                    </span>
                    <ul class="nav-links-fake">
                        <li><span class="nav-item-fake disabled">Inicio</span></li>
                        <li>
                            <div class="editable-text editable-nav-link" data-input="galeria_name">
                                <a href="/galeria" class="nav-item-fake active"><span class="text-display"><?= htmlspecialchars($currentName) ?></span></a>
                                <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                <span class="edit-inline"><input type="text" value="<?= htmlspecialchars($currentName) ?>" placeholder="Ej: Galeria de Ideas"><button type="button" class="btn-edit-ok" title="Guardar">✓</button><button type="button" class="btn-edit-cancel" title="Cancelar">✗</button></span>
                            </div>
                        </li>
                    </ul>
                    <span class="nav-icons-fake">❤️ 🛒</span>
                </div>
            </nav>
        </div>

        <!-- Vista idéntica a Galería de ideas con controles de edición -->
        <section class="galeria galeria-admin-preview">
            <div class="galeria-header-edit">
                <div class="editable-text editable-galeria-title" data-input="galeria_title">
                    <div class="galeria-title-row">
                        <h1 class="galeria-h1"><span class="text-display"><?= htmlspecialchars($currentTitle) ?></span></h1>
                        <button type="button" class="btn-edit-pencil" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                    </div>
                    <div class="edit-inline"><input type="text" value="<?= htmlspecialchars($currentTitle) ?>" placeholder="Ej: Fotos de Clientes"><button type="button" class="btn-edit-ok" title="Guardar">✓</button><button type="button" class="btn-edit-cancel" title="Cancelar">✗</button></div>
                </div>
                <p class="sub"><?= htmlspecialchars($subtitleFijo) ?></p>
            </div>

        <?php if (empty($items)): ?>
            <div style="text-align: center; padding: 3rem; color: #666;">
                <p>No hay imágenes en la galería.</p>
                <a href="add.php" class="btn btn-primary">Agregar primera imagen</a>
            </div>
        <?php else: ?>
            <div class="galeria-toolbar" style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
                <button type="button" id="btn-guardar-orden" class="btn btn-primary" style="display: none;">💾 Guardar orden</button>
            </div>
            <div id="galeria-grid" class="grid galeria-sortable-grid">
                <?php foreach ($items as $index => $item): ?>
                    <figure class="card galeria-sortable-card" 
                            draggable="true" 
                            data-id="<?= $item['id'] ?>"
                            data-orden="<?= $item['orden'] ?? 0 ?>"
                            data-index="<?= $index ?>">
                        <div class="card-image-wrap">
                            <div class="mobile-move-controls">
                                <button type="button" class="btn-move btn-move-up" data-id="<?= $item['id'] ?>" <?= $index === 0 ? 'disabled' : '' ?> title="Mover arriba">↑</button>
                                <button type="button" class="btn-move btn-move-down" data-id="<?= $item['id'] ?>" <?= $index === count($items) - 1 ? 'disabled' : '' ?> title="Mover abajo">↓</button>
                            </div>
                            <?php 
                            $imageUrl = preg_replace('/\?.*$/', '', $item['imagen']);
                            $fullImageUrl = BASE_URL . $imageUrl;
                            ?>
                            <img src="<?= $fullImageUrl ?>" 
                                 alt="<?= htmlspecialchars($item['alt'] ?? $item['nombre']) ?>" 
                                 class="thumb"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'250\'%3E%3Crect fill=\'%23f0f0f0\' width=\'200\' height=\'250\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-size=\'14\'%3ESin img%3C/text%3E%3C/svg%3E';">
                            <?php if (!$item['visible']): ?>
                                <span class="badge-oculta">Oculta</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-actions">
                            <a href="edit.php?id=<?= $item['id'] ?>" class="btn-edit-link">Editar</a>
                            <a href="delete.php?id=<?= $item['id'] ?>" class="btn-delete-link" onclick="return confirm('¿Eliminar esta imagen?')">Eliminar</a>
                        </div>
                    </figure>
                <?php endforeach; ?>
            </div>
            <div id="save-message" style="display: none; margin-top: 1rem; padding: 1rem; border-radius: 8px; text-align: center;"></div>
        <?php endif; ?>
    </section>
    </form>
</div>

<style>
/* Mini navbar preview: réplica del navbar real de la web */
.galeria-admin-navbar-preview .navbar-preview-fake {
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-radius: 8px;
    overflow: hidden;
}
.galeria-admin-navbar-preview .nav-container-fake {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.6rem 2rem;
}
.galeria-admin-navbar-preview .logo-fake,
.galeria-admin-navbar-preview .logo-fallback {
    font-weight: 700;
    font-size: 1.1rem;
    color: #333;
}
.galeria-admin-navbar-preview .logo-preview-wrap {
    display: flex;
    align-items: center;
}
.galeria-admin-navbar-preview .logo-preview-img {
    max-height: 40px;
    max-width: 120px;
    width: auto;
    height: auto;
    display: block;
    object-fit: contain;
}
.galeria-admin-navbar-preview .nav-links-fake {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 2rem;
}
.galeria-admin-navbar-preview .nav-item-fake {
    color: #333;
    text-decoration: none;
    font-weight: 500;
}
.galeria-admin-navbar-preview .nav-item-fake.disabled {
    color: #999;
    cursor: default;
}
.galeria-admin-navbar-preview .nav-item-fake.active {
    color: #333;
}
.galeria-admin-navbar-preview .nav-item-fake.active:hover {
    color: var(--primary-color);
}
.galeria-admin-navbar-preview .nav-icons-fake {
    font-size: 1.4rem;
    color: #333;
}
.galeria-admin-navbar-preview .editable-nav-link {
    position: relative;
    display: inline-block;
}
.galeria-admin-preview .editable-galeria-title {
    position: relative;
}
.galeria-admin-preview .galeria-title-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: nowrap;
    margin-bottom: 0.5rem;
}
.galeria-admin-preview .galeria-header-edit {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.galeria-admin-preview .editable-text {
    position: relative;
    display: inline-block;
}
.galeria-admin-navbar-preview .btn-edit-pencil,
.galeria-admin-preview .btn-edit-pencil {
    position: absolute;
    top: 2px;
    right: -28px;
    width: 28px;
    height: 28px;
    padding: 0;
    border: none;
    background: var(--primary-color);
    color: #fff;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    opacity: 0.9;
    transition: opacity 0.2s;
}
.galeria-admin-navbar-preview .btn-edit-pencil svg,
.galeria-admin-preview .btn-edit-pencil svg {
    width: 14px;
    height: 14px;
}
.galeria-admin-navbar-preview .btn-edit-pencil:hover,
.galeria-admin-preview .btn-edit-pencil:hover { opacity: 1; }
.galeria-admin-preview .editable-galeria-title .btn-edit-pencil {
    position: static;
    margin-left: 0;
    flex-shrink: 0;
}
.galeria-admin-navbar-preview .editable-text.edit-mode .text-display,
.galeria-admin-preview .editable-text.edit-mode .text-display { display: none; }
.galeria-admin-navbar-preview .editable-text.edit-mode .btn-edit-pencil,
.galeria-admin-preview .editable-text.edit-mode .btn-edit-pencil { display: none !important; }
.galeria-admin-navbar-preview .editable-text.edit-mode .edit-inline,
.galeria-admin-preview .editable-text.edit-mode .edit-inline { display: block; }
.galeria-admin-preview .editable-galeria-title.edit-mode .galeria-title-row { display: none; }
.galeria-admin-preview .editable-galeria-title.edit-mode .edit-inline { width: 100%; }
.galeria-admin-navbar-preview .edit-inline,
.galeria-admin-preview .edit-inline { display: none; margin-top: 4px; }
.galeria-admin-navbar-preview .edit-inline input,
.galeria-admin-navbar-preview .edit-inline input {
    padding: 6px 8px;
    border: 2px solid var(--primary-color);
    border-radius: 4px;
    font-size: inherit;
    min-width: 200px;
}
.galeria-admin-preview .edit-inline input {
    padding: 6px 8px;
    border: 2px solid var(--primary-color);
    border-radius: 4px;
    font-size: inherit;
    width: 100%;
    max-width: 400px;
}
.galeria-admin-navbar-preview .editable-text.edit-mode .edit-inline,
.galeria-admin-preview .editable-text.edit-mode .edit-inline {
    display: flex !important;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
}
.galeria-admin-navbar-preview .editable-text.edit-mode .edit-inline input,
.galeria-admin-preview .editable-text.edit-mode .edit-inline input {
    flex: 1;
    min-width: 120px;
}
.galeria-admin-navbar-preview .btn-edit-ok,
.galeria-admin-navbar-preview .btn-edit-cancel,
.galeria-admin-preview .btn-edit-ok,
.galeria-admin-preview .btn-edit-cancel {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    font-size: 1.1rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.galeria-admin-navbar-preview .btn-edit-ok,
.galeria-admin-preview .btn-edit-ok {
    background: #22c55e;
    color: white;
}
.galeria-admin-navbar-preview .btn-edit-ok:hover,
.galeria-admin-preview .btn-edit-ok:hover { background: #16a34a; }
.galeria-admin-navbar-preview .btn-edit-cancel,
.galeria-admin-preview .btn-edit-cancel {
    background: #dc3545;
    color: white;
}
.galeria-admin-navbar-preview .btn-edit-cancel:hover,
.galeria-admin-preview .btn-edit-cancel:hover { background: #c82333; }

/* Estilos idénticos a la Galería de ideas (public) */
.galeria-admin-preview.galeria {
    padding: 2rem 1rem;
}
.galeria-admin-preview .editable-galeria-title .galeria-h1 {
    margin: 0;
    flex: 0 1 auto;
    min-width: 0; /* permite que título y botón queden en línea en desktop */
}
.galeria-admin-preview .galeria-h1 {
    text-align: center;
    margin: 0;
    font-size: clamp(1.6rem, 2.8vw, 2.2rem);
    font-family: 'Playfair Display', Georgia, serif;
}
.galeria-admin-preview .sub {
    text-align: center;
    color: #666;
    margin-bottom: 1.25rem;
    font-size: 0.95rem;
}
.galeria-admin-preview .grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
    max-width: 1100px;
    margin: 0 auto;
}
@media (min-width: 480px) {
    .galeria-admin-preview .grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
}
@media (min-width: 900px) {
    .galeria-admin-preview.galeria { padding: 3rem 1rem; }
    .galeria-admin-preview .grid { gap: 1rem; }
}

.galeria-admin-preview .card {
    margin: 0;
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    background: #f4f4f4;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    cursor: move;
    transition: all 0.25s ease;
}
.galeria-admin-preview .card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.galeria-admin-preview .card-image-wrap {
    position: relative;
    overflow: hidden;
}
.galeria-admin-preview .thumb {
    width: 100%;
    height: 100%;
    aspect-ratio: 4/5;
    object-fit: cover;
    display: block;
    transition: transform 0.25s ease, filter 0.25s ease;
}
.galeria-admin-preview .card:hover .thumb {
    transform: scale(1.03);
    filter: brightness(0.98);
}

.galeria-admin-preview .badge-oculta {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
}

/* Botones Editar/Eliminar debajo de la card */
.galeria-admin-preview .card-actions {
    padding: 0.5rem;
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
}
.galeria-admin-preview .btn-edit-link,
.galeria-admin-preview .btn-delete-link {
    font-size: 0.85rem;
    text-decoration: none;
    padding: 0.25rem 0.5rem;
}
.galeria-admin-preview .btn-edit-link { color: var(--primary-color); }
.galeria-admin-preview .btn-delete-link { color: #dc3545; }

/* Drag and drop */
.galeria-admin-preview .galeria-sortable-card.dragging {
    opacity: 0.5;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}
.galeria-admin-preview .galeria-sortable-card.drag-over {
    border: 2px dashed var(--primary-color);
    background: #f8f9fa;
}

/* Controles móvil para ordenar */
.mobile-move-controls {
    display: none;
    position: absolute;
    top: 50%;
    right: 0.5rem;
    transform: translateY(-50%);
    z-index: 10;
    flex-direction: column;
    gap: 0.25rem;
}
.galeria-admin-preview .btn-move {
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
.galeria-admin-preview .btn-move:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
@media (max-width: 768px) {
    .galeria-admin-preview .card { cursor: default; }
    .mobile-move-controls { display: flex; }
}

/* Responsive: galería admin en mobile */
@media (max-width: 768px) {
    .galeria-admin-header {
        flex-direction: column;
        align-items: stretch !important;
    }
    .galeria-admin-header .btn { width: 100%; text-align: center; }
    .galeria-admin-navbar-preview {
        padding: 1rem;
        overflow-x: hidden;
    }
    .galeria-admin-navbar-preview .section-title {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }
    .galeria-admin-navbar-preview p {
        font-size: 0.9rem;
        margin-bottom: 0.75rem !important;
    }
    .galeria-admin-navbar-preview .nav-container-fake {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
    }
    .galeria-admin-navbar-preview .nav-links-fake {
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .galeria-admin-navbar-preview .logo-preview-wrap {
        justify-content: center;
    }
    .galeria-admin-navbar-preview .nav-icons-fake {
        font-size: 1.2rem;
        text-align: center;
    }
    .galeria-admin-navbar-preview .btn-edit-pencil,
    .galeria-admin-preview .btn-edit-pencil {
        position: static;
        margin-left: 0.5rem;
        vertical-align: middle;
    }
    .galeria-admin-navbar-preview .editable-text {
        display: inline-flex !important;
        flex-wrap: nowrap;
        align-items: center;
    }
    .galeria-admin-navbar-preview .edit-inline input,
    .galeria-admin-preview .edit-inline input {
        min-width: 0;
        width: 100%;
        max-width: 100%;
    }
    .galeria-admin-preview .galeria-toolbar {
        justify-content: stretch !important;
    }
    .galeria-admin-preview .galeria-toolbar .btn {
        width: 100%;
    }
    /* Guardar orden: fijo abajo centrado en mobile */
    #btn-guardar-orden {
        position: fixed;
        bottom: 1.5rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 900;
        width: auto;
        min-width: 200px;
        padding: 0.75rem 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    .galeria-admin-preview .galeria-toolbar {
        min-height: 0;
        margin-bottom: 0;
    }
    .galeria-admin-preview #galeria-grid {
        padding-bottom: 5rem;
    }
}
</style>

<script>
(function() {
    // Editar texto inline con OK (guardar) y X (cancelar)
    var editables = document.querySelectorAll('#galeria-form .editable-text');
    var form = document.getElementById('galeria-form');

    function cancelEditable(wrap) {
        var inputName = wrap.getAttribute('data-input');
        if (!form || !inputName) return;
        var hidden = form.querySelector('input[name="' + inputName + '"]');
        var display = wrap.querySelector('.text-display');
        var editBlock = wrap.querySelector('.edit-inline');
        var inputEl = editBlock && (editBlock.querySelector('input') || editBlock.querySelector('textarea'));
        if (!hidden || !display || !inputEl) return;
        inputEl.value = hidden.value;
        display.textContent = hidden.value;
        wrap.classList.remove('edit-mode');
    }

    document.addEventListener('click', function(e) {
        editables.forEach(function(wrap) {
            if (!wrap.classList.contains('edit-mode')) return;
            if (wrap.contains(e.target)) return;
            cancelEditable(wrap);
        });
    }, true);
    document.addEventListener('touchstart', function(e) {
        editables.forEach(function(wrap) {
            if (!wrap.classList.contains('edit-mode')) return;
            if (wrap.contains(e.target)) return;
            cancelEditable(wrap);
        });
    }, { passive: true, capture: true });

    editables.forEach(function(wrap) {
        var inputName = wrap.getAttribute('data-input');
        if (!inputName) return;
        var hidden = form.querySelector('input[name="' + inputName + '"]');
        var display = wrap.querySelector('.text-display');
        var pencil = wrap.querySelector('.btn-edit-pencil');
        var editBlock = wrap.querySelector('.edit-inline');
        var inputEl = editBlock && (editBlock.querySelector('input') || editBlock.querySelector('textarea'));
        var btnOk = editBlock && editBlock.querySelector('.btn-edit-ok');
        var btnCancel = editBlock && editBlock.querySelector('.btn-edit-cancel');
        if (!display || !pencil || !editBlock || !inputEl || !hidden) return;

        pencil.addEventListener('click', function(e) {
            e.stopPropagation();
            wrap.classList.add('edit-mode');
            inputEl.value = hidden.value;
            setTimeout(function() { inputEl.focus(); }, 10);
        });

        if (btnCancel) {
            btnCancel.addEventListener('click', function(e) {
                e.stopPropagation();
                cancelEditable(wrap);
            });
        }

        if (btnOk) {
            btnOk.addEventListener('click', function(e) {
                e.stopPropagation();
                var val = inputEl.value.trim();
                if (!val) { cancelEditable(wrap); return; }
                hidden.value = val;
                var formData = new FormData(form);
                var btn = this;
                btn.disabled = true;
                btn.textContent = '…';
                fetch('<?= ADMIN_URL ?>/api/galeria-save-info.php', { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var nameH = form.querySelector('input[name="galeria_name"]');
                            var titleH = form.querySelector('input[name="galeria_title"]');
                            if (nameH) nameH.value = data.name;
                            if (titleH) titleH.value = data.title;
                            var nameD = document.querySelector('.editable-nav-link .text-display');
                            var titleD = document.querySelector('.editable-galeria-title .text-display');
                            if (nameD) nameD.textContent = data.name;
                            if (titleD) titleD.textContent = data.title;
                            wrap.classList.remove('edit-mode');
                        } else {
                            alert(data.message || 'Error al guardar');
                        }
                    })
                    .catch(function() { alert('Error de conexión'); })
                    .finally(function() { btn.disabled = false; btn.textContent = '✓'; });
            });
        }

        inputEl.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && inputEl.tagName !== 'TEXTAREA') {
                e.preventDefault();
                if (btnOk) btnOk.click();
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                cancelEditable(wrap);
            }
        });
    });

    const grid = document.getElementById('galeria-grid');
    if (!grid) return;

    const saveBtn = document.getElementById('btn-guardar-orden');
    const saveMessage = document.getElementById('save-message');
    let draggedElement = null;
    let hasChanges = false;
    const isMobile = window.innerWidth <= 768;

    // Drag and drop (desktop)
    if (!isMobile) {
        const cards = grid.querySelectorAll('.galeria-sortable-card');
        const cardsArray = Array.from(cards);
        cards.forEach(card => {
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            card.addEventListener('dragover', handleDragOver);
            card.addEventListener('drop', handleDrop);
            card.addEventListener('dragenter', handleDragEnter);
            card.addEventListener('dragleave', handleDragLeave);
        });

        function handleDragStart(e) {
            draggedElement = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
        }
        function handleDragEnd(e) {
            this.classList.remove('dragging');
            cardsArray.forEach(c => c.classList.remove('drag-over'));
        }
        function handleDragOver(e) {
            if (e.preventDefault) e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            return false;
        }
        function handleDragEnter(e) {
            if (this !== draggedElement) this.classList.add('drag-over');
        }
        function handleDragLeave(e) {
            this.classList.remove('drag-over');
        }
        function handleDrop(e) {
            if (e.stopPropagation) e.stopPropagation();
            if (draggedElement !== this) {
                const all = Array.from(grid.querySelectorAll('.galeria-sortable-card'));
                const di = all.indexOf(draggedElement);
                const ti = all.indexOf(this);
                if (di < ti) {
                    grid.insertBefore(draggedElement, this.nextSibling);
                } else {
                    grid.insertBefore(draggedElement, this);
                }
                hasChanges = true;
                if (saveBtn) saveBtn.style.display = 'inline-block';
            }
            this.classList.remove('drag-over');
            return false;
        }
    }

    // Botones móvil para mover
    function moveCard(productId, direction) {
        const cards = Array.from(grid.querySelectorAll('.galeria-sortable-card'));
        const idx = cards.findIndex(c => c.getAttribute('data-id') === String(productId));
        if (idx < 0) return;
        const card = cards[idx];
        if (direction === 'up' && idx > 0) {
            grid.insertBefore(card, cards[idx - 1]);
        } else if (direction === 'down' && idx < cards.length - 1) {
            grid.insertBefore(card, cards[idx + 1].nextSibling);
        } else return;
        hasChanges = true;
        if (saveBtn) saveBtn.style.display = 'inline-block';
        updateMoveButtons();
    }
    function updateMoveButtons() {
        const cards = Array.from(grid.querySelectorAll('.galeria-sortable-card'));
        cards.forEach((card, i) => {
            const upBtn = card.querySelector('.btn-move-up');
            const downBtn = card.querySelector('.btn-move-down');
            if (upBtn) upBtn.disabled = i === 0;
            if (downBtn) downBtn.disabled = i === cards.length - 1;
        });
    }
    grid.querySelectorAll('.btn-move-up').forEach(btn => {
        btn.addEventListener('click', function() { moveCard(this.getAttribute('data-id'), 'up'); });
    });
    grid.querySelectorAll('.btn-move-down').forEach(btn => {
        btn.addEventListener('click', function() { moveCard(this.getAttribute('data-id'), 'down'); });
    });

    // Guardar orden
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const cards = grid.querySelectorAll('.galeria-sortable-card');
            const order = [];
            cards.forEach((card, index) => {
                order.push({
                    id: card.getAttribute('data-id'),
                    orden: index + 1
                });
            });
            saveBtn.disabled = true;
            saveBtn.textContent = '💾 Guardando...';
            fetch('<?= ADMIN_URL ?>/api/galeria-save-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order: order })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    hasChanges = false;
                    saveBtn.style.display = 'none';
                    if (saveMessage) {
                        saveMessage.style.display = 'block';
                        saveMessage.className = 'alert alert-success';
                        saveMessage.textContent = '✅ Orden guardado correctamente';
                        setTimeout(() => { saveMessage.style.display = 'none'; }, 3000);
                    }
                } else {
                    if (saveMessage) {
                        saveMessage.style.display = 'block';
                        saveMessage.className = 'alert alert-error';
                        saveMessage.textContent = '❌ ' + (data.error || 'Error al guardar');
                    }
                }
            })
            .catch(err => {
                if (saveMessage) {
                    saveMessage.style.display = 'block';
                    saveMessage.className = 'alert alert-error';
                    saveMessage.textContent = '❌ Error de conexión';
                }
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = '💾 Guardar orden';
            });
        });
    }

})();
</script>

<?php require_once '../_inc/footer.php'; ?>
