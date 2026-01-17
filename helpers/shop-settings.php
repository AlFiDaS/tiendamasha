<?php
/**
 * ============================================
 * HELPER: Configuración de la Tienda
 * ============================================
 * Funciones para obtener y gestionar la configuración de la tienda
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Obtener configuración de la tienda
 * @return array Configuración de la tienda
 */
function getShopSettings() {
    $settings = fetchOne("SELECT * FROM shop_settings WHERE id = 1 LIMIT 1");
    
    if (!$settings) {
        // Si no existe, crear registro por defecto
        try {
            executeQuery(
                "INSERT INTO shop_settings (id, shop_name) VALUES (1, :shop_name)",
                ['shop_name' => defined('SITE_NAME') ? SITE_NAME : 'LUME - Velas Artesanales']
            );
            $settings = fetchOne("SELECT * FROM shop_settings WHERE id = 1 LIMIT 1");
        } catch (Exception $e) {
            error_log('Error al crear configuración de tienda: ' . $e->getMessage());
        }
    }
    
    return $settings ?: [];
}

/**
 * Actualizar configuración de la tienda
 * @param array $data Datos a actualizar
 * @return bool True si se actualizó correctamente
 */
function updateShopSettings($data) {
    $allowedFields = [
        'shop_name', 'shop_logo', 'whatsapp_number', 'whatsapp_message',
        'address', 'instagram', 'facebook', 'email', 'phone', 'description', 'primary_color'
    ];
    
    $updateFields = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "`{$field}` = :{$field}";
            $params[$field] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        return false;
    }
    
    $params['id'] = 1;
    $sql = "UPDATE shop_settings SET " . implode(', ', $updateFields) . " WHERE id = :id";
    
    $result = executeQuery($sql, $params);
    return $result !== false;
}

/**
 * Obtener valor específico de configuración
 * @param string $key Clave de configuración
 * @param mixed $default Valor por defecto si no existe
 * @return mixed Valor de la configuración
 */
function getShopSetting($key, $default = null) {
    $settings = getShopSettings();
    return $settings[$key] ?? $default;
}
