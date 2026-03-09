-- MIGRACIÓN: Sistema de suscripciones y planes
-- Ejecutar en la base de datos (PLATFORM_DB_NAME = misma que products, shop_settings)

-- 1. Stores: agregar campos de suscripción
ALTER TABLE `stores`
ADD COLUMN `subscription_ends_at` DATETIME NULL COMMENT 'Fin del período pagado (ej: 2026-03-07 23:59:59)' AFTER `plan`,
ADD COLUMN `subscription_plan` VARCHAR(20) NULL COMMENT 'Plan de suscripción activo (basic, pro, platinum) - NULL = free' AFTER `subscription_ends_at`;

-- 2. Products: marcar productos auto-ocultos por downgrade (para restaurar al volver a pagar)
-- Si da error "Duplicate column", la columna ya existe.
ALTER TABLE `products`
ADD COLUMN `auto_hidden_by_plan` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = oculto automáticamente por límite de plan, restaurar al upgrade' AFTER `visible`;
