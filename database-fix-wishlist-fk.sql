-- ============================================
-- FIX: Wishlist - Eliminar FK que causa error 1452
-- ============================================
-- El FK wishlist.product_id -> products.id falla cuando products
-- tiene PK compuesta (store_id, id). Esta migración elimina el FK.
-- La validación de producto se hace en la API antes del INSERT.
--
-- Ejecutar en phpMyAdmin o consola MySQL:
-- ============================================

ALTER TABLE `wishlist` DROP FOREIGN KEY `wishlist_ibfk_1`;
