<?php
/**
 * Login de la plataforma Somos Tiendi
 */
require_once __DIR__ . '/../platform-config.php';
platformStartSession();

if (platformIsAuthenticated()) {
    header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php');
    exit;
}

$error = '';
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!platformValidateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Recargá la página.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $oldEmail = $email;

        if (empty($email) || empty($password)) {
            $error = 'Completá todos los campos';
        } else {
            $result = platformLogin($email, $password);
            if ($result['success']) {
                $redirect = $_GET['redirect'] ?? PLATFORM_PAGES_URL . '/dashboard.php';
                if (strpos($redirect, '/') !== 0 && strpos($redirect, PLATFORM_URL) !== 0) {
                    $redirect = PLATFORM_PAGES_URL . '/dashboard.php';
                }
                header('Location: ' . $redirect);
                exit;
            }
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Iniciar Sesión - Somos Tiendi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PLATFORM_PAGES_URL ?>/css/platform.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-brand">Somos Tiendi</div>
                <h1>Iniciá sesión</h1>
                <p>Accedé a tu panel para administrar tus tiendas</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-t alert-t-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= platformCSRFToken() ?>">

                <div class="form-t-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-t-input" required
                           placeholder="tu@email.com" value="<?= htmlspecialchars($oldEmail) ?>" autofocus>
                </div>

                <div class="form-t-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-t-input" required
                           placeholder="Tu contraseña">
                </div>

                <button type="submit" class="btn-t btn-t-primary btn-t-full" style="margin-top:0.5rem;">
                    Iniciar Sesión
                </button>
            </form>

            <div class="auth-footer">
                ¿No tenés cuenta? <a href="<?= PLATFORM_PAGES_URL ?>/register.php">Registrate gratis</a>
            </div>
        </div>
    </div>
</body>
</html>
