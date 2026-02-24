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
 * @param string|null $outError Mensaje de error si falla (por referencia)
 * @return bool True si se actualizó correctamente
 */
function updateShopSettings($data, &$outError = null) {
    // Campos básicos que siempre existen
    $baseFields = [
        'shop_name', 'shop_logo', 'whatsapp_number', 'whatsapp_message',
        'address', 'instagram', 'facebook', 'email', 'phone', 'description', 'primary_color'
    ];
    
    // Campos adicionales que pueden no existir si no se ejecutaron las migraciones
    $optionalFields = [
        'footer_description', 'footer_copyright', 'creation_year',
        'transfer_alias', 'transfer_cbu', 'transfer_titular',
        'mercadopago_access_token', 'mercadopago_public_key', 'mercadopago_test_mode',
        'telegram_bot_token', 'telegram_chat_id', 'telegram_enabled'
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
        $outError = 'No hay campos para actualizar';
        error_log('updateShopSettings: No hay campos para actualizar');
        return false;
    }
    
    $sql = "UPDATE `shop_settings` SET " . implode(', ', $updateFields);
    if (defined('CURRENT_STORE_ID') && CURRENT_STORE_ID > 0) {
        $params['__store_id'] = (int) CURRENT_STORE_ID;
        $sql .= " WHERE store_id = :__store_id";
    } else {
        $params['id'] = 1;
        $sql .= " WHERE `id` = :id";
    }
    
    try {
        $pdo = getDB();
        if (!$pdo) {
            $outError = 'Error de conexión a la base de datos';
            error_log('updateShopSettings: getDB retornó null');
            return false;
        }
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        if (!$result) {
            $err = $stmt->errorInfo();
            $outError = 'Error SQL: ' . ($err[2] ?? 'desconocido');
            error_log('updateShopSettings: execute falló - ' . $outError);
            error_log('updateShopSettings: SQL: ' . $sql);
            return false;
        }
        return true;
    } catch (PDOException $e) {
        $outError = 'Error: ' . $e->getMessage();
        error_log('updateShopSettings: PDOException: ' . $e->getMessage());
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
