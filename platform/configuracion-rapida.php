<?php
/**
 * Configuración Rápida - Solo accesible desde /platform/
 * Datos de la tienda + Transferencia directa
 */
$pageTitle = 'Configuración Rápida';
require_once __DIR__ . '/../platform-config.php';
platformRequireAuth();

$userId = $_SESSION['platform_user_id'];
$storeSlug = trim($_GET['store'] ?? $_POST['store'] ?? '');
$error = '';
$success = '';

if (empty($storeSlug)) {
    header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php');
    exit;
}

// Verificar que el usuario es miembro de la tienda
$store = platformFetchOne(
    'SELECT s.id, s.slug FROM stores s
     INNER JOIN store_members sm ON sm.store_id = s.id
     WHERE s.slug = :slug AND sm.user_id = :uid',
    ['slug' => $storeSlug, 'uid' => $userId]
);

if (!$store) {
    $error = 'No tenés acceso a esta tienda';
    $storeId = null;
    $settings = [];
} else {
    $storeId = (int) $store['id'];

    // Obtener shop_settings para esta tienda
    $settings = platformFetchOne(
        'SELECT * FROM shop_settings WHERE store_id = :sid',
        ['sid' => $storeId]
    ) ?: [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!platformValidateCSRF($_POST['csrf_token'] ?? '')) {
            $error = 'Token de seguridad inválido. Recargá la página.';
        } else {
            $instagram = trim(platformSanitize($_POST['instagram'] ?? ''));
            $facebook = trim(platformSanitize($_POST['facebook'] ?? ''));
            if ($instagram && !preg_match('#^https?://#i', $instagram)) {
                $instagram = preg_replace('/^@/', '', $instagram);
                $instagram = 'https://www.instagram.com/' . $instagram . '/';
            }
            if ($facebook && !preg_match('#^https?://#i', $facebook)) {
                $facebook = preg_replace('/^@/', '', $facebook);
                $facebook = 'https://www.facebook.com/' . $facebook . '/';
            }

            $shopName = platformSanitize($_POST['shop_name'] ?? '');
            $whatsapp = platformSanitize($_POST['whatsapp_number'] ?? '');
            $whatsappMsg = platformSanitize($_POST['whatsapp_message'] ?? '');
            $address = platformSanitize($_POST['address'] ?? '');
            $email = platformSanitize($_POST['email'] ?? '');
            $phone = platformSanitize($_POST['phone'] ?? '');
            $description = platformSanitize($_POST['description'] ?? '');
            $footerDesc = platformSanitize($_POST['footer_description'] ?? '');
            $creationYear = !empty($_POST['creation_year']) ? (int)$_POST['creation_year'] : null;
            $transferAlias = trim(platformSanitize($_POST['transfer_alias'] ?? ''));
            $transferCbu = preg_replace('/\D/', '', platformSanitize($_POST['transfer_cbu'] ?? ''));
            $transferTitular = trim(platformSanitize($_POST['transfer_titular'] ?? ''));

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'El email de contacto no es válido';
            } else {
                $shopLogo = $settings['shop_logo'] ?? null;

                // Procesar logo
                if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['shop_logo'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $isValid = true;
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
                        $error = 'Formato de archivo no permitido. Solo JPG, PNG, WEBP o SVG.';
                        $isValid = false;
                    } elseif ($file['size'] > 2 * 1024 * 1024) {
                        $error = 'El archivo es demasiado grande (máx. 2MB)';
                        $isValid = false;
                    } else {
                        if ($ext === 'svg') {
                            $svgContent = @file_get_contents($file['tmp_name']);
                            if ($svgContent === false || (strpos($svgContent, '<svg') === false && strpos($svgContent, '<?xml') === false)) {
                                $error = 'El archivo SVG no es válido';
                                $isValid = false;
                            }
                        }
                    }
                    if ($isValid && empty($error)) {
                        $basePath = dirname(PLATFORM_BASE_PATH);
                        $imagesPath = is_dir($basePath . '/public/images') ? $basePath . '/public/images' : $basePath . '/images';
                        $logoDir = $imagesPath . '/logo/' . preg_replace('/[^a-z0-9\-]/', '', strtolower($storeSlug));
                        if (!is_dir($logoDir)) {
                            @mkdir($logoDir, 0755, true);
                        }
                        if (is_dir($logoDir)) {
                            $filename = 'logo.' . $ext;
                            $destination = $logoDir . '/' . $filename;
                            foreach (glob($logoDir . '/logo.*') ?: [] as $old) {
                                @unlink($old);
                            }
                            if (move_uploaded_file($file['tmp_name'], $destination)) {
                                chmod($destination, 0644);
                                $shopLogo = '/images/logo/' . $storeSlug . '/' . $filename;
                            }
                        }
                    }
                }

                if (empty($error)) {
                    $cols = [
                        'shop_name' => $shopName,
                        'shop_logo' => $shopLogo,
                        'whatsapp_number' => $whatsapp,
                        'whatsapp_message' => $whatsappMsg,
                        'address' => $address,
                        'instagram' => $instagram,
                        'facebook' => $facebook,
                        'email' => $email,
                        'phone' => $phone,
                        'description' => $description,
                        'footer_description' => $footerDesc,
                        'creation_year' => $creationYear,
                        'transfer_alias' => $transferAlias,
                        'transfer_cbu' => $transferCbu,
                        'transfer_titular' => $transferTitular,
                        'configuracion_rapida_completada' => 1
                    ];

                    $sets = [];
                    $params = ['sid' => $storeId];
                    foreach ($cols as $k => $v) {
                        $sets[] = "`$k` = :$k";
                        $params[$k] = $v !== '' && $v !== null ? $v : null;
                    }

                    $sql = 'UPDATE shop_settings SET ' . implode(', ', $sets) . ' WHERE store_id = :sid';
                    if (platformQuery($sql, $params)) {
                        $success = 'Configuración guardada correctamente';
                        $settings = array_merge($settings ?: [], $cols);
                        header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php?config_ok=1');
                        exit;
                    } else {
                        $error = 'No se pudo actualizar la configuración';
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/_inc/header.php';
?>

<div class="platform-page-header">
    <h1>Configuración Rápida</h1>
    <p>Completá los datos de tu tienda <strong><?= htmlspecialchars($storeSlug) ?></strong> y los datos para transferencia directa.</p>
</div>

<?php if ($error): ?>
    <div class="alert-t alert-t-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($storeId): ?>
<div class="platform-card platform-card-form">
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= platformCSRFToken() ?>">
        <input type="hidden" name="store" value="<?= htmlspecialchars($storeSlug) ?>">

        <h2 style="font-size:1.15rem; font-weight:700; color:var(--t-dark); margin-bottom:1.25rem;">Datos de la tienda</h2>

        <div class="form-t-group">
            <label for="shop_name">Nombre de la Tienda *</label>
            <input type="text" id="shop_name" name="shop_name" class="form-t-input" required
                   value="<?= htmlspecialchars($settings['shop_name'] ?? '') ?>" placeholder="Ej: LUME - Velas Artesanales">
        </div>

        <div class="form-t-group">
            <label for="shop_logo">Logo de la Tienda</label>
            <?php if (!empty($settings['shop_logo'])): ?>
                <div style="margin-bottom:0.75rem;">
                    <p style="font-weight:600; margin-bottom:0.25rem;">Logo actual:</p>
                    <img src="<?= htmlspecialchars($settings['shop_logo']) ?>" alt="Logo" style="max-width:150px; max-height:80px; border:1px solid #ddd; border-radius:6px;" onerror="this.style.display='none'">
                </div>
            <?php endif; ?>
            <input type="file" id="shop_logo" name="shop_logo" class="form-t-input" accept="image/jpeg,image/png,image/webp,image/svg+xml,.svg">
            <small>JPG, PNG, WEBP o SVG. Máx. 2MB.</small>
        </div>

        <div class="form-t-group">
            <label for="whatsapp_number">WhatsApp</label>
            <input type="text" id="whatsapp_number" name="whatsapp_number" class="form-t-input"
                   value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>" placeholder="+5493795330156">
        </div>

        <div class="form-t-group">
            <label for="whatsapp_message">Mensaje predefinido para WhatsApp</label>
            <textarea id="whatsapp_message" name="whatsapp_message" class="form-t-input" rows="2" placeholder="Mensaje que se enviará al hacer clic en WhatsApp"><?= htmlspecialchars($settings['whatsapp_message'] ?? '') ?></textarea>
        </div>

        <div class="form-t-row">
            <div class="form-t-group">
                <label for="instagram">Instagram</label>
                <input type="text" id="instagram" name="instagram" class="form-t-input"
                       value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>" placeholder="@tu_cuenta">
            </div>
            <div class="form-t-group">
                <label for="facebook">Facebook</label>
                <input type="text" id="facebook" name="facebook" class="form-t-input"
                       value="<?= htmlspecialchars($settings['facebook'] ?? '') ?>" placeholder="tu_pagina">
            </div>
        </div>

        <div class="form-t-row">
            <div class="form-t-group">
                <label for="email">Email de contacto</label>
                <input type="email" id="email" name="email" class="form-t-input"
                       value="<?= htmlspecialchars($settings['email'] ?? '') ?>" placeholder="contacto@tienda.com">
            </div>
            <div class="form-t-group">
                <label for="phone">Teléfono</label>
                <input type="text" id="phone" name="phone" class="form-t-input"
                       value="<?= htmlspecialchars($settings['phone'] ?? '') ?>" placeholder="(0379) 15-1234-5678">
            </div>
        </div>

        <div class="form-t-group">
            <label for="address">Dirección del local</label>
            <textarea id="address" name="address" class="form-t-input" rows="2" placeholder="Calle, número, ciudad"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
        </div>

        <div class="form-t-group">
            <label for="description">Descripción para SEO</label>
            <textarea id="description" name="description" class="form-t-input" rows="4" placeholder="Breve descripción para buscadores"><?= htmlspecialchars($settings['description'] ?? '') ?></textarea>
        </div>

        <div class="form-t-group">
            <label for="footer_description">Descripción del Footer</label>
            <textarea id="footer_description" name="footer_description" class="form-t-input" rows="2" placeholder="Texto del pie de página"><?= htmlspecialchars($settings['footer_description'] ?? '') ?></textarea>
        </div>

        <div class="form-t-group">
            <label for="creation_year">Año de creación</label>
            <input type="number" id="creation_year" name="creation_year" class="form-t-input"
                   value="<?= htmlspecialchars($settings['creation_year'] ?? date('Y')) ?>" min="1900" max="<?= date('Y') ?>">
        </div>

        <hr style="border:none; border-top:1px solid var(--t-border); margin:1.5rem 0;">

        <h2 style="font-size:1.15rem; font-weight:700; color:var(--t-dark); margin-bottom:1.25rem;">Transferencia directa</h2>
        <p style="color:var(--t-muted); font-size:0.9rem; margin-bottom:1rem;">Estos datos se mostrarán en el carrito cuando el cliente elija pagar por transferencia.</p>

        <div class="form-t-group">
            <label for="transfer_alias">Alias</label>
            <input type="text" id="transfer_alias" name="transfer_alias" class="form-t-input"
                   value="<?= htmlspecialchars($settings['transfer_alias'] ?? '') ?>" placeholder="Ej: lume.co.mp">
        </div>

        <div class="form-t-group">
            <label for="transfer_cbu">CBU</label>
            <input type="text" id="transfer_cbu" name="transfer_cbu" class="form-t-input"
                   value="<?= htmlspecialchars($settings['transfer_cbu'] ?? '') ?>" placeholder="22 dígitos" maxlength="22">
        </div>

        <div class="form-t-group">
            <label for="transfer_titular">Titular</label>
            <input type="text" id="transfer_titular" name="transfer_titular" class="form-t-input"
                   value="<?= htmlspecialchars($settings['transfer_titular'] ?? '') ?>" placeholder="Ej: Juan Pérez">
        </div>

        <div class="platform-form-actions">
            <button type="submit" class="btn-t btn-t-primary">Guardar Configuración</button>
            <a href="<?= PLATFORM_PAGES_URL ?>/dashboard.php" class="btn-t btn-t-secondary">Volver al Dashboard</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="platform-card">
    <a href="<?= PLATFORM_PAGES_URL ?>/dashboard.php" class="btn-t btn-t-secondary">Volver al Dashboard</a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/_inc/footer.php'; ?>
