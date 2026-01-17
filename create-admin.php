<?php
/**
 * ============================================
 * CREAR USUARIO ADMIN
 * ============================================
 * Script para crear un usuario administrador en la base de datos
 * Uso web: Accede a create-admin.php desde el navegador
 * Uso CLI: php create-admin.php
 * ============================================
 */

require_once 'config.php';

// Detectar si es ejecución CLI o web
$isCLI = php_sapi_name() === 'cli';

// Si es web, procesar formulario
if (!$isCLI && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $error = '';
    $success = false;
    
    // Validaciones
    if (empty($username)) {
        $error = 'El nombre de usuario es requerido';
    } elseif (empty($password)) {
        $error = 'La contraseña es requerida';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido';
    } else {
        // Validar que el usuario no exista
        $existingUser = fetchOne(
            "SELECT id FROM admin_users WHERE username = :username",
            ['username' => $username]
        );
        
        if ($existingUser) {
            $error = "El usuario '$username' ya existe";
        } else {
            // Hashear contraseña e insertar
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $sql = "INSERT INTO admin_users (username, password, email) 
                        VALUES (:username, :password, :email)";
                
                $result = executeQuery($sql, [
                    'username' => $username,
                    'password' => $hashedPassword,
                    'email' => !empty($email) ? $email : null
                ]);
                
                if ($result) {
                    $success = true;
                } else {
                    $error = 'No se pudo crear el usuario';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Si es CLI, usar interfaz de consola
if ($isCLI) {
    // Función para obtener entrada desde consola
    function getInput($prompt) {
        echo $prompt;
        $line = trim(fgets(STDIN));
        return $line;
    }
    
    echo "\n";
    echo "============================================\n";
    echo "   CREAR USUARIO ADMINISTRADOR\n";
    echo "============================================\n\n";
    
    // Solicitar datos
    $username = getInput("Usuario: ");
    if (empty($username)) {
        echo "❌ Error: El nombre de usuario es requerido.\n";
        exit(1);
    }
    
    $password = getInput("Contraseña: ");
    if (empty($password)) {
        echo "❌ Error: La contraseña es requerida.\n";
        exit(1);
    }
    
    $confirmPassword = getInput("Confirmar contraseña: ");
    if ($password !== $confirmPassword) {
        echo "❌ Error: Las contraseñas no coinciden.\n";
        exit(1);
    }
    
    $email = getInput("Email (opcional): ");
    
    // Validar que el usuario no exista
    $existingUser = fetchOne(
        "SELECT id FROM admin_users WHERE username = :username",
        ['username' => $username]
    );
    
    if ($existingUser) {
        echo "❌ Error: El usuario '$username' ya existe.\n";
        exit(1);
    }
    
    // Validar email si se proporcionó
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "❌ Error: El email no es válido.\n";
        exit(1);
    }
    
    // Hashear contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar usuario
    try {
        $sql = "INSERT INTO admin_users (username, password, email) 
                VALUES (:username, :password, :email)";
        
        $result = executeQuery($sql, [
            'username' => $username,
            'password' => $hashedPassword,
            'email' => !empty($email) ? $email : null
        ]);
        
        if ($result) {
            echo "\n✅ Usuario admin creado exitosamente!\n\n";
            echo "Datos del usuario:\n";
            echo "  Usuario: $username\n";
            echo "  Email: " . ($email ?: 'No especificado') . "\n\n";
            echo "Ahora puedes iniciar sesión en: " . ADMIN_URL . "/login.php\n\n";
        } else {
            echo "❌ Error: No se pudo crear el usuario.\n";
            exit(1);
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    exit(0);
}

// Interfaz web (HTML)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario Admin - LUME</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        .optional {
            color: #999;
            font-size: 12px;
            font-weight: normal;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Crear Usuario Admin</h1>
        <p class="subtitle">Registra un nuevo administrador para el panel</p>
        
        <?php if (isset($error) && !empty($error)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success) && $success): ?>
            <div class="alert alert-success">
                ✅ Usuario admin creado exitosamente!
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="<?php echo ADMIN_URL; ?>/login.php" class="btn" style="text-decoration: none; display: inline-block;">
                    Ir al Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Usuario <span style="color: red;">*</span></label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña <span style="color: red;">*</span></label>
                    <input type="password" id="password" name="password" required 
                           minlength="6" placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña <span style="color: red;">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="optional">(opcional)</span></label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn">Crear Usuario Admin</button>
            </form>
        <?php endif; ?>
        
        <div class="login-link">
            <a href="<?php echo ADMIN_URL; ?>/login.php">← Volver al Login</a>
        </div>
    </div>
</body>
</html>
