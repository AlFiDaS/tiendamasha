-- Parche: Actualizar datos de Test1 (tienda ya creada)
-- Ejecutar si Test1 muestra contenido de Lume (velas, "Sobre LUME", etc.)
-- Reemplazá 2 por el store_id real de Test1: SELECT id, slug FROM stores;

UPDATE landing_page_settings SET sobre_title = 'Sobre Test1' WHERE store_id = 2;
-- Si tenés la columna productos_title: descomentar la línea siguiente
-- UPDATE landing_page_settings SET productos_title = 'Catálogo de Test1' WHERE store_id = 2;
UPDATE categories SET catalog_title = 'Catálogo de productos' WHERE store_id = 2 AND slug = 'productos';
