<?php
/**
 * Script para verificar y crear los campos de recuperación de contraseña
 */
require_once '../config.php';

// Necesitamos autenticación
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';
startSecureSession();

// Solo permitir acceso si está autenticado o si se pasa un parámetro de seguridad
$securityKey = $_GET['key'] ?? '';
$allowedKey = 'setup2024'; // Cambia esto por una clave segura

if (!isAuthenticated() && $securityKey !== $allowedKey) {
    die('Acceso denegado. Este script solo puede ejecutarse con la clave correcta o si estás autenticado.');
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Campos de Recuperación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        pre {
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Verificación de Campos de Recuperación de Contraseña</h1>
    
    <?php
    try {
        // Verificar si los campos existen
        $columns = fetchAll("SHOW COLUMNS FROM admin_users LIKE 'password_reset%'");
        
        $hasToken = false;
        $hasExpires = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'password_reset_token') {
                $hasToken = true;
            }
            if ($column['Field'] === 'password_reset_expires') {
                $hasExpires = true;
            }
        }
        
        echo '<div class="result info">';
        echo '<h2>Estado Actual:</h2>';
        echo '<ul>';
        echo '<li>Campo password_reset_token: ' . ($hasToken ? '✅ Existe' : '❌ No existe') . '</li>';
        echo '<li>Campo password_reset_expires: ' . ($hasExpires ? '✅ Existe' : '❌ No existe') . '</li>';
        echo '</ul>';
        echo '</div>';
        
        if (!$hasToken || !$hasExpires) {
            echo '<div class="result error">';
            echo '<h2>❌ Campos Faltantes</h2>';
            echo '<p>Los campos necesarios no existen en la tabla admin_users.</p>';
            echo '<p><strong>Ejecuta este SQL en phpMyAdmin o en la consola MySQL:</strong></p>';
            echo '<pre>';
            echo "ALTER TABLE `admin_users` \n";
            if (!$hasToken) {
                echo "ADD COLUMN `password_reset_token` VARCHAR(255) NULL DEFAULT NULL AFTER `email`,\n";
            }
            if (!$hasExpires) {
                echo "ADD COLUMN `password_reset_expires` DATETIME NULL DEFAULT NULL AFTER `password_reset_token`;\n";
            }
            echo "\n-- Agregar índice\n";
            echo "ALTER TABLE `admin_users` \n";
            echo "ADD KEY `idx_reset_token` (`password_reset_token`);\n";
            echo '</pre>';
            
            // Intentar crear automáticamente si el usuario lo solicita
            if (isset($_GET['auto_create']) && ($_GET['auto_create'] === '1')) {
                echo '<h3>Intentando crear campos automáticamente...</h3>';
                
                try {
                    if (!$hasToken) {
                        executeQuery("ALTER TABLE `admin_users` ADD COLUMN `password_reset_token` VARCHAR(255) NULL DEFAULT NULL AFTER `email`");
                        echo '<div class="result success">✅ Campo password_reset_token creado</div>';
                    }
                    
                    if (!$hasExpires) {
                        executeQuery("ALTER TABLE `admin_users` ADD COLUMN `password_reset_expires` DATETIME NULL DEFAULT NULL AFTER `password_reset_token`");
                        echo '<div class="result success">✅ Campo password_reset_expires creado</div>';
                    }
                    
                    // Verificar si el índice existe
                    $indexes = fetchAll("SHOW INDEXES FROM admin_users WHERE Key_name = 'idx_reset_token'");
                    if (empty($indexes)) {
                        executeQuery("ALTER TABLE `admin_users` ADD KEY `idx_reset_token` (`password_reset_token`)");
                        echo '<div class="result success">✅ Índice idx_reset_token creado</div>';
                    }
                    
                    echo '<div class="result success"><strong>✅ ¡Campos creados exitosamente!</strong> Ahora puedes usar la recuperación de contraseña.</div>';
                } catch (Exception $e) {
                    echo '<div class="result error">❌ Error al crear campos: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    echo '<p>Por favor, ejecuta el SQL manualmente mostrado arriba.</p>';
                }
            } else {
                echo '<p><a href="?key=' . urlencode($securityKey) . '&auto_create=1" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;">🔧 Crear Campos Automáticamente</a></p>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="result success">';
            echo '<h2>✅ Todo Correcto</h2>';
            echo '<p>Los campos necesarios existen en la base de datos. El sistema de recuperación de contraseña debería funcionar correctamente.</p>';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="result error">';
        echo '<h2>❌ Error</h2>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>
    
    <hr style="margin: 30px 0;">
    <p><a href="login.php">← Volver al Login</a></p>
</body>
</html>
