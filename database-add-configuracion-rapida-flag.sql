-- MIGRACIÓN: Agregar flag para saber si la configuración rápida fue completada
-- Ejecutar en la base de datos de la plataforma (PLATFORM_DB_NAME)
-- Si da error "Duplicate column", la columna ya existe.

ALTER TABLE `shop_settings`
ADD COLUMN `configuracion_rapida_completada` TINYINT(1) NOT NULL DEFAULT 0
COMMENT '1 = el dueño completó la configuración rápida tras crear la tienda';
