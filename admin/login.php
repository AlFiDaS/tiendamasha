<?php
/**
 * Página de login del panel administrativo
 */
require_once '../config.php';
require_once '../helpers/shop-settings.php';

startSecureSession();

// Obtener configuración de la tienda
$shopSettings = getShopSettings();
$shopName = $shopSettings['shop_name'] ?? SITE_NAME;
$primaryColor = '#5672E1'; // Color fijo del panel admin

$error = '';

// Si ya está autenticado, redirigir al dashboard o a la URL indicada
if (isAuthenticated()) {
    $redirect = $_GET['redirect'] ?? '';
    if ($redirect && preg_match('/^[a-z0-9\-_\.]+\.php$/i', $redirect)) {
        header('Location: ' . ADMIN_URL . '/' . $redirect);
    } else {
        header('Location: ' . ADMIN_URL . '/index.php');
    }
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting para login (más restrictivo)
    require_once '../helpers/security.php';
    $rateLimit = checkRateLimit('login_attempt', 5, 900); // 5 intentos cada 15 minutos
    
    if (!$rateLimit['allowed']) {
        $timeLeft = $rateLimit['reset_at'] - time();
        $minutesLeft = ceil($timeLeft / 60);
        $error = "Demasiados intentos de login. Por favor, espera {$minutesLeft} minuto(s) antes de intentar nuevamente.";
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Por favor, completa todos los campos';
        } else {
            if (login($username, $password)) {
                $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '';
                if ($redirect && preg_match('/^[a-z0-9\-_\.]+\.php$/i', $redirect)) {
                    header('Location: ' . ADMIN_URL . '/' . $redirect);
                } else {
                    header('Location: ' . ADMIN_URL . '/index.php');
                }
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
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
    <title>Login - Panel Administrativo <?= htmlspecialchars($shopName) ?></title>
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
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.95rem;
            margin: 0;
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
        
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-input-wrapper .password-input {
            width: 100%;
            padding-right: 45px;
        }
        
        .toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            color: #666;
            transition: color 0.3s;
            z-index: 10;
        }
        
        .toggle-password:hover {
            color: #333;
        }
        
        .toggle-password:focus {
            outline: none;
        }
        
        .toggle-password .eye-icon {
            width: 20px;
            height: 20px;
            transition: opacity 0.3s;
        }
        
        .toggle-password .eye-icon.hidden {
            display: none;
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(224, 164, 206, 0.4);
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?= htmlspecialchars($shopName) ?></h1>
            <p>Panel Administrativo</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php if (!empty($_GET['redirect']) && preg_match('/^[a-z0-9\-_\.]+\.php$/i', $_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-input-wrapper">
                    <input type="password" id="password" name="password" required class="password-input">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password', this)" tabindex="-1">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
        
        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="<?= ADMIN_URL ?>/forgot-password.php" style="color: #666; text-decoration: none; font-size: 0.9rem;">
                ¿Olvidaste tu contraseña?
            </a>
        </div>
    </div>
    
    <script>
    function togglePasswordVisibility(inputId, toggleElement) {
        const input = document.getElementById(inputId);
        const eyeIcon = toggleElement.querySelector('.eye-icon');
        
        if (input.type === 'password') {
            input.type = 'text';
            toggleElement.classList.add('active');
            // Cambiar a icono de ojo cerrado (ocultar)
            eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            `;
            eyeIcon.setAttribute('title', 'Ocultar contraseña');
        } else {
            input.type = 'password';
            toggleElement.classList.remove('active');
            // Cambiar a icono de ojo abierto (mostrar)
            eyeIcon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
            eyeIcon.setAttribute('title', 'Mostrar contraseña');
        }
    }
    </script>
</body>
</html>

