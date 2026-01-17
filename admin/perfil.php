<?php
/**
 * Página de perfil del administrador
 */
$pageTitle = 'Mi Perfil';
require_once '../config.php';

// Necesitamos autenticación
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}
require_once '../helpers/auth.php';
startSecureSession();
requireAuth();

$error = '';
$success = '';
$currentUser = getCurrentUser();

if (!$currentUser) {
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newUsername = sanitize($_POST['username'] ?? '');
        $newEmail = sanitize($_POST['email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Obtener usuario actual con contraseña
        $user = fetchOne(
            "SELECT * FROM admin_users WHERE id = :id",
            ['id' => $currentUser['id']]
        );
        
        if (!$user) {
            $error = 'Usuario no encontrado';
        } elseif (empty($currentPassword)) {
            $error = 'Debes ingresar tu contraseña actual para confirmar los cambios';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = 'La contraseña actual es incorrecta';
        } elseif (empty($newUsername)) {
            $error = 'El nombre de usuario es requerido';
        } else {
            // Validar email si se proporciona
            if (!empty($newEmail) && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'El email no es válido';
            } else {
                // Verificar si el nuevo username ya existe (excluyendo el usuario actual)
                if ($newUsername !== $user['username']) {
                    $existingUser = fetchOne(
                        "SELECT id FROM admin_users WHERE username = :username AND id != :id",
                        ['username' => $newUsername, 'id' => $currentUser['id']]
                    );
                    
                    if ($existingUser) {
                        $error = "El nombre de usuario '$newUsername' ya está en uso";
                    }
                }
                
                // Validar nueva contraseña si se proporciona
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 6) {
                        $error = 'La nueva contraseña debe tener al menos 6 caracteres';
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = 'Las contraseñas nuevas no coinciden';
                    }
                }
                
                // Si no hay errores, actualizar
                if (empty($error)) {
                    $updateFields = [];
                    $updateParams = ['id' => $currentUser['id']];
                    
                    // Actualizar username
                    if ($newUsername !== $user['username']) {
                        $updateFields[] = "username = :username";
                        $updateParams['username'] = $newUsername;
                    }
                    
                    // Actualizar email
                    if ($newEmail !== ($user['email'] ?? '')) {
                        $updateFields[] = "email = :email";
                        $updateParams['email'] = !empty($newEmail) ? $newEmail : null;
                    }
                    
                    // Actualizar contraseña si se proporcionó
                    if (!empty($newPassword)) {
                        $updateFields[] = "password = :password";
                        $updateParams['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                    }
                    
                    if (!empty($updateFields)) {
                        $sql = "UPDATE admin_users SET " . implode(', ', $updateFields) . " WHERE id = :id";
                        
                        try {
                            $result = executeQuery($sql, $updateParams);
                            
                            if ($result) {
                                // Actualizar sesión si cambió el username
                                if ($newUsername !== $user['username']) {
                                    $_SESSION['admin_username'] = $newUsername;
                                }
                                
                                // Recargar datos del usuario
                                $currentUser = getCurrentUser();
                                
                                $success = 'Perfil actualizado correctamente';
                                $_SESSION['success_message'] = $success;
                                
                                // Si cambió la contraseña, redirigir al login
                                if (!empty($newPassword)) {
                                    $_SESSION['success_message'] = 'Perfil actualizado. Por favor, inicia sesión nuevamente con tu nueva contraseña.';
                                    header('Location: ' . ADMIN_URL . '/logout.php');
                                    exit;
                                }
                            } else {
                                $error = 'No se pudo actualizar el perfil';
                            }
                        } catch (Exception $e) {
                            $error = 'Error al actualizar: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// Recargar usuario actualizado
$currentUser = getCurrentUser();

require_once '_inc/header.php';
?>

<div class="admin-content">
    <h2>Mi Perfil</h2>
    <p style="color: #666; margin-bottom: 2rem;">Actualiza tu información personal y contraseña</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div class="form-group">
            <label for="username">Nombre de Usuario <span style="color: red;">*</span></label>
            <input 
                type="text" 
                id="username" 
                name="username" 
                value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" 
                required
                placeholder="Ingresa tu nombre de usuario"
            >
            <small>Este será tu nombre de usuario para iniciar sesión</small>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" 
                placeholder="tu@email.com"
            >
            <small>Opcional: Agrega tu email para recuperar tu cuenta</small>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <h3 style="margin-bottom: 1rem; color: #333;">Cambiar Contraseña</h3>
        <p style="color: #666; margin-bottom: 1.5rem; font-size: 0.9rem;">
            Deja los campos de contraseña vacíos si no deseas cambiarla
        </p>
        
        <div class="form-group">
            <label for="new_password">Nueva Contraseña</label>
            <div class="password-input-wrapper">
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
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
            <small>Mínimo 6 caracteres. Déjalo vacío si no quieres cambiarla</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmar Nueva Contraseña</label>
            <div class="password-input-wrapper">
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="Confirma la nueva contraseña"
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
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <div class="form-group">
            <label for="current_password">
                Contraseña Actual <span style="color: red;">*</span>
                <span style="font-weight: normal; color: #666; font-size: 0.9rem;">(requerida para confirmar cambios)</span>
            </label>
            <div class="password-input-wrapper">
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    required
                    placeholder="Ingresa tu contraseña actual para confirmar"
                    class="password-input"
                >
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('current_password', this)" tabindex="-1">
                    <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
            <small style="color: #d32f2f;">
                ⚠️ Por seguridad, debes ingresar tu contraseña actual para guardar cualquier cambio
            </small>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary">
                💾 Guardar Cambios
            </button>
            <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-secondary">
                ↩️ Cancelar
            </a>
        </div>
    </form>
    
    <div style="margin-top: 3rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #2196f3;">
        <h3 style="margin-bottom: 1rem; color: #333; font-size: 1.1rem;">ℹ️ Información de la Cuenta</h3>
        <div style="color: #666; line-height: 1.8;">
            <p><strong>ID de Usuario:</strong> <?= htmlspecialchars($currentUser['id']) ?></p>
            <p><strong>Fecha de Registro:</strong> <?= date('d/m/Y H:i', strtotime($currentUser['created_at'] ?? 'now')) ?></p>
            <?php if (!empty($currentUser['last_login'])): ?>
                <p><strong>Último Acceso:</strong> <?= date('d/m/Y H:i', strtotime($currentUser['last_login'])) ?></p>
            <?php else: ?>
                <p><strong>Último Acceso:</strong> Nunca</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    hr {
        margin: 2rem 0;
        border: none;
        border-top: 1px solid #e0e0e0;
    }
    
    .form-group small {
        display: block;
        margin-top: 0.5rem;
        color: #666;
        font-size: 0.875rem;
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
    
    @media (max-width: 768px) {
        .admin-content {
            padding: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        button.btn,
        a.btn {
            width: 100%;
        }
    }
</style>

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

<?php require_once '_inc/footer.php'; ?>
