-- ============================================
-- MIGRACIÓN: Configuración de la Tienda
-- ============================================
-- Agrega la tabla shop_settings para configurar información de la tienda
-- ============================================

-- Crear tabla shop_settings
CREATE TABLE IF NOT EXISTS `shop_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `shop_name` VARCHAR(255) DEFAULT NULL COMMENT 'Nombre de la tienda',
  `shop_logo` VARCHAR(500) DEFAULT NULL COMMENT 'Ruta del logo de la tienda',
  `whatsapp_number` VARCHAR(50) DEFAULT NULL COMMENT 'Número de WhatsApp',
  `whatsapp_message` TEXT DEFAULT NULL COMMENT 'Mensaje predefinido para WhatsApp',
  `address` TEXT DEFAULT NULL COMMENT 'Dirección del local',
  `instagram` VARCHAR(255) DEFAULT NULL COMMENT 'URL de Instagram',
  `facebook` VARCHAR(255) DEFAULT NULL COMMENT 'URL de Facebook',
  `email` VARCHAR(255) DEFAULT NULL COMMENT 'Email de contacto',
  `phone` VARCHAR(50) DEFAULT NULL COMMENT 'Teléfono de contacto',
  `description` TEXT DEFAULT NULL COMMENT 'Descripción de la tienda',
  `primary_color` VARCHAR(7) DEFAULT '#FF6B35' COMMENT 'Color primario del panel (hex)',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar registro inicial (solo uno)
INSERT INTO `shop_settings` (`id`, `shop_name`) VALUES (1, 'LUME - Velas Artesanales')
ON DUPLICATE KEY UPDATE `shop_name` = `shop_name`;

-- Si tu versión de MySQL no soporta ON DUPLICATE KEY UPDATE, usa esto:
-- INSERT IGNORE INTO `shop_settings` (`id`, `shop_name`) VALUES (1, 'LUME - Velas Artesanales');
