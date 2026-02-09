<?php
/**
 * Lista de imágenes de la galería - Vista idéntica a Galería de ideas con controles de edición
 */
$pageTitle = 'Galería de Ideas';
require_once '../../config.php';
require_once '../_inc/header.php';
require_once '../../helpers/auth.php';

$error = '';
$success = '';

// Obtener título de la galería (editable)
$landingSettings = fetchOne("SELECT galeria_title FROM landing_page_settings WHERE id = 1 LIMIT 1");
$currentTitle = $landingSettings['galeria_title'] ?? 'Galería de ideas';

// Subtítulo fijo (no editable)
$subtitleFijo = 'Tocá una imagen para verla en grande. Usá flechas o deslizá para avanzar.';

// Procesar actualización del título
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_title'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $newTitle = sanitize($_POST['galeria_title'] ?? '');
        if (!empty($newTitle)) {
            if (executeQuery("UPDATE landing_page_settings SET galeria_title = :title WHERE id = 1", ['title' => $newTitle])) {
                $success = 'Título actualizado correctamente';
                $currentTitle = $newTitle;
            } else {
                $error = 'Error al actualizar el título';
            }
        } else {
            $error = 'El título no puede estar vacío';
        }
    }
}

// Obtener todas las imágenes (incluidas ocultas para el admin)
$sql = "SELECT * FROM galeria ORDER BY orden ASC, id ASC";
$items = fetchAll($sql, []);
?>

<div class="admin-content">
    <div class="galeria-admin-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-secondary" style="text-decoration: none;">← Volver al panel</a>
        <a href="add.php" class="btn btn-primary">➕ Agregar Imagen</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Vista idéntica a Galería de ideas con controles de edición -->
    <section class="galeria galeria-admin-preview">
        <!-- Título editable; subtítulo fijo -->
        <div class="galeria-header-edit">
            <form method="POST" action="" id="form-titulo" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="update_title" value="1">
                <h1 class="galeria-h1">
                    <span class="editable-wrap">
                        <span class="text-display"><?= htmlspecialchars($currentTitle) ?></span>
                        <button type="button" class="btn-edit-pencil" title="Editar título">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        </button>
                        <span class="edit-inline" style="display: none;">
                            <input type="text" name="galeria_title" value="<?= htmlspecialchars($currentTitle) ?>" placeholder="Título">
                            <button type="submit" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">Guardar</button>
                        </span>
                    </span>
                </h1>
            </form>
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
                            <div class="edit-overlay" onmousedown="event.stopPropagation()" onclick="event.stopPropagation()">
                                <a href="edit.php?id=<?= $item['id'] ?>" class="btn-overlay btn-change-img">✏️ Editar</a>
                                <a href="delete.php?id=<?= $item['id'] ?>" 
                                   class="btn-overlay btn-eliminar" 
                                   onclick="return confirm('¿Eliminar esta imagen?')">🗑️ Eliminar</a>
                            </div>
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
</div>

<style>
/* Estilos idénticos a la Galería de ideas (public) */
.galeria-admin-preview.galeria {
    padding: 2rem 1rem;
}
.galeria-admin-preview .galeria-h1 {
    text-align: center;
    margin: 0 0 0.5rem;
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

/* Edit overlay (como landing-page) */
.galeria-admin-preview .edit-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.25s;
    z-index: 2;
}
.galeria-admin-preview .card:hover .edit-overlay {
    opacity: 1;
}
.galeria-admin-preview .btn-overlay {
    background: #fff;
    color: #333;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.9rem;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.galeria-admin-preview .btn-overlay:hover {
    background: #f0f0f0;
}
.galeria-admin-preview .btn-eliminar {
    background: #dc3545;
    color: #fff !important;
}
.galeria-admin-preview .btn-eliminar:hover {
    background: #c82333 !important;
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

/* Editable título */
.galeria-admin-preview .editable-wrap {
    position: relative;
    display: inline-block;
}
.galeria-admin-preview .btn-edit-pencil {
    position: absolute;
    top: 2px;
    right: -32px;
    width: 24px;
    height: 24px;
    padding: 0;
    border: none;
    background: var(--primary-color);
    color: #fff;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.galeria-admin-preview .btn-edit-pencil svg {
    width: 12px;
    height: 12px;
}
.galeria-admin-preview .edit-inline input {
    padding: 0.5rem;
    border: 2px solid var(--primary-color);
    border-radius: 4px;
    width: 100%;
    max-width: 400px;
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
</style>

<script>
(function() {
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

    // Editable título (pencil)
    const pencil = document.querySelector('.galeria-admin-preview .btn-edit-pencil');
    const textDisplay = document.querySelector('.galeria-admin-preview .text-display');
    const editInline = document.querySelector('.galeria-admin-preview .edit-inline');
    if (pencil && textDisplay && editInline) {
        pencil.addEventListener('click', function() {
            textDisplay.style.display = 'none';
            editInline.style.display = 'block';
            pencil.style.display = 'none';
        });
    }
})();
</script>

<?php require_once '../_inc/footer.php'; ?>
