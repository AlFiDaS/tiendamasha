-- ============================================
-- MIGRACIÓN store_id - CONTINUAR (paso 2 en adelante)
-- ============================================
-- Usar este script cuando el paso 1 ya se ejecutó
-- (error #1060: columna store_id ya existe)
-- ============================================

-- 2. Asignar store_id a datos existentes (tablas sin prefijo -> primera tienda)
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

-- 4. Cambiar PK de products
ALTER TABLE `products` DROP PRIMARY KEY, ADD PRIMARY KEY (`store_id`, `id`);

-- 6. Hacer store_id NOT NULL (resto de tablas)
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

-- 7. Actualizar índices únicos para incluir store_id
ALTER TABLE `products` DROP INDEX `idx_slug`, ADD UNIQUE KEY `idx_store_slug` (`store_id`, `slug`);
ALTER TABLE `categories` DROP INDEX `idx_slug`, ADD UNIQUE KEY `idx_store_slug` (`store_id`, `slug`);
ALTER TABLE `admin_users` DROP INDEX `username`, ADD UNIQUE KEY `idx_store_username` (`store_id`, `username`);
ALTER TABLE `coupons` DROP INDEX `idx_code`, ADD UNIQUE KEY `idx_store_code` (`store_id`, `code`);
ALTER TABLE `customers` DROP INDEX `idx_email`, ADD UNIQUE KEY `idx_store_email` (`store_id`, `email`);
ALTER TABLE `gallery_info` DROP INDEX `idx_slug`, ADD UNIQUE KEY `idx_store_slug` (`store_id`, `slug`);
