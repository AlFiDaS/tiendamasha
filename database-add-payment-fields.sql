-- ============================================
-- MIGRACIÓN: Agregar campos de pagos a shop_settings
-- ============================================
-- Transferencia directa: alias, CBU, titular
-- MercadoPago: access token, public key, modo prueba
-- ============================================

ALTER TABLE `shop_settings` 
ADD COLUMN IF NOT EXISTS `transfer_alias` VARCHAR(100) DEFAULT NULL COMMENT 'Alias para transferencia (ej: tienda.mp)',
ADD COLUMN IF NOT EXISTS `transfer_cbu` VARCHAR(22) DEFAULT NULL COMMENT 'CBU para transferencia (22 dígitos)',
ADD COLUMN IF NOT EXISTS `transfer_titular` VARCHAR(255) DEFAULT NULL COMMENT 'Nombre del titular de la cuenta',
ADD COLUMN IF NOT EXISTS `mercadopago_access_token` VARCHAR(500) DEFAULT NULL COMMENT 'Access Token de MercadoPago',
ADD COLUMN IF NOT EXISTS `mercadopago_public_key` VARCHAR(500) DEFAULT NULL COMMENT 'Public Key de MercadoPago (opcional)',
ADD COLUMN IF NOT EXISTS `mercadopago_test_mode` TINYINT(1) DEFAULT 1 COMMENT '1=prueba, 0=producción';

-- Si tu versión de MySQL no soporta IF NOT EXISTS, ejecuta cada ADD COLUMN por separado
