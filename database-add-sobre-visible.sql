-- Agregar campo sobre_visible para poder ocultar la sección Sobre en el index
ALTER TABLE `landing_page_settings`
ADD COLUMN `sobre_visible` TINYINT(1) DEFAULT 1 COMMENT 'Mostrar u ocultar sección Sobre' AFTER `sobre_stat_3_label`;
