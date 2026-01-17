-- ============================================
-- MIGRACIÓN: Agregar campo primary_color a shop_settings
-- ============================================
-- Agrega el campo primary_color para personalizar el color del panel admin
-- ============================================

ALTER TABLE `shop_settings` 
ADD COLUMN IF NOT EXISTS `primary_color` VARCHAR(7) DEFAULT '#FF6B35' COMMENT 'Color primario del panel (hex)' 
AFTER `description`;

-- Si tu versión de MySQL no soporta IF NOT EXISTS, usa esto:
-- ALTER TABLE `shop_settings` 
-- ADD COLUMN `primary_color` VARCHAR(7) DEFAULT '#FF6B35' COMMENT 'Color primario del panel (hex)' 
-- AFTER `description`;
