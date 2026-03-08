-- ============================================
-- LIMPIEZA: Eliminar duplicados en store_members
-- ============================================
-- Tenés 3 registros duplicados (store_id=1, user_id=1).
-- Este script deja solo uno.
-- ============================================

-- Opción 1: Eliminar duplicados manteniendo uno por (store_id, user_id)
DELETE sm1 FROM store_members sm1
INNER JOIN store_members sm2
WHERE sm1.store_id = sm2.store_id
  AND sm1.user_id = sm2.user_id
  AND sm1.role = sm2.role
  AND sm1.store_id = 1 AND sm1.user_id = 1
  AND sm1.store_id > sm2.store_id;  -- Esto no funciona para mismo store_id

-- Mejor: eliminar todos y dejar uno solo
DELETE FROM store_members WHERE store_id = 1 AND user_id = 1;
INSERT INTO store_members (store_id, user_id, role) VALUES (1, 1, 'owner');
