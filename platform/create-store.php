<?php
/**
 * Crear tienda - Flujo de creación de una nueva tienda en la plataforma
 */
$pageTitle = 'Crear Tienda';
require_once __DIR__ . '/../platform-config.php';
platformRequireAuth();

$userId = $_SESSION['platform_user_id'];
$user = platformGetCurrentUser();

$error = '';
$success = '';
$old = [
    'store_name'     => '',
    'slug'           => '',
    'admin_username' => '',
    'whatsapp'       => '',
    'instagram'      => '',
    'description'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!platformValidateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Recargá la página.';
    } else {
        $storeName     = platformSanitize($_POST['store_name'] ?? '');
        $slug          = strtolower(trim($_POST['slug'] ?? ''));
        $adminUsername  = platformSanitize($_POST['admin_username'] ?? '');
        $adminPassword  = $_POST['admin_password'] ?? '';
        $whatsapp      = platformSanitize($_POST['whatsapp'] ?? '');
        $instagram     = platformSanitize($_POST['instagram'] ?? '');
        $description   = platformSanitize($_POST['description'] ?? '');

        $old = [
            'store_name'     => $storeName,
            'slug'           => $slug,
            'admin_username' => $adminUsername,
            'whatsapp'       => $whatsapp,
            'instagram'      => $instagram,
            'description'    => $description,
        ];

        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);

        if (empty($storeName) || empty($slug) || empty($adminUsername) || empty($adminPassword)) {
            $error = 'Completá todos los campos obligatorios';
        } elseif (strlen($slug) < 3 || strlen($slug) > 50) {
            $error = 'El slug debe tener entre 3 y 50 caracteres';
        } elseif (!preg_match('/^[a-z][a-z0-9\-]*$/', $slug)) {
            $error = 'El slug debe empezar con una letra y solo contener letras, números y guiones';
        } elseif (strlen($adminPassword) < 6) {
            $error = 'La contraseña del admin debe tener al menos 6 caracteres';
        } else {
            $existingStore = platformFetchOne(
                'SELECT id FROM stores WHERE slug = :slug',
                ['slug' => $slug]
            );

            if ($existingStore) {
                $error = 'Ya existe una tienda con ese slug. Elegí otro.';
            } else {
                $dbName = PLATFORM_DB_NAME;

                platformQuery(
                    "INSERT INTO stores (owner_id, slug, db_name, plan, status) VALUES (:oid, :slug, :db, 'free', 'active')",
                    ['oid' => $userId, 'slug' => $slug, 'db' => $dbName]
                );
                $storeId = platformLastInsertId();

                if (!$storeId) {
                    $error = 'Error al crear la tienda en la base de datos.';
                } else {
                    $dbResult = createStoreData(
                        $storeId,
                        $storeName,
                        $adminUsername,
                        $adminPassword,
                        $user['email'],
                        $whatsapp,
                        $instagram,
                        $description
                    );

                    if (!$dbResult['success']) {
                        platformQuery('DELETE FROM stores WHERE id = :id', ['id' => $storeId]);
                        $error = 'Error al configurar la tienda: ' . ($dbResult['error'] ?? 'Error desconocido');
                    } else {
                        platformQuery(
                            "INSERT INTO store_members (store_id, user_id, role) VALUES (:sid, :uid, 'owner')",
                            ['sid' => $storeId, 'uid' => $userId]
                        );

                        header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php?created=1');
                        exit;
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/_inc/header.php';
?>

<div class="platform-page-header">
    <h1>Crear nueva tienda</h1>
    <p>Completá los datos para crear tu tienda online</p>
</div>

<?php if ($error): ?>
    <div class="alert-t alert-t-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($_GET['created'])): ?>
    <div class="alert-t alert-t-success">¡Tienda creada exitosamente!</div>
<?php endif; ?>

<div class="platform-card" style="max-width:700px;">
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= platformCSRFToken() ?>">

        <h2 style="font-size:1.15rem; font-weight:700; color:var(--t-dark); margin-bottom:1.25rem;">
            Datos de la tienda
        </h2>

        <div class="form-t-group">
            <label for="store_name">Nombre de la tienda *</label>
            <input type="text" id="store_name" name="store_name" class="form-t-input" required
                   placeholder="Ej: Lume - Velas Artesanales" value="<?= htmlspecialchars($old['store_name']) ?>">
        </div>

        <div class="form-t-group">
            <label for="slug">Slug (URL de tu tienda) *</label>
            <input type="text" id="slug" name="slug" class="form-t-input" required
                   placeholder="ej: lume" value="<?= htmlspecialchars($old['slug']) ?>"
                   pattern="[a-z][a-z0-9\-]*" minlength="3" maxlength="50">
            <small>Tu tienda estará en: <strong>www.somostiendi.com/<span id="slugPreview"><?= htmlspecialchars($old['slug'] ?: 'tu-tienda') ?></span>/</strong></small>
        </div>

        <div class="form-t-row">
            <div class="form-t-group">
                <label for="whatsapp">WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" class="form-t-input"
                       placeholder="+54 11 1234-5678" value="<?= htmlspecialchars($old['whatsapp']) ?>">
            </div>
            <div class="form-t-group">
                <label for="instagram">Instagram</label>
                <input type="text" id="instagram" name="instagram" class="form-t-input"
                       placeholder="@tu_tienda" value="<?= htmlspecialchars($old['instagram']) ?>">
            </div>
        </div>

        <div class="form-t-group">
            <label for="description">Descripción breve</label>
            <textarea id="description" name="description" class="form-t-input" rows="3"
                      placeholder="Contá brevemente de qué se trata tu tienda..."><?= htmlspecialchars($old['description']) ?></textarea>
        </div>

        <hr style="border:none; border-top:1px solid var(--t-border); margin:1.5rem 0;">

        <h2 style="font-size:1.15rem; font-weight:700; color:var(--t-dark); margin-bottom:1.25rem;">
            Administrador de la tienda
        </h2>
        <p style="color:var(--t-muted); font-size:0.9rem; margin-bottom:1.25rem;">
            Estas credenciales se usarán para acceder al panel de administración de esta tienda.
        </p>

        <div class="form-t-row">
            <div class="form-t-group">
                <label for="admin_username">Usuario del admin *</label>
                <input type="text" id="admin_username" name="admin_username" class="form-t-input" required
                       placeholder="admin" value="<?= htmlspecialchars($old['admin_username']) ?>">
            </div>
            <div class="form-t-group">
                <label for="admin_password">Contraseña del admin *</label>
                <input type="password" id="admin_password" name="admin_password" class="form-t-input" required
                       placeholder="Mínimo 6 caracteres" minlength="6">
            </div>
        </div>

        <button type="submit" class="btn-t btn-t-primary btn-t-full" style="margin-top:0.5rem;">
            Crear Tienda
        </button>
    </form>
</div>

<script>
(function() {
    const slugInput = document.getElementById('slug');
    const preview = document.getElementById('slugPreview');
    if (slugInput && preview) {
        slugInput.addEventListener('input', function() {
            const val = this.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
            this.value = val;
            preview.textContent = val || 'tu-tienda';
        });
    }
})();
</script>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
