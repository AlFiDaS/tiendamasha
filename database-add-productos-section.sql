-- Sección "Más Vendidos" editable en landing page (ejecutar una sola vez)
ALTER TABLE `landing_page_settings`
  ADD COLUMN `productos_title` VARCHAR(255) DEFAULT 'Más Vendidos' COMMENT 'Título de la sección productos',
  ADD COLUMN `productos_description` TEXT DEFAULT NULL COMMENT 'Descripción debajo del título',
  ADD COLUMN `productos_button_text` VARCHAR(255) DEFAULT 'Ver todos los productos' COMMENT 'Texto del botón',
  ADD COLUMN `productos_button_link` VARCHAR(255) DEFAULT '/productos' COMMENT 'URL del botón';
