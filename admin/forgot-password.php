<?php
/**
 * Página para solicitar recuperación de contraseña
 */
require_once '../config.php';
require_once '../helpers/email.php';
require_once '../helpers/shop-settings.php';

startSecureSession();

// Obtener configuración de la tienda
$shopSettings = getShopSettings();
$shopName = $shopSettings['shop_name'] ?? SITE_NAME;
$primaryColor = '#5672E1'; // Color fijo del panel admin

$error = '';
$success = false;

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Por favor, ingresa tu email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido';
    } else {
        // Buscar usuario por email
        $user = fetchOne(
            "SELECT id, username, email FROM admin_users WHERE email = :email LIMIT 1",
            ['email' => $email]
        );
        
        if (!$user || empty($user['email'])) {
            // Por seguridad, no revelamos si el email existe o no
            $success = true; // Mostrar mensaje de éxito aunque no exista
        } else {
            // Generar token único
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Guardar token en la base de datos
            $updateSql = "UPDATE admin_users 
                         SET password_reset_token = :token, 
                             password_reset_expires = :expires 
                         WHERE id = :id";
            
            try {
                executeQuery($updateSql, [
                    'token' => $resetToken,
                    'expires' => $expiresAt,
                    'id' => $user['id']
                ]);
                
                // Enviar email
                if (sendPasswordResetEmail($user['email'], $user['username'], $resetToken)) {
                    $success = true;
                } else {
                    $error = 'No se pudo enviar el email. Por favor, intenta más tarde o contacta al administrador.';
                }
            } catch (Exception $e) {
                error_log('Error al generar token de reset: ' . $e->getMessage());
                $error = 'Ocurrió un error. Por favor, intenta más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Panel Administrativo <?= htmlspecialchars($shopName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a; min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 2rem;
        }
        .login-container {
            background: #fff; border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 2.5rem; max-width: 400px; width: 100%;
        }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h1 { color: #0f172a; font-size: 1.5rem; font-weight: 800; margin-bottom: 0.35rem; }
        .login-header p { color: #94a3b8; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; margin-bottom: 0.4rem;
            color: #334155; font-weight: 600; font-size: 0.88rem;
        }
        .form-group input {
            width: 100%; padding: 0.65rem 0.85rem;
            border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: 0.95rem; font-family: inherit; color: #334155;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px #eef2ff; }
        .btn-primary {
            width: 100%; padding: 0.7rem;
            background: #6366f1; color: white;
            border: none; border-radius: 10px;
            font-size: 0.95rem; font-weight: 700;
            cursor: pointer; font-family: inherit; transition: background 0.2s;
        }
        .btn-primary:hover { background: #4f46e5; }
        .alert { padding: 0.85rem 1rem; border-radius: 10px; margin-bottom: 1.25rem; font-size: 0.88rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: <?= htmlspecialchars($primaryColor) ?>;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🌸 <?= htmlspecialchars($shopName) ?></h1>
            <p>Recuperar Contraseña</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <strong>¡Email enviado!</strong><br><br>
                Si el email está registrado en el sistema, recibirás un enlace para restablecer tu contraseña.<br><br>
                <small>Revisa tu bandeja de entrada y la carpeta de spam. El enlace expirará en 1 hora.</small>
            </div>
            <div class="back-link">
                <a href="<?= ADMIN_URL ?>/login.php">← Volver al Login</a>
            </div>
        <?php else: ?>
            <p style="color: #666; margin-bottom: 1.5rem; text-align: center;">
                Ingresa tu email y te enviaremos un enlace para restablecer tu contraseña.
            </p>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        autofocus
                        placeholder="tu@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>
                
                <button type="submit" class="btn-primary">Enviar Enlace de Recuperación</button>
            </form>
            
            <div class="back-link">
                <a href="<?= ADMIN_URL ?>/login.php">← Volver al Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
