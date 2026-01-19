-- ============================================
-- MIGRACIÓN: Agregar campos de color para modo claro y oscuro
-- ============================================
-- Agrega campos para configurar colores primarios en modo claro y oscuro
-- ============================================

-- Agregar campos de color a landing_page_settings
-- Nota: Si los campos ya existen, este script generará un error pero no afectará la estructura
ALTER TABLE `landing_page_settings`
ADD COLUMN `primary_color_light` VARCHAR(7) DEFAULT '#ff8c00' COMMENT 'Color primario para modo claro' AFTER `galeria_button_text`,
ADD COLUMN `primary_color_dark` VARCHAR(7) DEFAULT '#ff8c00' COMMENT 'Color primario para modo oscuro' AFTER `primary_color_light`;

-- Actualizar registro existente con valores por defecto si no existen
UPDATE `landing_page_settings` 
SET `primary_color_light` = '#ff8c00', 
    `primary_color_dark` = '#ff8c00' 
WHERE `id` = 1 AND (`primary_color_light` IS NULL OR `primary_color_dark` IS NULL);
