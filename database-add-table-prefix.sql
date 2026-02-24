-- ============================================
-- MIGRACIÓN: Agregar table_prefix a stores
-- ============================================
-- Soporta multi-tenancy con tablas prefijadas en BD compartida
-- Ejecutar UNA VEZ en la base de datos de la plataforma
-- ============================================

ALTER TABLE `stores` ADD COLUMN `table_prefix` VARCHAR(100) DEFAULT NULL AFTER `db_name`;

-- La tienda existente (wemasha) no tiene prefijo (backward compatible)
-- Las nuevas tiendas tendrán prefijo: '{slug}_'
