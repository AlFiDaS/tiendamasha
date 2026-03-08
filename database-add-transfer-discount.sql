-- ============================================
-- MIGRACIÓN: Descuento por transferencia configurable
-- ============================================
-- Permite que cada tienda configure el % de descuento al pagar por transferencia.
-- Ej: 20% = transferencia 20% más barata que tarjeta (comportamiento actual)
-- Ej: 15% = transferencia 15% más barata
-- Ej: 30% = transferencia 30% más barata
-- Precio tarjeta = precio_transferencia / (1 - descuento/100)
-- ============================================

-- MySQL 8.0.12+ soporta IF NOT EXISTS. Si falla, usa la línea comentada abajo.
ALTER TABLE `shop_settings`
ADD COLUMN IF NOT EXISTS `transfer_discount_percent` DECIMAL(5,2) DEFAULT 20.00 COMMENT 'Descuento % al pagar por transferencia (ej: 20 = 20% más barato que tarjeta)';

-- Alternativa para MySQL < 8.0.12 (ejecutar solo si la anterior falla):
-- ALTER TABLE `shop_settings` ADD COLUMN `transfer_discount_percent` DECIMAL(5,2) DEFAULT 20.00 COMMENT 'Descuento % al pagar por transferencia';
