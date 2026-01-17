-- ============================================
-- MIGRACIÓN: Campos adicionales para Footer
-- ============================================
-- Agrega campos para editar el footer desde tienda.php
-- ============================================

-- Agregar campos para el footer
ALTER TABLE `shop_settings` 
ADD COLUMN IF NOT EXISTS `footer_description` TEXT DEFAULT NULL COMMENT 'Descripción que aparece en el footer' AFTER `description`,
ADD COLUMN IF NOT EXISTS `footer_copyright` VARCHAR(255) DEFAULT NULL COMMENT 'Texto de copyright (ej: © 2024 LUME)' AFTER `footer_description`,
ADD COLUMN IF NOT EXISTS `creation_year` INT(4) DEFAULT NULL COMMENT 'Año de creación de la tienda' AFTER `footer_copyright`;

-- Actualizar valores por defecto si la tabla ya tiene datos
UPDATE `shop_settings` 
SET 
    `footer_description` = COALESCE(`footer_description`, 'Iluminando momentos especiales con velas artesanales únicas'),
    `footer_copyright` = COALESCE(`footer_copyright`, CONCAT('© ', YEAR(CURDATE()), ' ', COALESCE(`shop_name`, 'LUME'), '. Todos los derechos reservados.'))
WHERE `id` = 1;
