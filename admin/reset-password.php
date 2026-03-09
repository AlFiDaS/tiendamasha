<?php
/**
 * Página para restablecer contraseña con token
 */
require_once '../config.php';
require_once '../helpers/shop-settings.php';

if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';

startSecureSession();

// Obtener configuración de la tienda
$shopSettings = getShopSettings();
$shopName = $shopSettings['shop_name'] ?? SITE_NAME;
$primaryColor = '#5672E1'; // Color fijo del panel admin

$error = '';
$success = false;
$token = sanitize($_GET['token'] ?? '');
$validToken = false;
$user = null;

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

// Validar token
if (!empty($token)) {
    // Primero verificar que los campos existan en la tabla
    try {
        $user = fetchOne(
            "SELECT id, username, password_reset_token, password_reset_expires 
             FROM admin_users 
             WHERE password_reset_token = :token 
             AND password_reset_expires IS NOT NULL
             AND password_reset_expires > :now
             LIMIT 1",
            [
                'token' => $token,
                'now' => date('Y-m-d H:i:s')
            ]
        );
        
        if ($user) {
            $validToken = true;
        } else {
            // Verificar si el token existe pero expiró
            $expiredUser = fetchOne(
                "SELECT id, password_reset_token, password_reset_expires 
                 FROM admin_users 
                 WHERE password_reset_token = :token 
                 LIMIT 1",
                ['token' => $token]
            );
            
            if ($expiredUser) {
                $error = 'El enlace de recuperación ha expirado. Por favor, solicita uno nuevo.';
            } else {
                $error = 'El enlace de recuperación no es válido. Por favor, solicita uno nuevo.';
            }
        }
    } catch (Exception $e) {
        // Si hay un error de SQL, probablemente los campos no existen
        error_log('Error al validar token de reset: ' . $e->getMessage());
        $error = 'Error en la configuración del sistema. Por favor, contacta al administrador. Los campos de recuperación de contraseña pueden no estar creados en la base de datos.';
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword)) {
        $error = 'La contraseña es requerida';
    } elseif (strlen($newPassword) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // Actualizar contraseña y limpiar token
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updateSql = "UPDATE admin_users 
                     SET password = :password, 
                         password_reset_token = NULL, 
                         password_reset_expires = NULL 
                     WHERE id = :id";
        
        try {
            $result = executeQuery($updateSql, [
                'password' => $hashedPassword,
                'id' => $user['id']
            ]);
            
            if ($result) {
                $success = true;
            } else {
                $error = 'No se pudo actualizar la contraseña. Por favor, intenta nuevamente.';
            }
        } catch (Exception $e) {
            error_log('Error al resetear contraseña: ' . $e->getMessage());
            $error = 'Ocurrió un error. Por favor, intenta más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Panel Administrativo <?= htmlspecialchars($shopName) ?></title>
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
        .password-input-wrapper { position: relative; }
        .form-group input {
            width: 100%; padding: 0.65rem 0.85rem;
            border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: 0.95rem; font-family: inherit; color: #334155;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .password-input-wrapper .password-input { padding-right: 45px; }
        .form-group input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px #eef2ff; }
        .toggle-password {
            position: absolute; right: 8px; top: 50%;
            transform: translateY(-50%); background: none; border: none;
            cursor: pointer; padding: 0.5rem; display: flex;
            align-items: center; justify-content: center;
            color: #94a3b8; transition: color 0.2s; z-index: 10;
        }
        .toggle-password:hover { color: #334155; }
        .toggle-password:focus { outline: none; }
        .toggle-password .eye-icon { width: 20px; height: 20px; }
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
            <p>Restablecer Contraseña</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <strong>¡Contraseña actualizada!</strong><br><br>
                Tu contraseña ha sido restablecida correctamente. Ahora puedes iniciar sesión con tu nueva contraseña.
            </div>
            <div class="back-link">
                <a href="<?= ADMIN_URL ?>/login.php">Ir al Login →</a>
            </div>
        <?php elseif ($validToken && $user): ?>
            <p style="color: #666; margin-bottom: 1.5rem; text-align: center;">
                Hola, <strong><?= htmlspecialchars($user['username']) ?></strong>. Ingresa tu nueva contraseña.
            </p>
            
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña</label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            required 
                            autofocus
                            placeholder="Mínimo 6 caracteres"
                            minlength="6"
                            class="password-input"
                        >
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('new_password', this)" tabindex="-1">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            placeholder="Confirma tu contraseña"
                            class="password-input"
                        >
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)" tabindex="-1">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Restablecer Contraseña</button>
            </form>
            
            <div class="back-link">
                <a href="<?= ADMIN_URL ?>/login.php">← Volver al Login</a>
            </div>
        <?php elseif (empty($token)): ?>
            <div class="alert alert-error">
                ❌ No se proporcionó un token válido.
            </div>
            <div class="back-link">
                <a href="<?= ADMIN_URL ?>/forgot-password.php">Solicitar recuperación de contraseña</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    function togglePasswordVisibility(inputId, toggleElement) {
        const input = document.getElementById(inputId);
        const eyeIcon = toggleElement.querySelector('.eye-icon');
        
        if (input.type === 'password') {
            input.type = 'text';
            toggleElement.classList.add('active');
            eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            `;
        } else {
            input.type = 'password';
            toggleElement.classList.remove('active');
            eyeIcon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
        }
    }
    </script>
</body>
</html>
