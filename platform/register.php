<?php
/**
 * Registro de usuarios en la plataforma Somos Tiendi
 */
require_once __DIR__ . '/../platform-config.php';
platformStartSession();

if (platformIsAuthenticated()) {
    header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php');
    exit;
}

$error = '';
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!platformValidateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Recargá la página.';
    } else {
        $name = platformSanitize($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $old = ['name' => $name, 'email' => $email];

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Todos los campos son obligatorios';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ingresá un email válido';
        } elseif ($password !== $confirm) {
            $error = 'Las contraseñas no coinciden';
        } else {
            $result = platformRegister($name, $email, $password);
            if ($result['success']) {
                header('Location: ' . PLATFORM_PAGES_URL . '/dashboard.php');
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
    <title>Crear Cuenta - Somos Tiendi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= PLATFORM_PAGES_URL ?>/css/platform.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-brand">Somos Tiendi</div>
                <h1>Creá tu cuenta</h1>
                <p>Registrate para crear y administrar tus tiendas online</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-t alert-t-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= platformCSRFToken() ?>">

                <div class="form-t-group">
                    <label for="name">Nombre completo</label>
                    <input type="text" id="name" name="name" class="form-t-input" required
                           placeholder="Tu nombre" value="<?= htmlspecialchars($old['name']) ?>" autofocus>
                </div>

                <div class="form-t-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-t-input" required
                           placeholder="tu@email.com" value="<?= htmlspecialchars($old['email']) ?>">
                </div>

                <div class="form-t-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-t-input" required
                           placeholder="Mínimo 6 caracteres" minlength="6">
                </div>

                <div class="form-t-group">
                    <label for="confirm_password">Confirmar contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-t-input" required
                           placeholder="Repetí tu contraseña">
                </div>

                <button type="submit" class="btn-t btn-t-primary btn-t-full" style="margin-top:0.5rem;">
                    Crear Cuenta
                </button>
            </form>

            <div class="auth-footer">
                ¿Ya tenés cuenta? <a href="<?= PLATFORM_PAGES_URL ?>/login.php">Iniciá sesión</a>
            </div>
        </div>
    </div>
</body>
</html>
