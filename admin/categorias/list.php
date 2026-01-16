<?php
/**
 * Lista de categorías
 */
$pageTitle = 'Gestionar Categorías';
require_once '../../config.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../../helpers/auth.php';
require_once '../../helpers/categories.php';
startSecureSession();
requireAuth();

// Obtener todas las categorías
$categorias = getAllCategories(false);

// Contar productos por categoría
foreach ($categorias as &$cat) {
    $cat['product_count'] = countProductsInCategory($cat['slug']);
}
unset($cat);

require_once '../_inc/header.php';
?>

<div class="admin-content">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-secondary">← Volver al Dashboard</a>
    </div>
    <div class="page-header">
        <h2>Gestionar Categorías</h2>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="../ordenar.php" class="btn btn-secondary">📋 Ordenar Productos</a>
            <a href="add.php" class="btn btn-primary">➕ Agregar Categoría</a>
        </div>
    </div>
    
    <?php if (empty($categorias)): ?>
        <div class="alert alert-info">
            No hay categorías creadas. <a href="add.php">Agregar primera categoría</a>
        </div>
    <?php else: ?>
        <table class="categories-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Productos</th>
                    <th>Orden</th>
                    <th>Visible</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td data-label="ID"><?= htmlspecialchars($cat['id']) ?></td>
                        <td data-label="Nombre">
                            <strong><?= htmlspecialchars($cat['name']) ?></strong>
                            <div class="mobile-extra-info">
                                <div class="mobile-info-row">
                                    <span class="mobile-label">Slug:</span>
                                    <code><?= htmlspecialchars($cat['slug']) ?></code>
                                </div>
                                <div class="mobile-info-row">
                                    <span class="mobile-label">Orden:</span>
                                    <span><?= htmlspecialchars($cat['orden']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td data-label="Slug" class="desktop-only"><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                        <td data-label="Productos">
                            <span class="badge badge-info"><?= $cat['product_count'] ?> productos</span>
                        </td>
                        <td data-label="Orden" class="desktop-only"><?= htmlspecialchars($cat['orden']) ?></td>
                        <td data-label="Visible">
                            <?php if ($cat['visible']): ?>
                                <span class="badge badge-success">Visible</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Oculta</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Acciones">
                            <div class="actions">
                                <a href="edit.php?id=<?= $cat['id'] ?>" class="btn-edit">Editar</a>
                                <?php if ($cat['product_count'] == 0): ?>
                                    <a href="delete.php?id=<?= $cat['id'] ?>" 
                                       class="btn-delete"
                                       onclick="return confirm('¿Estás seguro de eliminar esta categoría?')">Eliminar</a>
                                <?php else: ?>
                                    <span class="no-delete-message" title="No se puede eliminar porque tiene productos asociados">
                                        No eliminar
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <h3 style="margin-bottom: 1rem;">💡 Notas importantes:</h3>
            <ul style="margin-left: 1.5rem; color: #666;">
                <li>Las categorías <strong>visibles</strong> aparecen en el sitio web y en el selector al agregar productos.</li>
                <li>Las categorías <strong>ocultas</strong> no aparecen en el sitio web, pero sí en el selector del admin (para crear productos antes de hacerlas visibles).</li>
                <li>No se pueden eliminar categorías que tienen productos asociados.</li>
                <li>El <strong>slug</strong> se usa en las URLs (ej: /productos/, /souvenirs/)</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<style>
/* Header de página */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

/* Ocultar información extra en desktop */
.mobile-extra-info {
    display: none;
}

/* Estilos responsive para tabla de categorías */
@media (max-width: 768px) {
    .categories-table {
        width: 100%;
        display: block;
    }
    
    .categories-table thead {
        display: none;
    }
    
    .categories-table tbody {
        display: block;
    }
    
    .categories-table tbody tr {
        display: block;
        border: 1px solid #e0e0e0;
        margin-bottom: 1rem;
        padding: 1.25rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .categories-table tbody td {
        display: block;
        border: none;
        padding: 0.75rem 0;
        text-align: left;
    }
    
    .categories-table tbody td:before {
        content: attr(data-label) ": ";
        font-weight: 700;
        color: #666;
        display: inline-block;
        min-width: 90px;
        margin-right: 0.5rem;
    }
    
    /* ID - ocultar label, solo mostrar número pequeño */
    .categories-table tbody td[data-label="ID"] {
        display: none;
    }
    
    /* Nombre - estilo destacado */
    .categories-table tbody td[data-label="Nombre"] {
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
        margin-bottom: 0.5rem;
    }
    
    .categories-table tbody td[data-label="Nombre"]:before {
        display: none;
    }
    
    .categories-table tbody td[data-label="Nombre"] strong {
        font-size: 1.3rem;
        color: #333;
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 700;
    }
    
    /* Información extra en mobile */
    .categories-table tbody td[data-label="Nombre"] .mobile-extra-info {
        display: block;
        margin-top: 0.5rem;
    }
    
    .mobile-info-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .mobile-label {
        font-weight: 600;
        color: #666;
        min-width: 60px;
        font-size: 0.9rem;
    }
    
    .mobile-info-row code {
        background: #f5f5f5;
        padding: 0.3rem 0.6rem;
        border-radius: 6px;
        font-size: 0.85rem;
        color: #333;
        border: 1px solid #e0e0e0;
    }
    
    /* Productos y Visible - en línea horizontal */
    .categories-table tbody td[data-label="Productos"] {
        display: inline-block;
        width: auto;
        padding: 0.5rem 0.75rem 0.5rem 0;
        margin-right: 1rem;
    }
    
    .categories-table tbody td[data-label="Productos"]:before {
        display: none;
    }
    
    .categories-table tbody td[data-label="Visible"] {
        display: inline-block;
        width: auto;
        padding: 0.5rem 0;
    }
    
    .categories-table tbody td[data-label="Visible"]:before {
        display: none;
    }
    
    /* Badges mejorados */
    .categories-table .badge {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
        display: inline-block;
    }
    
    /* Acciones */
    .categories-table tbody td[data-label="Acciones"] {
        padding-top: 1rem;
        margin-top: 1rem;
        border-top: 2px solid #f0f0f0;
    }
    
    .categories-table tbody td[data-label="Acciones"]:before {
        display: none;
    }
    
    .categories-table tbody td.desktop-only {
        display: none !important;
    }
    
    /* Botones de acción responsive */
    .categories-table .actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        width: 100%;
    }
    
    .categories-table .actions a {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        text-align: center;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .categories-table .btn-edit {
        background: #4a90e2;
        color: white;
    }
    
    .categories-table .btn-edit:hover {
        background: #357abd;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .categories-table .btn-delete {
        background: #e74c3c;
        color: white;
    }
    
    .categories-table .btn-delete:hover {
        background: #c0392b;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .categories-table .no-delete-message {
        display: block;
        color: #999;
        font-size: 0.85rem;
        padding: 0.75rem;
        text-align: center;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    
    /* Header responsive */
    .page-header {
        flex-direction: column;
        align-items: stretch !important;
        gap: 1rem;
    }
    
    .page-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }
    
    .page-header > div {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        width: 100%;
    }
    
    .page-header .btn {
        width: 100%;
        text-align: center;
        padding: 0.875rem 1.5rem;
        font-size: 1rem;
    }
    
    /* Notas responsive */
    .admin-content > div:last-child {
        padding: 1rem !important;
        margin-top: 1.5rem !important;
    }
    
    .admin-content > div:last-child h3 {
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
    }
    
    .admin-content > div:last-child ul {
        margin-left: 1.25rem;
        font-size: 0.9rem;
        line-height: 1.6;
    }
    
    .admin-content > div:last-child li {
        margin-bottom: 0.6rem;
    }
    
    /* Ajustar padding del contenedor */
    .admin-content {
        padding: 1.25rem !important;
    }
}

@media (max-width: 480px) {
    .admin-content {
        padding: 1rem !important;
    }
    
    .categories-table tbody tr {
        padding: 1rem;
        margin-bottom: 0.875rem;
    }
    
    .categories-table tbody td[data-label="Nombre"] strong {
        font-size: 1.2rem;
    }
    
    .mobile-info-row {
        font-size: 0.85rem;
        flex-wrap: wrap;
    }
    
    .mobile-label {
        min-width: 55px;
        font-size: 0.85rem;
    }
    
    .categories-table .actions {
        gap: 0.5rem;
    }
    
    .categories-table .actions a {
        padding: 0.65rem 0.875rem;
        font-size: 0.85rem;
    }
    
    .page-header h2 {
        font-size: 1.3rem;
    }
}
</style>

<?php require_once '../_inc/footer.php'; ?>

