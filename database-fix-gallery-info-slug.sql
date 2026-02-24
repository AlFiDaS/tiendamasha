-- Parche: Permitir múltiples tiendas con slug 'galeria' en gallery_info
-- Ejecutar si al crear una tienda aparece: Duplicate entry 'galeria' for key 'idx_slug'
ALTER TABLE `gallery_info` DROP INDEX `idx_slug`, ADD UNIQUE KEY `idx_store_slug` (`store_id`, `slug`);
