<?php
/**
 * Detalle de orden
 */
$pageTitle = 'Detalle de Orden';
require_once '../../config.php';
require_once '../../helpers/auth.php';

// Necesitamos autenticación
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
startSecureSession();
requireAuth();

$ordenId = sanitize($_GET['id'] ?? '');

if (empty($ordenId)) {
    $_SESSION['error_message'] = 'ID de orden no válido';
    header('Location: list.php');
    exit;
}

// Obtener orden
$orden = fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $ordenId]);

if (!$orden) {
    $_SESSION['error_message'] = 'Orden no encontrada';
    header('Location: list.php');
    exit;
}

// Decodificar datos JSON
$items = json_decode($orden['items'] ?? '[]', true);
$metadata = json_decode($orden['metadata'] ?? '{}', true);

/**
 * Generar mensaje de WhatsApp según el estado de la orden
 */
function generateWhatsAppMessage($orden, $items) {
    $nombre = $orden['payer_name'] ?? 'Cliente';
    $ordenId = $orden['id'];
    $status = $orden['status'] ?? 'pending';
    
    if ($status === 'approved') {
        // Mensaje para pedido aprobado
        $mensaje = "Hola $nombre, somos Lume. Confirmamos tu pedido #$ordenId!\n\n";
        $mensaje .= "Productos pedidos:\n";
        
        if (is_array($items) && count($items) > 0) {
            foreach ($items as $item) {
                $cantidad = intval($item['cantidad'] ?? 1);
                $nombreProducto = $item['name'] ?? 'Producto';
                $mensaje .= "$cantidad x $nombreProducto\n";
            }
        }
        
        $mensaje .= "\nGracias por tu compra!";
        
    } elseif ($status === 'a_confirmar') {
        // Mensaje para pedido a confirmar
        $mensaje = "Hola $nombre, somos Lume, queríamos avisarte que tuvimos un error en el comprobante de pago del pedido #$ordenId.\n\n";
        $mensaje .= "¿Podrías reenviarnos por aquí el comprobante por favor?\n\n";
        $mensaje .= "Muchas gracias.";
        
    } elseif ($status === 'rejected') {
        // Mensaje para pedido rechazado (con espacio para motivo manual)
        $mensaje = "Hola $nombre, somos Lume, queríamos avisarte que tu pedido #$ordenId fue rechazado por ";
        // Dejar espacio para que el usuario escriba el motivo manualmente
        
    } else {
        // Mensaje genérico para otros estados
        $mensaje = "Hola $nombre, somos Lume. Te contactamos respecto a tu pedido #$ordenId.";
    }
    
    return $mensaje;
}

/**
 * Formatear número de teléfono para WhatsApp
 * Remueve espacios, guiones, paréntesis y agrega código de país si no lo tiene
 */
function formatPhoneForWhatsApp($phone) {
    if (empty($phone)) {
        return null;
    }
    
    // Remover caracteres no numéricos excepto +
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Si no empieza con +, asumir que es de Argentina (+54)
    if (substr($phone, 0, 1) !== '+') {
        // Si empieza con 54, agregar +
        if (substr($phone, 0, 2) === '54') {
            $phone = '+' . $phone;
        } else {
            // Si empieza con 0, removerlo y agregar +54
            if (substr($phone, 0, 1) === '0') {
                $phone = '+54' . substr($phone, 1);
            } else {
                // Agregar +54 al inicio
                $phone = '+54' . $phone;
            }
        }
    }
    
    // Remover el + para el enlace de WhatsApp (wa.me usa solo números)
    $phone = str_replace('+', '', $phone);
    
    return $phone;
}

// Generar mensaje y número de WhatsApp
$whatsappMessage = generateWhatsAppMessage($orden, $items);
$whatsappPhone = formatPhoneForWhatsApp($orden['payer_phone'] ?? '');
$whatsappUrl = $whatsappPhone ? 'https://wa.me/' . $whatsappPhone . '?text=' . urlencode($whatsappMessage) : null;

$statusLabels = [
    'pending' => 'Pendiente',
    'a_confirmar' => 'A Confirmar',
    'approved' => 'Aprobada',
    'rejected' => 'Rechazada',
    'cancelled' => 'Cancelada',
    'finalizado' => 'Finalizada'
];
$statusLabel = $statusLabels[$orden['status'] ?? 'pending'] ?? 'Desconocido';

$statusClasses = [
    'a_confirmar' => 'status-a_confirmar',
    'approved' => 'status-approved',
    'pending' => 'status-pending',
    'finalizado' => 'status-finalizado',
    'rejected' => 'status-rejected',
    'cancelled' => 'status-cancelled'
];
$statusClass = $statusClasses[$orden['status'] ?? 'pending'] ?? 'status-pending';

require_once '../_inc/header.php';
?>

<style>
.orden-detail {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.detail-section {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.detail-section h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f0f0f0;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #666;
}

.detail-value {
    text-align: right;
    color: #333;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 12px;
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

.items-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.items-list li {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
}

.items-list li:last-child {
    border-bottom: none;
}

.total-row {
    font-weight: bold;
    font-size: 1.1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #333;
}

.btn-back {
    display: inline-block;
    background: #6c757d;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    margin-bottom: 1rem;
}

.btn-back:hover {
    background: #5a6268;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-whatsapp:hover {
    background: #20BA5A;
}

.btn-whatsapp:active {
    background: #1A9D4A;
}

@media (max-width: 968px) {
    .orden-detail {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .detail-section {
        padding: 1rem;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .detail-value {
        text-align: left;
    }
    
    .items-list li {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .items-list li > div:last-child {
        text-align: left;
        font-weight: 600;
    }
    
    .total-row {
        flex-direction: row;
        justify-content: space-between;
    }
}

@media (max-width: 768px) {
    .admin-content h2 {
        font-size: 1.5rem;
    }
    
    .admin-content > div:first-of-type {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .detail-section {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .detail-section h3 {
        font-size: 1.1rem;
    }
    
    form[method="POST"] {
        padding-top: 1rem;
    }
    
    form[method="POST"] > div {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    form[method="POST"] select,
    form[method="POST"] button {
        width: 100%;
    }
    
    .btn-whatsapp {
        width: 100%;
        justify-content: center;
    }
    
    .btn-back {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .detail-section {
        padding: 0.875rem;
    }
    
    .detail-row {
        padding: 0.5rem 0;
    }
    
    .items-list li {
        padding: 0.5rem;
    }
    
    .status-badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.75rem;
    }
}
</style>

<div class="admin-content">
    <a href="list.php" class="btn-back">← Volver a Pedidos</a>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Orden #<?= htmlspecialchars($orden['id']) ?></h2>
        <span class="status-badge <?= $statusClass ?>">
            <?= $statusLabel ?>
        </span>
    </div>

    <div class="orden-detail">
        <!-- Información principal -->
        <div>
            <!-- Items del pedido -->
            <div class="detail-section">
                <h3>Productos Pedidos</h3>
                <ul class="items-list">
                    <?php if (is_array($items) && count($items) > 0): ?>
                        <?php 
                        $subtotal = 0;
                        foreach ($items as $item): 
                            $precio = floatval(str_replace(['$', ',', '.'], '', $item['price'] ?? '0'));
                            $cantidad = intval($item['cantidad'] ?? 1);
                            $totalItem = $precio * $cantidad;
                            $subtotal += $totalItem;
                        ?>
                            <li>
                                <div>
                                    <strong><?= htmlspecialchars($item['name'] ?? 'Producto') ?></strong>
                                    <br>
                                    <small style="color: #666;">
                                        <?= htmlspecialchars($item['price'] ?? '$0') ?> x <?= $cantidad ?>
                                    </small>
                                </div>
                                <div>
                                    <strong>$<?= number_format($totalItem, 2, ',', '.') ?></strong>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <li class="total-row">
                            <span>Total:</span>
                            <span>$<?= number_format($orden['total_amount'] ?? $subtotal, 2, ',', '.') ?></span>
                        </li>
                    <?php else: ?>
                        <li>No hay items en esta orden</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Información de envío -->
            <?php if ($orden['shipping_type'] || $orden['shipping_address']): ?>
            <div class="detail-section">
                <h3>Información de Envío</h3>
                <?php if ($orden['shipping_type']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Tipo de envío:</span>
                        <span class="detail-value"><?= htmlspecialchars($orden['shipping_type']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($orden['shipping_address']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Dirección:</span>
                        <span class="detail-value" style="text-align: left; white-space: pre-line;"><?= htmlspecialchars($orden['shipping_address']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Notas -->
            <?php if ($orden['notes']): ?>
            <div class="detail-section">
                <h3>Notas del Cliente</h3>
                <p style="white-space: pre-line;"><?= htmlspecialchars($orden['notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Información del cliente y pago -->
        <div>
            <!-- Datos del cliente -->
            <div class="detail-section">
                <h3>Datos del Cliente</h3>
                <div class="detail-row">
                    <span class="detail-label">Nombre:</span>
                    <span class="detail-value"><?= htmlspecialchars($orden['payer_name'] ?? 'N/A') ?></span>
                </div>
                <?php if ($orden['payer_email']): ?>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?= htmlspecialchars($orden['payer_email']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($orden['payer_phone']): ?>
                <div class="detail-row">
                    <span class="detail-label">Teléfono:</span>
                    <span class="detail-value"><?= htmlspecialchars($orden['payer_phone']) ?></span>
                </div>
                <?php if ($whatsappUrl): ?>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f0f0f0;">
                    <a href="<?= $whatsappUrl ?>" 
                       target="_blank" 
                       class="btn-whatsapp"
                       style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #25D366; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; transition: background 0.2s;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink: 0;">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        Contactar al cliente
                    </a>
                    <?php if ($orden['status'] === 'rejected'): ?>
                    <p style="margin-top: 0.5rem; font-size: 0.75rem; color: #856404;">
                        ⚠️ El mensaje incluye espacio para que escribas el motivo del rechazo manualmente.
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($orden['payer_document']): ?>
                <div class="detail-row">
                    <span class="detail-label">Documento:</span>
                    <span class="detail-value"><?= htmlspecialchars($orden['payer_document']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Comprobante de pago (solo si existe y no está finalizado) -->
            <?php if (!empty($orden['proof_image']) && $orden['status'] !== 'finalizado'): ?>
            <div class="detail-section">
                <h3>Comprobante de Pago</h3>
                <div style="margin-top: 1rem;">
                    <img src="<?= BASE_URL . $orden['proof_image'] ?>" 
                         alt="Comprobante de pago" 
                         style="max-width: 100%; border-radius: 8px; border: 1px solid #ddd; cursor: pointer;"
                         onclick="window.open('<?= BASE_URL . $orden['proof_image'] ?>', '_blank')">
                    <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #666;">
                        <a href="<?= BASE_URL . $orden['proof_image'] ?>" target="_blank" style="color: #007bff;">Ver imagen completa</a>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Información de pago -->
            <div class="detail-section">
                <h3>Información de Pago</h3>
                <div class="detail-row">
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value">
                        <span class="status-badge <?= $statusClass ?>">
                            <?= $statusLabel ?>
                        </span>
                    </span>
                </div>
                
                <!-- Formulario para cambiar estado -->
                <form method="POST" action="update-status.php" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f0f0f0;">
                    <input type="hidden" name="order_id" value="<?= $orden['id'] ?>">
                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <label for="status" style="font-weight: 500;">Cambiar estado:</label>
                        <select name="status" id="status" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="pending" <?= $orden['status'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="a_confirmar" <?= $orden['status'] === 'a_confirmar' ? 'selected' : '' ?>>A Confirmar</option>
                            <option value="approved" <?= $orden['status'] === 'approved' ? 'selected' : '' ?>>Aprobada</option>
                            <option value="rejected" <?= $orden['status'] === 'rejected' ? 'selected' : '' ?>>Rechazada</option>
                            <option value="cancelled" <?= $orden['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                            <option value="finalizado" <?= $orden['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizada</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Actualizar
                        </button>
                    </div>
                    <?php if ($orden['status'] !== 'finalizado' && !empty($orden['proof_image'])): ?>
                        <p style="margin-top: 0.5rem; font-size: 0.75rem; color: #856404;">
                            ⚠️ Al cambiar a "Finalizada", se eliminará el comprobante de pago para ahorrar espacio.
                        </p>
                    <?php endif; ?>
                </form>
                <?php if ($orden['status_detail']): ?>
                <div class="detail-row">
                    <span class="detail-label">Detalle:</span>
                    <span class="detail-value"><?= htmlspecialchars($orden['status_detail']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($orden['payment_method']): ?>
                <div class="detail-row">
                    <span class="detail-label">Método:</span>
                    <span class="detail-value"><?= htmlspecialchars($orden['payment_method']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($orden['payment_type']): ?>
                <div class="detail-row">
                    <span class="detail-label">Tipo:</span>
                    <span class="detail-value"><?= htmlspecialchars($orden['payment_type']) ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="detail-label">Total:</span>
                    <span class="detail-value"><strong>$<?= number_format($orden['total_amount'] ?? 0, 2, ',', '.') ?></strong></span>
                </div>
            </div>

            <!-- IDs técnicos -->
            <div class="detail-section">
                <h3>IDs Técnicos</h3>
                <?php if ($orden['mercadopago_id']): ?>
                <div class="detail-row">
                    <span class="detail-label">MercadoPago ID:</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 0.875rem;"><?= htmlspecialchars($orden['mercadopago_id']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($orden['preference_id']): ?>
                <div class="detail-row">
                    <span class="detail-label">Preference ID:</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 0.875rem;"><?= htmlspecialchars($orden['preference_id']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($orden['external_reference']): ?>
                <div class="detail-row">
                    <span class="detail-label">Referencia Externa:</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 0.875rem;"><?= htmlspecialchars($orden['external_reference']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Fechas -->
            <div class="detail-section">
                <h3>Fechas</h3>
                <div class="detail-row">
                    <span class="detail-label">Creada:</span>
                    <span class="detail-value"><?= date('d/m/Y H:i:s', strtotime($orden['created_at'])) ?></span>
                </div>
                <?php if ($orden['updated_at']): ?>
                <div class="detail-row">
                    <span class="detail-label">Actualizada:</span>
                    <span class="detail-value"><?= date('d/m/Y H:i:s', strtotime($orden['updated_at'])) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Acciones peligrosas -->
            <div class="detail-section" style="border: 2px solid #dc3545; background: #fff5f5;">
                <h3 style="color: #dc3545;">Acciones Peligrosas</h3>
                <div style="margin-top: 1rem;">
                    <a href="delete.php?id=<?= $orden['id'] ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('¿Estás seguro de eliminar esta orden? Esta acción no se puede deshacer.');"
                       style="display: inline-block; padding: 0.5rem 1rem; background: #dc3545; color: white; text-decoration: none; border-radius: 4px;">
                        🗑️ Eliminar Orden
                    </a>
                    <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #721c24;">
                        Esta acción eliminará permanentemente la orden y su comprobante de pago (si existe).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../_inc/footer.php'; ?>

