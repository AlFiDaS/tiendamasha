-- ============================================
-- MIGRACIÓN: Campos adicionales para Galería de Ideas
-- ============================================
-- Agrega campos para editar badge, features (botones) y texto del botón principal
-- ============================================

-- Agregar campos para badge, features y button_text de galería
ALTER TABLE `landing_page_settings` 
ADD COLUMN IF NOT EXISTS `galeria_badge` VARCHAR(255) DEFAULT '✨ Inspiración' COMMENT 'Texto del badge (puede incluir emoji)' AFTER `galeria_visible`,
ADD COLUMN IF NOT EXISTS `galeria_features` TEXT DEFAULT NULL COMMENT 'JSON: Array de features [{icon, text}]' AFTER `galeria_badge`,
ADD COLUMN IF NOT EXISTS `galeria_button_text` VARCHAR(255) DEFAULT 'Galeria de ideas' COMMENT 'Texto del botón principal' AFTER `galeria_features`;

-- Actualizar valores por defecto si la tabla ya tiene datos
UPDATE `landing_page_settings` 
SET 
    `galeria_badge` = COALESCE(`galeria_badge`, '✨ Inspiración'),
    `galeria_features` = COALESCE(`galeria_features`, '[
        {"icon": "💡", "text": "Ideas creativas"},
        {"icon": "🏠", "text": "Decoración hogar"},
        {"icon": "✨", "text": "Paso a paso"}
    ]'),
    `galeria_button_text` = COALESCE(`galeria_button_text`, 'Galeria de ideas')
WHERE `id` = 1;
