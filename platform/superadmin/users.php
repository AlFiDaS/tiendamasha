<?php
/**
 * Super Admin - Lista de usuarios de la plataforma
 */
$pageTitle = 'Usuarios';
require_once __DIR__ . '/../../platform-config.php';

$users = platformFetchAll(
    'SELECT pu.*,
            (SELECT COUNT(*) FROM store_members sm WHERE sm.user_id = pu.id) as stores_count
     FROM platform_users pu
     ORDER BY pu.created_at DESC'
);

require_once __DIR__ . '/_inc/header.php';
?>

<div class="sa-page-header">
    <h1>Usuarios de la plataforma</h1>
    <p><?= count($users) ?> usuario(s) registrado(s)</p>
</div>

<div class="sa-table-wrap">
    <div class="sa-table-header">
        <h2>Todos los usuarios</h2>
    </div>
    <table class="sa-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Tiendas</th>
                <th>Verificado</th>
                <th>Registrado</th>
                <th>Último login</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="8" style="text-align:center; padding:3rem; color:var(--sa-muted);">No hay usuarios aún</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
            <tr>
                <td style="color:var(--sa-muted);">#<?= (int)$u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['name'] ?? '-') ?></strong></td>
                <td style="color:var(--sa-muted);"><?= htmlspecialchars($u['email']) ?></td>
                <td style="font-size:0.85rem;"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                <td style="text-align:center; font-weight:600;"><?= (int)$u['stores_count'] ?></td>
                <td style="text-align:center;">
                    <?php if ($u['email_verified']): ?>
                        <span class="sa-badge sa-badge-active">Sí</span>
                    <?php else: ?>
                        <span class="sa-badge sa-badge-setup">No</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.85rem;"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                <td style="font-size:0.85rem; color:var(--sa-muted);">
                    <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Nunca' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
