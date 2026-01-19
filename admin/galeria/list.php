<?php
/**
 * Lista de imágenes de la galería
 */
$pageTitle = 'Galería de Ideas';
require_once '../../config.php';
require_once '../_inc/header.php';
require_once '../../helpers/auth.php';

$error = '';
$success = '';

// Obtener título actual de la galería
$landingSettings = fetchOne("SELECT galeria_title FROM landing_page_settings WHERE id = 1 LIMIT 1");
$currentTitle = $landingSettings['galeria_title'] ?? 'Galería de ideas';

// Procesar actualización del título
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_title'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $newTitle = sanitize($_POST['galeria_title'] ?? '');
        if (!empty($newTitle)) {
            $updateSql = "UPDATE landing_page_settings SET galeria_title = :title WHERE id = 1";
            if (executeQuery($updateSql, ['title' => $newTitle])) {
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

// Obtener todas las imágenes
$sql = "SELECT * FROM galeria ORDER BY orden ASC, id ASC";
$items = fetchAll($sql, []);
?>

<div class="admin-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h2>Galería de Ideas</h2>
        <a href="add.php" class="btn btn-primary">➕ Agregar Imagen</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <!-- Formulario para editar título -->
    <div style="background: #f9f9f9; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #ddd;">
        <h3 style="margin-bottom: 1rem; color: #333;">Configuración de la Galería</h3>
        <form method="POST" action="" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="update_title" value="1">
            <div style="flex: 1; min-width: 250px;">
                <label for="galeria_title" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Título de la Galería:</label>
                <input 
                    type="text" 
                    id="galeria_title" 
                    name="galeria_title" 
                    value="<?= htmlspecialchars($currentTitle) ?>" 
                    placeholder="Galería de ideas"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                    required>
                <small style="display: block; margin-top: 0.25rem; color: #666;">Este título aparecerá en la página pública de la galería</small>
            </div>
            <div>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">💾 Guardar Título</button>
            </div>
        </form>
    </div>
    
    <!-- Grid de imágenes -->
    <?php if (empty($items)): ?>
        <div style="text-align: center; padding: 3rem; color: #666;">
            <p>No hay imágenes en la galería.</p>
            <a href="add.php" class="btn btn-primary">Agregar primera imagen</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;" class="galeria-grid">
            <?php 
            $totalItems = count($items);
            foreach ($items as $index => $item): 
                $isFirst = $index === 0;
                $isLast = $index === $totalItems - 1;
            ?>
                <div class="galeria-item" data-id="<?= $item['id'] ?>" data-orden="<?= $item['orden'] ?? 0 ?>" style="background: #f8f9fa; border-radius: 8px; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative;">
                    <!-- Botones de orden -->
                    <div class="orden-controls">
                        <button class="btn-orden btn-orden-left <?= $isFirst ? 'disabled' : '' ?>" 
                                data-id="<?= $item['id'] ?>" 
                                data-direction="up"
                                title="Mover hacia atrás (←)"
                                <?= $isFirst ? 'disabled' : '' ?>>
                            <span class="desktop-only">←</span>
                            <span class="mobile-only">↑</span>
                        </button>
                        <button class="btn-orden btn-orden-right <?= $isLast ? 'disabled' : '' ?>" 
                                data-id="<?= $item['id'] ?>" 
                                data-direction="down"
                                title="Mover hacia adelante (→)"
                                <?= $isLast ? 'disabled' : '' ?>>
                            <span class="desktop-only">→</span>
                            <span class="mobile-only">↓</span>
                        </button>
                    </div>
                    
                    <div style="position: relative; margin-bottom: 1rem;">
                        <?php 
                        // Remover parámetros de cache busting para el admin
                        $imageUrl = preg_replace('/\?.*$/', '', $item['imagen']);
                        $fullImageUrl = BASE_URL . $imageUrl;
                        ?>
                        <img src="<?= $fullImageUrl ?>" 
                             alt="<?= htmlspecialchars($item['alt'] ?? $item['nombre']) ?>" 
                             style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px;"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23f0f0f0\' width=\'200\' height=\'200\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ESin img%3C/text%3E%3C/svg%3E';">
                        <?php if (!$item['visible']): ?>
                            <div style="position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.7); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                Oculta
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                    </div>
                    <?php if (!empty($item['alt'])): ?>
                        <div style="color: #666; font-size: 0.875rem; margin-bottom: 0.5rem;">
                            <?= htmlspecialchars($item['alt']) ?>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <a href="edit.php?id=<?= $item['id'] ?>" class="btn-edit" style="flex: 1; text-align: center;">Editar</a>
                        <a href="delete.php?id=<?= $item['id'] ?>" 
                           class="btn-delete" 
                           onclick="return confirm('¿Estás seguro de eliminar esta imagen?')"
                           style="flex: 1; text-align: center;">Eliminar</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 2rem; color: #666;">
            Total: <?= count($items) ?> imagen(es)
        </div>
    <?php endif; ?>
</div>

<style>
/* Controles de orden */
.orden-controls {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    display: flex;
    gap: 0.25rem;
    z-index: 10;
}

.btn-orden {
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid #e0a4ce;
    color: #e0a4ce;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 0;
}

.btn-orden:hover:not(.disabled) {
    background: #e0a4ce;
    color: white;
    transform: scale(1.1);
}

.btn-orden.disabled,
.btn-orden:disabled {
    opacity: 0.3;
    cursor: not-allowed;
    background: rgba(240, 240, 240, 0.8);
    border-color: #ccc;
    color: #999;
}

.btn-orden:active:not(.disabled) {
    transform: scale(0.95);
}

.mobile-only {
    display: none;
}

.desktop-only {
    display: inline;
}

/* Responsive: En mobile mostrar flechas arriba/abajo */
@media (max-width: 768px) {
    .mobile-only {
        display: inline;
    }
    
    .desktop-only {
        display: none;
    }
    
    .orden-controls {
        flex-direction: column;
        top: 0.5rem;
        left: 0.5rem;
    }
    
    .btn-orden {
        width: 28px;
        height: 28px;
        font-size: 1rem;
    }
}

/* Feedback visual al cambiar orden */
.galeria-item.updating {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<script>
(function() {
    const ordenButtons = document.querySelectorAll('.btn-orden');
    
    ordenButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (this.disabled || this.classList.contains('disabled')) {
                return;
            }
            
            const itemId = this.getAttribute('data-id');
            const direction = this.getAttribute('data-direction');
            const item = this.closest('.galeria-item');
            
            if (!itemId || !direction) {
                console.error('Faltan datos: itemId=', itemId, 'direction=', direction);
                alert('Error: Faltan datos necesarios');
                return;
            }
            
            // Feedback visual
            item.classList.add('updating');
            const allButtons = item.querySelectorAll('.btn-orden');
            allButtons.forEach(btn => btn.disabled = true);
            
            try {
                const response = await fetch('<?= ADMIN_URL ?>/api/galeria-change-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: parseInt(itemId, 10),
                        direction: direction
                    })
                });
                
                const data = await response.json();
                
                console.log('Respuesta del servidor:', data);
                
                if (data.success) {
                    // Recargar la página para mostrar el nuevo orden
                    window.location.reload();
                } else {
                    alert('Error al cambiar el orden: ' + (data.error || data.message || 'Error desconocido'));
                    item.classList.remove('updating');
                    allButtons.forEach(btn => btn.disabled = false);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión al cambiar el orden. Intenta nuevamente.');
                item.classList.remove('updating');
                allButtons.forEach(btn => btn.disabled = false);
            }
        });
    });
})();
</script>

<?php require_once '../_inc/footer.php'; ?>

