-- ============================================
-- MIGRACIÓN: De prefijos de tabla a store_id
-- ============================================
-- Ejecutar UNA SOLA VEZ en la base de datos.
-- Requiere: tabla stores con columnas id, slug, table_prefix
-- NOTA: Si hay FK a products, ejecutar antes:
--   ALTER TABLE reviews DROP FOREIGN KEY reviews_ibfk_1;
--   ALTER TABLE wishlist DROP FOREIGN KEY wishlist_ibfk_1;
--   ALTER TABLE stock_movements DROP FOREIGN KEY stock_movements_ibfk_1;
-- (Los nombres pueden variar; ver SHOW CREATE TABLE)
--
-- SI TE DIO ERROR #1060 "Nombre duplicado de columna 'store_id'":
--   El paso 1 ya se ejecutó. Saltá el paso 1 y ejecutá SOLO desde el paso 2.
-- ============================================

-- 1. Agregar store_id a todas las tablas de tienda (nullable primero)
--    SALTAR si store_id ya existe (error #1060)
ALTER TABLE `products` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `categories` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `admin_users` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `orders` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `galeria` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `coupons` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `reviews` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `wishlist` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `customers` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `stock_movements` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD INDEX `idx_store_id` (`store_id`);
ALTER TABLE `shop_settings` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD UNIQUE INDEX `idx_store_id` (`store_id`);
ALTER TABLE `gallery_info` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD UNIQUE INDEX `idx_store_id` (`store_id`);
ALTER TABLE `landing_page_settings` ADD COLUMN `store_id` INT(11) NULL AFTER `id`, ADD UNIQUE INDEX `idx_store_id` (`store_id`);

-- 2. Asignar store_id a datos existentes (tablas sin prefijo -> primera tienda)
-- La tienda "default" es la que tiene table_prefix vacío o NULL. Si no existe, usamos id=1.
SET @default_store_id = (SELECT id FROM stores WHERE (table_prefix = '' OR table_prefix IS NULL) LIMIT 1);
SET @default_store_id = IFNULL(@default_store_id, (SELECT id FROM stores ORDER BY id ASC LIMIT 1));

UPDATE `products` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `categories` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `admin_users` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `orders` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `galeria` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `coupons` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `reviews` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `wishlist` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `customers` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `stock_movements` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `shop_settings` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `gallery_info` SET store_id = @default_store_id WHERE store_id IS NULL;
UPDATE `landing_page_settings` SET store_id = @default_store_id WHERE store_id IS NULL;

-- 3. Hacer store_id NOT NULL en products (para cambiar PK)
ALTER TABLE `products` MODIFY `store_id` INT(11) NOT NULL;

-- 4. Cambiar PK de products ANTES de migrar (evitar conflictos de id)
ALTER TABLE `products` DROP PRIMARY KEY, ADD PRIMARY KEY (`store_id`, `id`);

-- 5. Migrar datos de tablas prefijadas (test1_, etc.) - OPCIONAL
-- Solo descomentar si tenés tablas test1_* y querés migrar esos datos.
-- Si no tenés tablas prefijadas, saltá esta sección.
/*
INSERT IGNORE INTO `products` (store_id, id, slug, name, descripcion, price, en_descuento, precio_descuento, image, hoverImage, stock, stock_minimo, destacado, categoria, visible, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), id, slug, name, descripcion, price, en_descuento, precio_descuento, image, hoverImage, stock, stock_minimo, destacado, categoria, visible, created_at, updated_at FROM `test1_products` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `categories` (store_id, slug, name, catalog_title, min_quantity, visible, orden, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), slug, name, catalog_title, min_quantity, visible, orden, created_at, updated_at FROM `test1_categories` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `admin_users` (store_id, username, password, email, password_reset_token, password_reset_expires, created_at, last_login)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), username, password, email, password_reset_token, password_reset_expires, created_at, last_login FROM `test1_admin_users` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `orders` (store_id, customer_id, mercadopago_id, preference_id, external_reference, status, status_detail, payer_name, payer_email, payer_phone, payer_document, proof_image, items, total_amount, payment_method, payment_type, coupon_code, discount_amount, shipping_type, shipping_address, notes, metadata, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), customer_id, mercadopago_id, preference_id, external_reference, status, status_detail, payer_name, payer_email, payer_phone, payer_document, proof_image, items, total_amount, payment_method, payment_type, coupon_code, discount_amount, shipping_type, shipping_address, notes, metadata, created_at, updated_at FROM `test1_orders` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `galeria` (store_id, nombre, imagen, alt, orden, visible, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), nombre, imagen, alt, orden, visible, created_at, updated_at FROM `test1_galeria` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `coupons` (store_id, code, type, value, min_purchase, max_discount, usage_limit, used_count, valid_from, valid_until, active, applicable_to, category_slug, product_id, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), code, type, value, min_purchase, max_discount, usage_limit, used_count, valid_from, valid_until, active, applicable_to, category_slug, product_id, created_at, updated_at FROM `test1_coupons` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `reviews` (store_id, product_id, customer_name, customer_email, rating, comment, verified_purchase, order_id, status, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), product_id, customer_name, customer_email, rating, comment, verified_purchase, order_id, status, created_at, updated_at FROM `test1_reviews` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `wishlist` (store_id, session_id, product_id, created_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), session_id, product_id, created_at FROM `test1_wishlist` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `customers` (store_id, email, name, phone, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), email, name, phone, created_at, updated_at FROM `test1_customers` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `stock_movements` (store_id, product_id, type, quantity, order_id, notes, created_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), product_id, type, quantity, order_id, notes, created_at FROM `test1_stock_movements` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `shop_settings` (store_id, shop_name, shop_logo, whatsapp_number, whatsapp_message, address, instagram, facebook, email, phone, description, primary_color, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), shop_name, shop_logo, whatsapp_number, whatsapp_message, address, instagram, facebook, email, phone, description, primary_color, updated_at FROM `test1_shop_settings` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `gallery_info` (store_id, name, slug, title, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), name, slug, title, created_at, updated_at FROM `test1_gallery_info` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');

INSERT IGNORE INTO `landing_page_settings` (store_id, carousel_images, sobre_title, sobre_text_1, sobre_text_2, sobre_image, sobre_stat_1_number, sobre_stat_1_label, sobre_stat_2_number, sobre_stat_2_label, sobre_stat_3_number, sobre_stat_3_label, testimonials, testimonials_visible, galeria_title, galeria_description, galeria_image, galeria_link, galeria_visible, updated_at)
SELECT (SELECT id FROM stores WHERE table_prefix = 'test1_'), carousel_images, sobre_title, sobre_text_1, sobre_text_2, sobre_image, sobre_stat_1_number, sobre_stat_1_label, sobre_stat_2_number, sobre_stat_2_label, sobre_stat_3_number, sobre_stat_3_label, testimonials, testimonials_visible, galeria_title, galeria_description, galeria_image, galeria_link, galeria_visible, updated_at FROM `test1_landing_page_settings` WHERE EXISTS (SELECT 1 FROM stores WHERE table_prefix = 'test1_');
*/

-- 6. Hacer store_id NOT NULL (resto de tablas; products ya en paso 3)
ALTER TABLE `categories` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `admin_users` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `orders` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `galeria` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `coupons` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `reviews` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `wishlist` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `customers` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `stock_movements` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `shop_settings` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `gallery_info` MODIFY `store_id` INT(11) NOT NULL;
ALTER TABLE `landing_page_settings` MODIFY `store_id` INT(11) NOT NULL;

-- 7. Actualizar índices únicos para incluir store_id (products PK ya cambiado en paso 4)
ALTER TABLE `products` DROP INDEX `idx_slug`, ADD UNIQUE KEY `idx_store_slug` (`store_id`, `slug`);
ALTER TABLE `categories` DROP INDEX `idx_slug`, ADD UNIQUE KEY `idx_store_slug` (`store_id`, `slug`);
ALTER TABLE `admin_users` DROP INDEX `username`, ADD UNIQUE KEY `idx_store_username` (`store_id`, `username`);
ALTER TABLE `coupons` DROP INDEX `idx_code`, ADD UNIQUE KEY `idx_store_code` (`store_id`, `code`);
ALTER TABLE `customers` DROP INDEX `idx_email`, ADD UNIQUE KEY `idx_store_email` (`store_id`, `email`);
ALTER TABLE `gallery_info` DROP INDEX `idx_slug`, ADD UNIQUE KEY `idx_store_slug` (`store_id`, `slug`);

-- 8. Eliminar tablas prefijadas (descomentar cuando estés listo)
-- DROP TABLE IF EXISTS `test1_products`;
-- DROP TABLE IF EXISTS `test1_categories`;
-- DROP TABLE IF EXISTS `test1_admin_users`;
-- DROP TABLE IF EXISTS `test1_orders`;
-- DROP TABLE IF EXISTS `test1_galeria`;
-- DROP TABLE IF EXISTS `test1_coupons`;
-- DROP TABLE IF EXISTS `test1_reviews`;
-- DROP TABLE IF EXISTS `test1_wishlist`;
-- DROP TABLE IF EXISTS `test1_customers`;
-- DROP TABLE IF EXISTS `test1_stock_movements`;
-- DROP TABLE IF EXISTS `test1_shop_settings`;
-- DROP TABLE IF EXISTS `test1_gallery_info`;
-- DROP TABLE IF EXISTS `test1_landing_page_settings`;

-- 9. Eliminar columna table_prefix de stores (opcional, hacer después de subir el código nuevo)
-- ALTER TABLE `stores` DROP COLUMN `table_prefix`;
