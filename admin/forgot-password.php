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
$primaryColor = $shopSettings['primary_color'] ?? '#FF6B35';

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 3rem;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: <?= htmlspecialchars($primaryColor) ?>;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: <?= htmlspecialchars($primaryColor) ?>;
        }
        
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background: <?= htmlspecialchars($primaryColor) ?>;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(224, 164, 206, 0.4);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
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
