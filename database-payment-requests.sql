-- ============================================
-- MIGRACIÓN: Solicitudes de pago de membresía
-- Ejecutar en PLATFORM_DB (u161673556_tiendamasha)
-- ============================================

CREATE TABLE IF NOT EXISTS `payment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `plan` varchar(20) NOT NULL COMMENT 'basic, pro, platinum',
  `duration_months` int(11) NOT NULL COMMENT '1, 6, 12, 36',
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending_payment','paid','approved','rejected') NOT NULL DEFAULT 'pending_payment',
  `payment_method` varchar(30) NOT NULL DEFAULT 'mercadopago' COMMENT 'mercadopago o transferencia',
  `proof_image` varchar(500) DEFAULT NULL COMMENT 'Ruta del comprobante de transferencia',
  `mercadopago_preference_id` varchar(255) DEFAULT NULL,
  `mercadopago_payment_id` varchar(255) DEFAULT NULL,
  `payer_email` varchar(255) DEFAULT NULL,
  `payer_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL COMMENT 'platform_user_id del superadmin',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_status` (`status`),
  KEY `idx_mercadopago_preference` (`mercadopago_preference_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla ya existe, agregar las columnas nuevas:
-- ALTER TABLE `payment_requests` ADD COLUMN `payment_method` varchar(30) NOT NULL DEFAULT 'mercadopago' AFTER `status`;
-- ALTER TABLE `payment_requests` ADD COLUMN `proof_image` varchar(500) DEFAULT NULL AFTER `payment_method`;
