-- ============================================
-- MIGRACIÓN: Agregar campos de Telegram a shop_settings
-- ============================================
-- Bot Token, Chat ID y activar/desactivar notificaciones
-- ============================================

ALTER TABLE `shop_settings` 
ADD COLUMN IF NOT EXISTS `telegram_bot_token` VARCHAR(255) DEFAULT NULL COMMENT 'Token del bot de Telegram (de @BotFather)',
ADD COLUMN IF NOT EXISTS `telegram_chat_id` VARCHAR(50) DEFAULT NULL COMMENT 'Chat ID (de @userinfobot)',
ADD COLUMN IF NOT EXISTS `telegram_enabled` TINYINT(1) DEFAULT 0 COMMENT '1=activado, 0=desactivado';
