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
    // Campos básicos que siempre existen
    $baseFields = [
        'shop_name', 'shop_logo', 'whatsapp_number', 'whatsapp_message',
        'address', 'instagram', 'facebook', 'email', 'phone', 'description', 'primary_color'
    ];
    
    // Campos adicionales que pueden no existir si no se ejecutaron las migraciones
    $optionalFields = [
        'footer_description', 'footer_copyright', 'creation_year'
    ];
    
    // Obtener columnas existentes de la tabla para verificar qué campos podemos actualizar
    $existingColumns = [];
    try {
        $pdo = getDB();
        if ($pdo) {
            $stmt = $pdo->query("SHOW COLUMNS FROM `shop_settings`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $existingColumns = array_map('strtolower', $columns);
        }
    } catch (Exception $e) {
        error_log('updateShopSettings: Error al obtener columnas: ' . $e->getMessage());
    }
    
    // Determinar qué campos están permitidos basándose en lo que existe en la tabla
    $allowedFields = array_merge($baseFields, $optionalFields);
    $updateFields = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            // Si el campo es opcional, verificar que existe en la tabla
            if (in_array($field, $optionalFields) && !empty($existingColumns)) {
                $fieldLower = strtolower($field);
                if (!in_array($fieldLower, $existingColumns)) {
                    // Saltar este campo si no existe en la tabla
                    continue;
                }
            }
            
            // Escapar el nombre del campo para evitar problemas con caracteres especiales
            $fieldEscaped = '`' . str_replace('`', '``', $field) . '`';
            $updateFields[] = "{$fieldEscaped} = :{$field}";
            $params[$field] = $data[$field] !== null && $data[$field] !== '' ? $data[$field] : null;
        }
    }
    
    if (empty($updateFields)) {
        error_log('updateShopSettings: No hay campos para actualizar');
        return false;
    }
    
    $params['id'] = 1;
    $sql = "UPDATE `shop_settings` SET " . implode(', ', $updateFields) . " WHERE `id` = :id";
    
    try {
        $result = executeQuery($sql, $params);
        if ($result === false) {
            error_log('updateShopSettings: executeQuery retornó false');
            error_log('updateShopSettings: SQL: ' . $sql);
            error_log('updateShopSettings: Params: ' . print_r($params, true));
        }
        return $result !== false;
    } catch (Exception $e) {
        error_log('updateShopSettings: Exception: ' . $e->getMessage());
        error_log('updateShopSettings: SQL: ' . $sql);
        error_log('updateShopSettings: Params: ' . print_r($params, true));
        return false;
    }
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
