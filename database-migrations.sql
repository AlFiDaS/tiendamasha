-- ============================================
-- MIGRACIONES: Base de Datos LUME
-- ============================================
-- Este archivo contiene todas las migraciones históricas
-- Solo úsalo si necesitas migrar una base de datos existente
-- Para nuevas instalaciones, usa database.sql que ya incluye todo
-- ============================================

-- ============================================
-- MIGRACIÓN 1: Sistema de Categorías Dinámicas
-- ============================================
-- Fecha: Primera implementación de categorías
-- Descripción: Crea tabla categories y cambia products.categoria de ENUM a VARCHAR
-- ============================================

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `visible` TINYINT(1) DEFAULT 1,
  `orden` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_visible` (`visible`),
  KEY `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `categories` (`slug`, `name`, `visible`, `orden`) VALUES
('productos', 'Productos', 1, 1),
('souvenirs', 'Souvenirs', 1, 2),
('navidad', 'Navidad', 1, 3);

ALTER TABLE `products` 
MODIFY COLUMN `categoria` VARCHAR(100) NOT NULL;

DROP INDEX IF EXISTS `idx_categoria` ON `products`;
CREATE INDEX `idx_categoria` ON `products` (`categoria`);

-- ============================================
-- MIGRACIÓN 2: Campos adicionales en categories
-- ============================================
-- Fecha: Agregado de catalog_title y min_quantity
-- ============================================

ALTER TABLE `categories` 
ADD COLUMN IF NOT EXISTS `catalog_title` VARCHAR(255) NULL DEFAULT NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `min_quantity` INT(11) NULL DEFAULT NULL AFTER `catalog_title`;

UPDATE `categories` 
SET `min_quantity` = 10 
WHERE `slug` = 'souvenirs' AND `min_quantity` IS NULL;

-- ============================================
-- MIGRACIÓN 3: Tabla de órdenes (orders)
-- ============================================
-- Fecha: Implementación de sistema de pedidos
-- ============================================

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `mercadopago_id` VARCHAR(255) DEFAULT NULL,
  `preference_id` VARCHAR(255) DEFAULT NULL,
  `external_reference` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT NULL,
  `status_detail` VARCHAR(100) DEFAULT NULL,
  `payer_name` VARCHAR(255) DEFAULT NULL,
  `payer_email` VARCHAR(255) DEFAULT NULL,
  `payer_phone` VARCHAR(50) DEFAULT NULL,
  `payer_document` VARCHAR(50) DEFAULT NULL,
  `items` TEXT DEFAULT NULL,
  `total_amount` DECIMAL(10,2) DEFAULT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `payment_type` VARCHAR(50) DEFAULT NULL,
  `shipping_type` VARCHAR(255) DEFAULT NULL,
  `shipping_address` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `metadata` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mercadopago_id` (`mercadopago_id`),
  KEY `idx_preference_id` (`preference_id`),
  KEY `idx_external_reference` (`external_reference`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla ya existe, agregar campos faltantes
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `payer_document` VARCHAR(50) DEFAULT NULL AFTER `payer_phone`;

-- ============================================
-- MIGRACIÓN 4: Campo proof_image en orders
-- ============================================
-- Fecha: Agregado de comprobante de pago
-- ============================================

ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `proof_image` VARCHAR(500) DEFAULT NULL AFTER `payer_document`;

-- ============================================
-- MIGRACIÓN 5: Tabla de galería
-- ============================================
-- Fecha: Implementación de galería de ideas
-- ============================================

CREATE TABLE IF NOT EXISTS `galeria` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `imagen` VARCHAR(500) NOT NULL,
  `alt` VARCHAR(255) DEFAULT NULL,
  `orden` INT(11) DEFAULT 0,
  `visible` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_visible` (`visible`),
  KEY `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MIGRACIÓN 6: Descuentos en productos
-- ============================================
-- Fecha: Implementación de precios en descuento
-- ============================================

ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `en_descuento` TINYINT(1) DEFAULT 0 AFTER `price`,
ADD COLUMN IF NOT EXISTS `precio_descuento` VARCHAR(50) DEFAULT NULL AFTER `en_descuento`;

ALTER TABLE `products` 
ADD KEY IF NOT EXISTS `idx_en_descuento` (`en_descuento`);

-- ============================================
-- MIGRACIÓN 7: Stock ilimitado y stock_minimo
-- ============================================
-- Fecha: Cambio de stock booleano a numérico
-- ============================================

-- Cambiar stock de TINYINT(1) a INT(11) permitiendo NULL
ALTER TABLE `products` 
MODIFY COLUMN `stock` INT(11) DEFAULT NULL COMMENT 'Cantidad disponible en stock (NULL = ilimitado, 0 = sin stock, >0 = cantidad limitada)';

-- Agregar campo stock_minimo
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `stock_minimo` INT(11) DEFAULT 5 COMMENT 'Cantidad mínima antes de alertar (solo para stock limitado)' AFTER `stock`;

-- Convertir productos existentes con stock = 1 a NULL (ilimitado)
UPDATE `products` 
SET `stock` = NULL 
WHERE `stock` = 1;

-- ============================================
-- MIGRACIÓN 8: Sistema completo V2
-- ============================================
-- Fecha: Implementación de cupones, reviews, wishlist, customers
-- ============================================

-- Tabla de cupones/descuentos
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
  `value` DECIMAL(10,2) NOT NULL,
  `min_purchase` DECIMAL(10,2) DEFAULT 0,
  `max_discount` DECIMAL(10,2) DEFAULT NULL,
  `usage_limit` INT(11) DEFAULT NULL,
  `used_count` INT(11) DEFAULT 0,
  `valid_from` DATETIME DEFAULT NULL,
  `valid_until` DATETIME DEFAULT NULL,
  `active` TINYINT(1) DEFAULT 1,
  `applicable_to` ENUM('all', 'category', 'product') DEFAULT 'all',
  `category_slug` VARCHAR(100) DEFAULT NULL,
  `product_id` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`),
  KEY `idx_active` (`active`),
  KEY `idx_valid_dates` (`valid_from`, `valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de reviews/reseñas
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` VARCHAR(50) NOT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_email` VARCHAR(255) DEFAULT NULL,
  `rating` TINYINT(1) NOT NULL COMMENT 'Calificación de 1 a 5',
  `comment` TEXT DEFAULT NULL,
  `verified_purchase` TINYINT(1) DEFAULT 0,
  `order_id` INT(11) DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`),
  KEY `idx_rating` (`rating`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de wishlist/favoritos
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(255) NOT NULL COMMENT 'ID de sesión o email del cliente',
  `product_id` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_session_product` (`session_id`, `product_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_session_id` (`session_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de clientes
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar customer_id a orders
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `customer_id` INT(11) DEFAULT NULL AFTER `id`,
ADD KEY IF NOT EXISTS `idx_customer_id` (`customer_id`);

-- Agregar campos de cupones a orders
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `coupon_code` VARCHAR(50) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) DEFAULT 0 AFTER `coupon_code`,
ADD KEY IF NOT EXISTS `idx_coupon_code` (`coupon_code`);

-- Tabla de movimientos de stock
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` VARCHAR(50) NOT NULL,
  `type` ENUM('sale', 'restock', 'adjustment', 'return') NOT NULL,
  `quantity` INT(11) NOT NULL COMMENT 'Cantidad positiva o negativa',
  `order_id` INT(11) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTAS IMPORTANTES
-- ============================================
-- 1. Este archivo contiene migraciones históricas
-- 2. Para nuevas instalaciones, usa database.sql
-- 3. Las migraciones están en orden cronológico
-- 4. Algunos comandos usan IF NOT EXISTS (MySQL 5.7.4+)
-- 5. Si usas versiones anteriores, elimina las cláusulas IF NOT EXISTS
-- 6. Siempre haz backup antes de ejecutar migraciones
