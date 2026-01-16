<?php
/**
 * Lista de cupones
 */
$pageTitle = 'Cupones y Descuentos';
require_once '../../config.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../../helpers/auth.php';
require_once '../../helpers/coupons.php';
startSecureSession();
requireAuth();

require_once '../_inc/header.php';

// Obtener todos los cupones
$coupons = getAllCoupons(false);
?>

<div class="admin-content">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-secondary">← Volver al Dashboard</a>
    </div>
    <div class="page-header">
        <h2>Cupones y Descuentos</h2>
        <a href="add.php" class="btn btn-primary">➕ Nuevo Cupón</a>
    </div>
    
    <?php if (empty($coupons)): ?>
        <div class="empty-state">
            <p>No hay cupones creados aún.</p>
            <a href="add.php" class="btn btn-primary">Crear primer cupón</a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Descuento</th>
                        <th>Usos</th>
                        <th>Válido Desde</th>
                        <th>Válido Hasta</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                            <td>
                                <?php if ($coupon['type'] === 'percentage'): ?>
                                    <span class="badge badge-info">Porcentaje</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Monto Fijo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($coupon['type'] === 'percentage'): ?>
                                    <?= number_format($coupon['value'], 0) ?>%
                                    <?php if ($coupon['max_discount']): ?>
                                        <br><small>Máx: $<?= number_format($coupon['max_discount'], 2, ',', '.') ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    $<?= number_format($coupon['value'], 2, ',', '.') ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($coupon['usage_limit']): ?>
                                    <?= $coupon['used_count'] ?> / <?= $coupon['usage_limit'] ?>
                                <?php else: ?>
                                    <?= $coupon['used_count'] ?> (sin límite)
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($coupon['valid_from']): ?>
                                    <?= date('d/m/Y', strtotime($coupon['valid_from'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin inicio</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($coupon['valid_until']): ?>
                                    <?= date('d/m/Y', strtotime($coupon['valid_until'])) ?>
                                    <?php if (strtotime($coupon['valid_until']) < time()): ?>
                                        <br><small class="text-danger">Expirado</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin expiración</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($coupon['active']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?= $coupon['id'] ?>" class="btn btn-sm btn-primary">✏️ Editar</a>
                                    <a href="delete.php?id=<?= $coupon['id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('¿Estás seguro de eliminar este cupón?')">🗑️ Eliminar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .badge-info {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .badge-success {
        background: #e8f5e9;
        color: #388e3c;
    }
    
    .badge-secondary {
        background: #f5f5f5;
        color: #666;
    }
    
    .text-muted {
        color: #999;
    }
    
    .text-danger {
        color: #d32f2f;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
</style>

<?php require_once '../_inc/footer.php'; ?>

