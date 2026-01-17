-- ============================================
-- MIGRACIÓN: Recuperación de Contraseña
-- ============================================
-- Agrega campos necesarios para recuperación de contraseña por email
-- ============================================

-- Agregar campos para recuperación de contraseña
ALTER TABLE `admin_users` 
ADD COLUMN IF NOT EXISTS `password_reset_token` VARCHAR(255) NULL DEFAULT NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `password_reset_expires` DATETIME NULL DEFAULT NULL AFTER `password_reset_token`;

-- Agregar índice para búsquedas rápidas de tokens
ALTER TABLE `admin_users` 
ADD KEY IF NOT EXISTS `idx_reset_token` (`password_reset_token`);

-- Si tu versión de MySQL no soporta IF NOT EXISTS, usa esto:
-- ALTER TABLE `admin_users` 
-- ADD COLUMN `password_reset_token` VARCHAR(255) NULL DEFAULT NULL AFTER `email`,
-- ADD COLUMN `password_reset_expires` DATETIME NULL DEFAULT NULL AFTER `password_reset_token`,
-- ADD KEY `idx_reset_token` (`password_reset_token`);
