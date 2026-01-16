-- ============================================
-- BASE DE DATOS: LUME - Catálogo de Velas
-- ============================================
-- Descripción: Estructura completa de base de datos para catálogo dinámico
-- Compatible: MySQL 5.7+ / MariaDB
-- Versión: Unificada - Todas las tablas y campos actualizados
-- ============================================

-- Crear base de datos (opcional, comenta si ya existe)
-- CREATE DATABASE IF NOT EXISTS lume_catalogo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE lume_catalogo;

-- ============================================
-- TABLA: products
-- ============================================
CREATE TABLE IF NOT EXISTS `products` (
  `id` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `price` VARCHAR(50) DEFAULT NULL,
  `en_descuento` TINYINT(1) DEFAULT 0,
  `precio_descuento` VARCHAR(50) DEFAULT NULL,
  `image` VARCHAR(500) DEFAULT NULL,
  `hoverImage` VARCHAR(500) DEFAULT NULL,
  `stock` INT(11) DEFAULT NULL COMMENT 'Cantidad disponible en stock (NULL = ilimitado, 0 = sin stock, >0 = cantidad limitada)',
  `stock_minimo` INT(11) DEFAULT 5 COMMENT 'Cantidad mínima antes de alertar (solo para stock limitado)',
  `destacado` TINYINT(1) DEFAULT 0,
  `categoria` VARCHAR(100) NOT NULL COMMENT 'Slug de la categoría (relacionado con categories.slug)',
  `visible` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_destacado` (`destacado`),
  KEY `idx_visible` (`visible`),
  KEY `idx_stock` (`stock`),
  KEY `idx_en_descuento` (`en_descuento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: categories
-- ============================================
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `catalog_title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Título personalizado del catálogo',
  `min_quantity` INT(11) NULL DEFAULT NULL COMMENT 'Cantidad mínima de compra',
  `visible` TINYINT(1) DEFAULT 1,
  `orden` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_visible` (`visible`),
  KEY `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar categorías iniciales
INSERT IGNORE INTO `categories` (`slug`, `name`, `visible`, `orden`, `min_quantity`) VALUES
('productos', 'Productos', 1, 1, NULL),
('souvenirs', 'Souvenirs', 1, 2, 10),
('navidad', 'Navidad', 1, 3, NULL);

-- ============================================
-- TABLA: admin_users
-- ============================================
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: orders
-- ============================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) DEFAULT NULL COMMENT 'Relación con tabla customers (opcional)',
  `mercadopago_id` VARCHAR(255) DEFAULT NULL,
  `preference_id` VARCHAR(255) DEFAULT NULL,
  `external_reference` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT NULL,
  `status_detail` VARCHAR(100) DEFAULT NULL,
  `payer_name` VARCHAR(255) DEFAULT NULL,
  `payer_email` VARCHAR(255) DEFAULT NULL,
  `payer_phone` VARCHAR(50) DEFAULT NULL,
  `payer_document` VARCHAR(50) DEFAULT NULL,
  `proof_image` VARCHAR(500) DEFAULT NULL COMMENT 'Ruta de la imagen del comprobante de pago',
  `items` TEXT DEFAULT NULL,
  `total_amount` DECIMAL(10,2) DEFAULT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `payment_type` VARCHAR(50) DEFAULT NULL,
  `coupon_code` VARCHAR(50) DEFAULT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0,
  `shipping_type` VARCHAR(255) DEFAULT NULL,
  `shipping_address` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `metadata` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_mercadopago_id` (`mercadopago_id`),
  KEY `idx_preference_id` (`preference_id`),
  KEY `idx_external_reference` (`external_reference`),
  KEY `idx_status` (`status`),
  KEY `idx_coupon_code` (`coupon_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: galeria
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
-- TABLA: coupons
-- ============================================
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

-- ============================================
-- TABLA: reviews
-- ============================================
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

-- ============================================
-- TABLA: wishlist
-- ============================================
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

-- ============================================
-- TABLA: customers
-- ============================================
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

-- Agregar foreign key de orders a customers (si no existe ya)
-- ALTER TABLE `orders` 
-- ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL;

-- ============================================
-- TABLA: stock_movements
-- ============================================
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
-- DATOS DE EJEMPLO (Opcional)
-- ============================================
-- INSERT INTO `admin_users` (`username`, `password`, `email`) VALUES
-- ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@lume.com');
-- Contraseña por defecto: "password" (CAMBIA ESTO EN PRODUCCIÓN)

-- ============================================
-- NOTAS IMPORTANTES
-- ============================================
-- 1. El campo 'id' de products usa VARCHAR(50) para mayor flexibilidad
-- 2. El campo 'slug' debe ser único para URLs amigables
-- 3. 'visible' controla qué productos se muestran en la web
-- 4. 'destacado' marca productos para homepage
-- 5. 'stock' NULL = ilimitado (productos hechos bajo pedido), 0 = sin stock, >0 = cantidad limitada
-- 6. 'categoria' en products debe coincidir con categories.slug
-- 7. Todos los timestamps usan zona horaria del servidor
-- 8. Las foreign keys están definidas para mantener integridad referencial
