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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 2.5rem;
            max-width: 400px;
            width: 100%;
        }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h1 {
            color: #0f172a;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.35rem;
        }
        .login-header p { color: #94a3b8; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; margin-bottom: 0.4rem;
            color: #334155; font-weight: 600; font-size: 0.88rem;
        }
        .form-group input {
            width: 100%; padding: 0.65rem 0.85rem;
            border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: 0.95rem; font-family: inherit;
            color: #334155; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus {
            outline: none; border-color: #6366f1;
            box-shadow: 0 0 0 3px #eef2ff;
        }
        .password-input-wrapper { position: relative; }
        .password-input-wrapper .password-input { width: 100%; padding-right: 45px; }
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
        .btn-login {
            width: 100%; padding: 0.7rem;
            background: #6366f1; color: white;
            border: none; border-radius: 10px;
            font-size: 0.95rem; font-weight: 700;
            cursor: pointer; font-family: inherit;
            transition: background 0.2s;
        }
        .btn-login:hover { background: #4f46e5; }
        .alert-error {
            background: #fee2e2; color: #991b1b;
            padding: 0.85rem 1rem; border-radius: 10px;
            margin-bottom: 1.25rem; border: 1px solid #fecaca;
            font-size: 0.88rem;
        }
        .login-footer-link {
            color: #94a3b8; text-decoration: none;
            font-size: 0.85rem; transition: color 0.2s;
        }
        .login-footer-link:hover { color: #6366f1; }
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
        
        <div style="text-align: center; margin-top: 1.25rem;">
            <a href="<?= ADMIN_URL ?>/forgot-password.php" class="login-footer-link">
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

