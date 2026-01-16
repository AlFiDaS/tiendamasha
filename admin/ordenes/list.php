<?php
/**
 * Lista de órdenes/pedidos
 */
$pageTitle = 'Pedidos';
require_once '../../config.php';
require_once '../../helpers/auth.php';

// Necesitamos autenticación
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
startSecureSession();
requireAuth();

// Filtros
$filtro_status = $_GET['status'] ?? '';
$buscar = $_GET['buscar'] ?? '';

// Construir consulta
$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];

if (!empty($filtro_status)) {
    $sql .= " AND status = :status";
    $params['status'] = $filtro_status;
}

if (!empty($buscar)) {
    $buscarTrimmed = trim($buscar);
    
    // Detectar si es una búsqueda por ID (número o #número)
    $isIdSearch = false;
    $orderId = null;
    
    // Si empieza con #, quitar el # y verificar si es número
    if (strpos($buscarTrimmed, '#') === 0) {
        $buscarTrimmed = substr($buscarTrimmed, 1);
    }
    
    // Verificar si es un número (búsqueda por ID)
    if (is_numeric($buscarTrimmed)) {
        $isIdSearch = true;
        $orderId = (int)$buscarTrimmed;
    }
    
    if ($isIdSearch) {
        // Búsqueda exacta por ID
        $sql .= " AND id = :order_id";
        $params['order_id'] = $orderId;
    } else {
        // Búsqueda por texto (nombre, email, teléfono, mercadopago_id)
        $buscarTerm = '%' . $buscarTrimmed . '%';
        $sql .= " AND (payer_name LIKE :buscar1 OR payer_email LIKE :buscar2 OR payer_phone LIKE :buscar3 OR mercadopago_id LIKE :buscar4)";
        $params['buscar1'] = $buscarTerm;
        $params['buscar2'] = $buscarTerm;
        $params['buscar3'] = $buscarTerm;
        $params['buscar4'] = $buscarTerm;
    }
}

$sql .= " ORDER BY created_at DESC";

$ordenes = fetchAll($sql, $params);

// Estadísticas
$stats = [
    'total' => fetchOne("SELECT COUNT(*) as total FROM orders", [])['total'] ?? 0,
    'approved' => fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'approved'", [])['total'] ?? 0,
    'pending' => fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'", [])['total'] ?? 0,
    'a_confirmar' => fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'a_confirmar'", [])['total'] ?? 0,
    'rejected' => fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'rejected'", [])['total'] ?? 0,
    'finalizado' => fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'finalizado'", [])['total'] ?? 0,
];

require_once '../_inc/header.php';
?>

<style>
.ordenes-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    padding: 0.875rem 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    margin: 0 0 0.25rem 0;
    color: #666;
    font-size: 0.75rem;
    font-weight: normal;
}

.stat-card .stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
    line-height: 1.2;
}

.stat-card.total .stat-value { color: #007bff; }
.stat-card.approved .stat-value { color: #28a745; }
.stat-card.pending .stat-value { color: #ffc107; }
.stat-card.a_confirmar .stat-value { color: #fd7e14; }
.stat-card.rejected .stat-value { color: #dc3545; }
.stat-card.finalizado .stat-value { color: #6c757d; }

.filters-container {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filters-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filters-form .form-group {
    flex: 1;
    min-width: 200px;
}

.filters-form label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.filters-form input,
.filters-form select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.ordenes-table {
    width: 100%;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ordenes-table table {
    width: 100%;
    border-collapse: collapse;
}

.ordenes-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.ordenes-table td {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: table-cell;
}

/* Asegurar que en desktop todo se muestre como tabla normal */
.ordenes-table tbody td {
    display: table-cell;
}

/* Asegurar que en desktop no se muestren los :after */
.ordenes-table tbody td[data-label="ID"]:after,
.ordenes-table tbody td[data-label="Cliente"]:after,
.ordenes-table tbody td[data-label="Total"]:after {
    display: none !important;
    content: none;
}

/* Asegurar que Estado se muestre en desktop */
.ordenes-table tbody td[data-label="Estado"] {
    display: table-cell;
}

.ordenes-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.status-cancelled {
    background: #e2e3e5;
    color: #383d41;
}

.status-a_confirmar {
    background: #ffeaa7;
    color: #6c5700;
}

.status-finalizado {
    background: #d1ecf1;
    color: #0c5460;
}

.btn-view {
    background: #007bff;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    font-size: 0.875rem;
}

.btn-view:hover {
    background: #0056b3;
}

.items-preview {
    max-width: 200px;
    font-size: 0.875rem;
    color: #666;
}

@media (max-width: 768px) {
    .ordenes-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.75rem;
    }
    
    .stat-card .stat-value {
        font-size: 1.25rem;
    }
    
    .stat-card h3 {
        font-size: 0.7rem;
    }
    
    .filters-container {
        padding: 1rem;
    }
    
    .filters-form {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .filters-form .form-group {
        min-width: 100%;
    }
    
    .filters-form .form-group:last-child {
        display: flex;
        gap: 0.5rem;
    }
    
    .filters-form .form-group:last-child button,
    .filters-form .form-group:last-child a {
        flex: 1;
    }
    
    /* Convertir tabla en cards en mobile */
    .ordenes-table {
        overflow: visible;
    }
    
    .ordenes-table table,
    .ordenes-table thead,
    .ordenes-table tbody,
    .ordenes-table tr {
        display: block;
        width: 100%;
    }
    
    .ordenes-table thead {
        display: none;
    }
    
    /* Ocultar Fecha y Teléfono en mobile ya que se muestran en ID y Cliente */
    .ordenes-table tbody td[data-label="Fecha"],
    .ordenes-table tbody td[data-label="Teléfono"] {
        display: none;
    }
    
    .ordenes-table tbody tr {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        margin-bottom: 0.5rem;
        padding: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .ordenes-table tbody td {
        display: block;
        padding: 0.15rem 0;
        border: none;
        text-align: left;
        font-size: 0.8rem;
    }
    
    .ordenes-table tbody td:before {
        display: none;
    }
    
    /* Layout compacto horizontal - solo en mobile */
    .ordenes-table tbody td[data-label="ID"] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        margin-bottom: 0.2rem;
        font-size: 0.95rem;
        font-weight: bold;
        border: none;
    }
    
    .ordenes-table tbody td[data-label="ID"]:after {
        content: attr(data-fecha);
        font-weight: normal;
        font-size: 0.8rem;
        color: #666;
    }
    
    .ordenes-table tbody td[data-label="Cliente"] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        margin-bottom: 0.2rem;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .ordenes-table tbody td[data-label="Cliente"]:after {
        content: attr(data-telefono);
        font-weight: normal;
        font-size: 0.8rem;
        color: #666;
    }
    
    .ordenes-table tbody td[data-label="Productos"] {
        padding: 0;
        margin-bottom: 0.2rem;
        font-size: 0.8rem;
        color: #666;
    }
    
    .ordenes-table tbody td[data-label="Total"] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        margin-bottom: 0.2rem;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .ordenes-table tbody td[data-label="Estado"] {
        display: none;
    }
    
    .ordenes-table tbody td[data-label="Total"]:after {
        content: attr(data-estado);
        font-weight: normal;
        display: inline-block !important;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        margin-left: 0.5rem;
    }
    
    /* Mostrar los :after solo en mobile */
    .ordenes-table tbody td[data-label="ID"]:after,
    .ordenes-table tbody td[data-label="Cliente"]:after {
        display: block !important;
    }
    
    /* Colores para los estados en el after basados en data-status-class */
    .ordenes-table tbody td[data-status-class="status-approved"]:after {
        background: #d4edda;
        color: #155724;
    }
    
    .ordenes-table tbody td[data-status-class="status-pending"]:after {
        background: #fff3cd;
        color: #856404;
    }
    
    .ordenes-table tbody td[data-status-class="status-rejected"]:after {
        background: #f8d7da;
        color: #721c24;
    }
    
    .ordenes-table tbody td[data-status-class="status-cancelled"]:after {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .ordenes-table tbody td[data-status-class="status-a_confirmar"]:after {
        background: #ffeaa7;
        color: #6c5700;
    }
    
    .ordenes-table tbody td[data-status-class="status-finalizado"]:after {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .ordenes-table tbody td[data-label="Acciones"] {
        padding: 0;
        margin-top: 0.3rem;
        text-align: center;
    }
    
    .btn-view {
        width: auto;
        min-width: 120px;
        text-align: center;
        padding: 0.35rem 0.75rem;
        font-size: 0.8rem;
        display: inline-block;
    }
}

@media (max-width: 480px) {
    .ordenes-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.625rem;
    }
    
    .stat-card .stat-value {
        font-size: 1.1rem;
    }
    
    .stat-card h3 {
        font-size: 0.65rem;
    }
    
    .ordenes-table tbody tr {
        padding: 0.5rem;
        margin-bottom: 0.375rem;
    }
    
    .ordenes-table tbody td {
        padding: 0.2rem 0;
        font-size: 0.8rem;
    }
    
    .ordenes-table tbody td:before {
        font-size: 0.7rem;
    }
    
    .ordenes-table tbody td[data-label="ID"] {
        font-size: 0.95rem;
        margin-bottom: 0.2rem;
        padding-bottom: 0.3rem;
    }
    
    .ordenes-table tbody td[data-label="Acciones"] {
        margin-top: 0.3rem;
        padding-top: 0.3rem;
    }
    
    .btn-view {
        padding: 0.4rem;
        font-size: 0.8rem;
    }
    
    .admin-content h2 {
        font-size: 1.5rem;
    }
}
</style>

<div class="admin-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2>Pedidos</h2>
    </div>

    <!-- Estadísticas -->
    <div class="ordenes-stats">
        <div class="stat-card total">
            <h3>Total de Órdenes</h3>
            <div class="stat-value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card approved">
            <h3>Aprobadas</h3>
            <div class="stat-value"><?= $stats['approved'] ?></div>
        </div>
        <div class="stat-card a_confirmar">
            <h3>A Confirmar</h3>
            <div class="stat-value"><?= $stats['a_confirmar'] ?></div>
        </div>
        <div class="stat-card pending">
            <h3>Pendientes</h3>
            <div class="stat-value"><?= $stats['pending'] ?></div>
        </div>
        <div class="stat-card finalizado">
            <h3>Finalizadas</h3>
            <div class="stat-value"><?= $stats['finalizado'] ?></div>
        </div>
        <div class="stat-card rejected">
            <h3>Rechazadas</h3>
            <div class="stat-value"><?= $stats['rejected'] ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters-container">
        <form method="GET" class="filters-form">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" 
                       name="buscar" 
                       placeholder="Nombre, email, teléfono, ID..." 
                       value="<?= htmlspecialchars($buscar) ?>">
            </div>
            
            <div class="form-group">
                <label>Estado</label>
                <select name="status">
                    <option value="">Todos</option>
                    <option value="pending" <?= $filtro_status === 'pending' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="a_confirmar" <?= $filtro_status === 'a_confirmar' ? 'selected' : '' ?>>A Confirmar</option>
                    <option value="approved" <?= $filtro_status === 'approved' ? 'selected' : '' ?>>Aprobadas</option>
                    <option value="rejected" <?= $filtro_status === 'rejected' ? 'selected' : '' ?>>Rechazadas</option>
                    <option value="cancelled" <?= $filtro_status === 'cancelled' ? 'selected' : '' ?>>Canceladas</option>
                    <option value="finalizado" <?= $filtro_status === 'finalizado' ? 'selected' : '' ?>>Finalizadas</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <?php if (!empty($buscar) || !empty($filtro_status)): ?>
                    <a href="list.php" class="btn btn-secondary" style="margin-left: 0.5rem;">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla de órdenes -->
    <div class="ordenes-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ordenes)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: #666;">
                            No se encontraron órdenes.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ordenes as $orden): ?>
                        <?php 
                        $items = json_decode($orden['items'] ?? '[]', true);
                        $statusClass = 'status-' . ($orden['status'] ?? 'pending');
                        $statusLabels = [
                            'pending' => 'Pendiente',
                            'a_confirmar' => 'A Confirmar',
                            'approved' => 'Aprobada',
                            'rejected' => 'Rechazada',
                            'cancelled' => 'Cancelada',
                            'finalizado' => 'Finalizada'
                        ];
                        $statusLabel = $statusLabels[$orden['status'] ?? 'pending'] ?? 'Desconocido';
                        ?>
                        <tr>
                            <td data-label="ID" data-fecha="<?= date('d/m/Y H:i', strtotime($orden['created_at'])) ?>">
                                <strong>#<?= htmlspecialchars($orden['id']) ?></strong>
                            </td>
                            <td data-label="Fecha">
                                <?= date('d/m/Y H:i', strtotime($orden['created_at'])) ?>
                            </td>
                            <td data-label="Cliente" data-telefono="<?= htmlspecialchars($orden['payer_phone'] ?? '-') ?>">
                                <strong><?= htmlspecialchars($orden['payer_name'] ?? 'N/A') ?></strong>
                                <?php if ($orden['payer_email']): ?>
                                    <br><small style="color: #666;"><?= htmlspecialchars($orden['payer_email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Teléfono">
                                <?= htmlspecialchars($orden['payer_phone'] ?? '-') ?>
                            </td>
                            <td data-label="Productos" class="items-preview">
                                <?php if (is_array($items) && count($items) > 0): ?>
                                    <?php 
                                    $itemsText = array_map(function($item) {
                                        return ($item['name'] ?? 'Producto') . ' x' . ($item['cantidad'] ?? 1);
                                    }, array_slice($items, 0, 2));
                                    echo htmlspecialchars(implode(', ', $itemsText));
                                    if (count($items) > 2) {
                                        echo ' +' . (count($items) - 2) . ' más';
                                    }
                                    ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td data-label="Total" data-estado="<?= $statusLabel ?>" data-status-class="<?= $statusClass ?>">
                                <strong>$<?= number_format($orden['total_amount'] ?? 0, 2, ',', '.') ?></strong>
                            </td>
                            <td data-label="Estado" class="status-cell">
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td data-label="Acciones">
                                <a href="detail.php?id=<?= $orden['id'] ?>" class="btn-view">Ver Detalle</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../_inc/footer.php'; ?>

