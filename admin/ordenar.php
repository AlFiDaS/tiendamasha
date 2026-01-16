<?php
/**
 * Página para reordenar productos con drag and drop
 */
$pageTitle = 'Ordenar Productos';
require_once '../config.php';
require_once '../helpers/categories.php';
require_once '_inc/header.php';

// Obtener categoría del filtro
$categoriaFiltro = $_GET['categoria'] ?? 'productos';

// Validar categoría - debe existir en la base de datos
$categoriaObj = getCategoryBySlug($categoriaFiltro);
if (!$categoriaObj) {
    // Si no existe, usar la primera categoría disponible
    $categorias = getAllCategories(true);
    if (!empty($categorias)) {
        $categoriaFiltro = $categorias[0]['slug'];
    } else {
        $categoriaFiltro = 'productos'; // Fallback
    }
}

// Verificar si la columna orden existe
$hasOrdenColumn = false;
try {
    $checkOrden = fetchOne("SHOW COLUMNS FROM products LIKE 'orden'");
    $hasOrdenColumn = !empty($checkOrden);
} catch (Exception $e) {
    // Si hay error, asumir que no existe
    $hasOrdenColumn = false;
}

// Obtener productos visibles de la categoría seleccionada, ordenados igual que en la página pública
$sql = "SELECT id, name, image, slug, categoria";
if ($hasOrdenColumn) {
    $sql .= ", orden";
}
$sql .= " FROM products 
        WHERE visible = 1 AND categoria = :categoria";

if ($hasOrdenColumn) {
    $sql .= " ORDER BY 
            CASE WHEN orden IS NULL THEN 1 ELSE 0 END,
            orden ASC,
            destacado DESC,
            name ASC";
} else {
    $sql .= " ORDER BY destacado DESC, name ASC";
}

$products = fetchAll($sql, ['categoria' => $categoriaFiltro]);

// Asegurar que siempre sea un array
if (!is_array($products)) {
    $products = [];
}

// Debug: mostrar cantidad de productos encontrados
$debugCount = count($products);

// Asignar orden si no existe
foreach ($products as $index => $product) {
    if (!isset($product['orden']) || $product['orden'] === null || $product['orden'] === '') {
        $products[$index]['orden'] = $index + 1;
    }
}
?>

<div class="admin-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h2>Ordenar Productos</h2>
        <div>
            <button type="button" id="btn-guardar-orden" class="btn btn-primary" style="display: none;">
                💾 Guardar Orden
            </button>
            <a href="list.php" class="btn btn-secondary">← Volver a Lista</a>
        </div>
    </div>
    
    <!-- Filtros por categoría - Pestañas tipo navegador -->
    <div class="tabs-container">
        <div class="tabs-wrapper">
            <?php 
            // Mostrar TODAS las categorías para ordenar (incluso ocultas, para poder ordenar productos)
            $categorias = getAllCategories(false);
            foreach ($categorias as $cat): 
            ?>
                <a href="?categoria=<?= htmlspecialchars($cat['slug']) ?>" 
                   class="tab <?= $categoriaFiltro === $cat['slug'] ? 'active' : '' ?>">
                    <?= strtoupper(htmlspecialchars($cat['name'])) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="tabs-content">
            <p style="margin: 0; color: #666; font-size: 0.9rem; padding: 1rem 0;">
                <strong>Instrucciones:</strong> Arrastra y suelta las imágenes de los productos para cambiar su orden. 
                Los productos se mostrarán en filas de 4 columnas. Haz clic en "Guardar Orden" cuando termines.
            </p>
        </div>
    </div>
    
    <?php if (empty($products)): ?>
        <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
            <p style="margin: 0; color: #856404;">
                <strong>⚠️ No se encontraron productos</strong><br>
                <small>Categoría: <?= strtoupper($categoriaFiltro) ?> | Visible: 1</small>
            </p>
            <p style="margin: 0.5rem 0 0 0; color: #856404; font-size: 0.9rem;">
                Verifica que haya productos de esta categoría marcados como visibles en la base de datos.
            </p>
            <?php 
            // Debug: contar productos totales de esta categoría
            $totalCategoria = fetchOne("SELECT COUNT(*) as count FROM products WHERE categoria = :categoria", ['categoria' => $categoriaFiltro]);
            $totalVisible = fetchOne("SELECT COUNT(*) as count FROM products WHERE categoria = :categoria AND visible = 1", ['categoria' => $categoriaFiltro]);
            ?>
            <p style="margin: 0.5rem 0 0 0; color: #856404; font-size: 0.85rem;">
                <small>
                    Total en categoría: <?= $totalCategoria['count'] ?? 0 ?> | 
                    Visibles: <?= $totalVisible['count'] ?? 0 ?>
                </small>
            </p>
        </div>
    <?php else: ?>
        <div style="background: #d4edda; border: 1px solid #28a745; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
            <small style="color: #155724;">
                Mostrando <?= count($products) ?> producto(s) de <?= strtoupper($categoriaFiltro) ?>
            </small>
        </div>
    <?php endif; ?>
    
    <div id="products-grid" class="products-sortable-grid">
        <?php foreach ($products as $index => $product): ?>
            <div class="product-sortable-card" 
                 draggable="true" 
                 data-id="<?= htmlspecialchars($product['id']) ?>"
                 data-orden="<?= htmlspecialchars($product['orden'] ?? '') ?>"
                 data-index="<?= $index ?>">
                <?php if (!empty($product['image'])): ?>
                    <?php 
                    $imageUrl = preg_replace('/\?.*$/', '', $product['image']);
                    $fullImageUrl = BASE_URL . $imageUrl;
                    ?>
                    <div style="position: relative;">
                        <img src="<?= $fullImageUrl ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23f0f0f0\' width=\'200\' height=\'200\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-size=\'14\'%3ESin img%3C/text%3E%3C/svg%3E';">
                        <!-- Botones móvil: solo visibles en pantallas pequeñas, dentro del contenedor de imagen -->
                        <div class="mobile-controls">
                            <button type="button" class="btn-move btn-move-up" 
                                    data-id="<?= htmlspecialchars($product['id']) ?>"
                                    <?= $index === 0 ? 'disabled' : '' ?>
                                    title="Mover arriba">
                                ↑
                            </button>
                            <button type="button" class="btn-move btn-move-down" 
                                    data-id="<?= htmlspecialchars($product['id']) ?>"
                                    <?= $index === count($products) - 1 ? 'disabled' : '' ?>
                                    title="Mover abajo">
                                ↓
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="product-placeholder" style="position: relative;">
                        Sin imagen
                        <!-- Botones móvil también para productos sin imagen -->
                        <div class="mobile-controls">
                            <button type="button" class="btn-move btn-move-up" 
                                    data-id="<?= htmlspecialchars($product['id']) ?>"
                                    <?= $index === 0 ? 'disabled' : '' ?>
                                    title="Mover arriba">
                                ↑
                            </button>
                            <button type="button" class="btn-move btn-move-down" 
                                    data-id="<?= htmlspecialchars($product['id']) ?>"
                                    <?= $index === count($products) - 1 ? 'disabled' : '' ?>
                                    title="Mover abajo">
                                ↓
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="product-sortable-name">
                    <?= htmlspecialchars($product['name']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div id="save-message" style="display: none; margin-top: 1rem; padding: 1rem; border-radius: 8px; text-align: center;"></div>
</div>

<style>
/* Pestañas tipo navegador */
.tabs-container {
    background: white;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tabs-wrapper {
    display: flex;
    background: #f0f0f0;
    border-bottom: 1px solid #ddd;
    padding: 0;
    margin: 0;
    gap: 0;
}

.tab {
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    font-weight: 600;
    color: #666;
    background: #f0f0f0;
    border: none;
    border-top: 3px solid transparent;
    border-right: 1px solid #ddd;
    transition: all 0.2s ease;
    position: relative;
    display: inline-block;
    cursor: pointer;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tab:last-child {
    border-right: none;
}

.tab:hover {
    background: #e8e8e8;
    color: #333;
}

.tab.active {
    background: white;
    color: #e0a4ce;
    border-top-color: #e0a4ce;
    border-bottom: 1px solid white;
    margin-bottom: -1px;
    z-index: 1;
}

.tab.active:hover {
    background: white;
    color: #d89bc0;
}

.tabs-content {
    padding: 0 1.5rem;
    background: white;
}

/* Responsive para pestañas */
@media (max-width: 768px) {
    .tabs-wrapper {
        flex-wrap: wrap;
    }
    
    .tab {
        flex: 1;
        min-width: 0;
        padding: 0.6rem 0.75rem;
        font-size: 0.8rem;
        text-align: center;
        border-right: 1px solid #ddd;
    }
    
    .tab:last-child {
        border-right: 1px solid #ddd;
    }
    
    .tabs-content {
        padding: 0 1rem;
    }
}

.products-sortable-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-top: 1rem;
}

.product-sortable-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 1rem;
    cursor: move;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

/* Controles móvil - ocultos en desktop */
.mobile-controls {
    display: none;
    position: absolute;
    top: 50%;
    right: 0.5rem;
    transform: translateY(-50%);
    z-index: 10;
    gap: 0.25rem;
    flex-direction: column;
}

.btn-move {
    background: rgba(224, 164, 206, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    font-size: 1.2rem;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: all 0.2s;
    padding: 0;
    line-height: 1;
}

.btn-move:hover:not(:disabled) {
    background: rgba(216, 155, 192, 1);
    transform: scale(1.1);
    box-shadow: 0 3px 8px rgba(0,0,0,0.3);
}

.btn-move:active:not(:disabled) {
    transform: scale(0.95);
}

.btn-move:disabled {
    background: rgba(200, 200, 200, 0.5);
    color: rgba(255, 255, 255, 0.5);
    cursor: not-allowed;
    box-shadow: none;
}

.product-sortable-card:hover {
    border-color: #e0a4ce;
    box-shadow: 0 4px 12px rgba(224, 164, 206, 0.2);
    transform: translateY(-2px);
}

.product-sortable-card.dragging {
    opacity: 0.5;
    border-color: #e0a4ce;
    box-shadow: 0 8px 16px rgba(224, 164, 206, 0.4);
}

.product-sortable-card.drag-over {
    border-color: #e0a4ce;
    background: #f8f0f5;
}

.product-sortable-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    display: block;
    margin-bottom: 0.75rem;
}

.product-placeholder {
    width: 100%;
    height: 200px;
    background: #f0f0f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    margin-bottom: 0.75rem;
}

.product-sortable-name {
    font-weight: 600;
    color: #333;
    text-align: center;
    font-size: 0.9rem;
    line-height: 1.3;
    min-height: 2.6em;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsive */
@media (max-width: 1200px) {
    .products-sortable-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .products-sortable-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .product-sortable-card {
        padding: 0.75rem;
        cursor: default; /* Desactivar cursor move en mobile */
    }
    
    .product-sortable-card img,
    .product-placeholder {
        height: 150px;
    }
    
    /* Mostrar controles móvil */
    .mobile-controls {
        display: flex;
        flex-direction: column;
        top: 50%;
        right: 0.5rem;
        transform: translateY(-50%);
    }
    
    /* Hacer las cards no arrastrables en mobile (el drag and drop no funciona bien) */
    .product-sortable-card {
        -webkit-user-drag: none;
        user-select: none;
    }
}

@media (max-width: 480px) {
    .products-sortable-grid {
        grid-template-columns: 1fr;
    }
    
    .mobile-controls {
        display: flex;
        flex-direction: column; /* Mantener en columna también en móvil pequeño */
        top: 50%;
        right: 0.5rem;
        transform: translateY(-50%);
    }
    
    .btn-move {
        width: 36px;
        height: 36px;
        font-size: 1.3rem;
    }
}
</style>

<script>
(function() {
    const grid = document.getElementById('products-grid');
    const saveBtn = document.getElementById('btn-guardar-orden');
    const saveMessage = document.getElementById('save-message');
    let draggedElement = null;
    let hasChanges = false;
    
    // Detectar si es mobile
    const isMobile = window.innerWidth <= 768;
    
    // Hacer todas las cards arrastrables (solo en desktop)
    if (!isMobile) {
        const cards = grid.querySelectorAll('.product-sortable-card');
        cards.forEach(card => {
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            card.addEventListener('dragover', handleDragOver);
            card.addEventListener('drop', handleDrop);
            card.addEventListener('dragenter', handleDragEnter);
            card.addEventListener('dragleave', handleDragLeave);
        });
    }
    
    // Funciones para mover productos con botones (mobile)
    function moveProductUp(productId) {
        const cards = Array.from(grid.querySelectorAll('.product-sortable-card'));
        const currentIndex = cards.findIndex(card => card.getAttribute('data-id') === productId);
        
        if (currentIndex > 0) {
            const currentCard = cards[currentIndex];
            const previousCard = cards[currentIndex - 1];
            
            grid.insertBefore(currentCard, previousCard);
            updateMoveButtons();
            hasChanges = true;
            saveBtn.style.display = 'inline-block';
        }
    }
    
    function moveProductDown(productId) {
        const cards = Array.from(grid.querySelectorAll('.product-sortable-card'));
        const currentIndex = cards.findIndex(card => card.getAttribute('data-id') === productId);
        
        if (currentIndex < cards.length - 1) {
            const currentCard = cards[currentIndex];
            const nextCard = cards[currentIndex + 1];
            
            grid.insertBefore(currentCard, nextCard.nextSibling);
            updateMoveButtons();
            hasChanges = true;
            saveBtn.style.display = 'inline-block';
        }
    }
    
    function updateMoveButtons() {
        const cards = Array.from(grid.querySelectorAll('.product-sortable-card'));
        cards.forEach((card, index) => {
            const upBtn = card.querySelector('.btn-move-up');
            const downBtn = card.querySelector('.btn-move-down');
            
            if (upBtn) {
                upBtn.disabled = index === 0;
            }
            if (downBtn) {
                downBtn.disabled = index === cards.length - 1;
            }
        });
    }
    
    // Event listeners para botones móvil
    document.querySelectorAll('.btn-move-up').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            moveProductUp(productId);
        });
    });
    
    document.querySelectorAll('.btn-move-down').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            moveProductDown(productId);
        });
    });
    
    function handleDragStart(e) {
        draggedElement = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
    }
    
    function handleDragEnd(e) {
        this.classList.remove('dragging');
        // Limpiar todas las clases drag-over
        cards.forEach(card => card.classList.remove('drag-over'));
    }
    
    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }
    
    function handleDragEnter(e) {
        if (this !== draggedElement) {
            this.classList.add('drag-over');
        }
    }
    
    function handleDragLeave(e) {
        this.classList.remove('drag-over');
    }
    
    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        if (draggedElement !== this) {
            // Intercambiar posiciones
            const allCards = Array.from(grid.querySelectorAll('.product-sortable-card'));
            const draggedIndex = allCards.indexOf(draggedElement);
            const targetIndex = allCards.indexOf(this);
            
            if (draggedIndex < targetIndex) {
                grid.insertBefore(draggedElement, this.nextSibling);
            } else {
                grid.insertBefore(draggedElement, this);
            }
            
            hasChanges = true;
            saveBtn.style.display = 'inline-block';
        }
        
        this.classList.remove('drag-over');
        return false;
    }
    
    // Guardar orden
    saveBtn.addEventListener('click', function() {
        const cards = grid.querySelectorAll('.product-sortable-card');
        const order = [];
        const categoria = '<?= $categoriaFiltro ?>';
        
        cards.forEach((card, index) => {
            order.push({
                id: card.getAttribute('data-id'),
                orden: index + 1
            });
        });
        
        // Deshabilitar botón mientras se guarda
        saveBtn.disabled = true;
        saveBtn.textContent = '💾 Guardando...';
        
        fetch('api/save-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order: order })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                hasChanges = false;
                saveBtn.style.display = 'none';
                saveMessage.style.display = 'block';
                saveMessage.className = 'alert-success';
                saveMessage.textContent = '✅ Orden guardado correctamente';
                
                // Actualizar data-orden en las cards
                cards.forEach((card, index) => {
                    card.setAttribute('data-orden', index + 1);
                });
                
                setTimeout(() => {
                    saveMessage.style.display = 'none';
                }, 3000);
            } else {
                saveMessage.style.display = 'block';
                saveMessage.className = 'alert-error';
                saveMessage.textContent = '❌ Error al guardar: ' + (data.error || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            saveMessage.style.display = 'block';
            saveMessage.className = 'alert-error';
            saveMessage.textContent = '❌ Error al guardar el orden';
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.textContent = '💾 Guardar Orden';
        });
    });
})();
</script>

<?php require_once '_inc/footer.php'; ?>

