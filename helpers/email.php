<?php
/**
 * ============================================
 * HELPER: Envío de Emails
 * ============================================
 * Funciones para enviar emails desde el sistema
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Enviar email de recuperación de contraseña
 * @param string $to Email del destinatario
 * @param string $username Nombre de usuario
 * @param string $resetToken Token para recuperar contraseña
 * @return bool True si se envió correctamente
 */
function sendPasswordResetEmail($to, $username, $resetToken) {
    $resetUrl = BASE_URL . '/admin/reset-password.php?token=' . urlencode($resetToken);
    
    $subject = 'Recuperación de Contraseña - ' . SITE_NAME;
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background: #f9f9f9;
            }
            .header {
                background: linear-gradient(135deg, #e0a4ce 0%, #d89bc0 100%);
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 8px 8px 0 0;
            }
            .content {
                background: white;
                padding: 30px;
                border-radius: 0 0 8px 8px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background: #e0a4ce;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                margin: 20px 0;
            }
            .button:hover {
                background: #d89bc0;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                color: #666;
                font-size: 12px;
            }
            .warning {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 15px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌸 LUME</h1>
                <p>Recuperación de Contraseña</p>
            </div>
            <div class='content'>
                <h2>Hola, {$username}</h2>
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta de administrador.</p>
                
                <p>Para crear una nueva contraseña, haz clic en el siguiente botón:</p>
                
                <div style='text-align: center;'>
                    <a href='{$resetUrl}' class='button'>Restablecer Contraseña</a>
                </div>
                
                <p>O copia y pega este enlace en tu navegador:</p>
                <p style='word-break: break-all; color: #666;'>{$resetUrl}</p>
                
                <div class='warning'>
                    <strong>⚠️ Importante:</strong>
                    <ul>
                        <li>Este enlace expirará en 1 hora</li>
                        <li>Si no solicitaste este cambio, ignora este email</li>
                        <li>Tu contraseña actual seguirá siendo válida hasta que la cambies</li>
                    </ul>
                </div>
                
                <p>Si tienes problemas con el botón, copia y pega el enlace completo en tu navegador.</p>
            </div>
            <div class='footer'>
                <p>Este es un email automático, por favor no respondas.</p>
                <p>&copy; " . date('Y') . " " . SITE_NAME . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers del email
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SITE_NAME . ' <noreply@' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . '>',
        'Reply-To: noreply@' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'),
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Intentar enviar el email
    $result = @mail($to, $subject, $message, implode("\r\n", $headers));
    
    return $result;
}

/**
 * Enviar email simple (texto plano)
 * @param string $to Email del destinatario
 * @param string $subject Asunto del email
 * @param string $message Mensaje (texto plano)
 * @return bool True si se envió correctamente
 */
function sendEmail($to, $subject, $message) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: ' . SITE_NAME . ' <noreply@' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . '>',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return @mail($to, $subject, $message, implode("\r\n", $headers));
}
