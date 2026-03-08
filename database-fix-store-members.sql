-- ============================================
-- FIX: Agregar owners a store_members
-- ============================================
-- Si tenés tiendas en el dashboard que no aparecen, puede ser que
-- el owner_id esté en stores pero no exista el registro en store_members.
-- Este script agrega los owners faltantes.
-- ============================================

-- Insertar en store_members los owners que no están como miembros
INSERT IGNORE INTO store_members (store_id, user_id, role)
SELECT s.id, s.owner_id, 'owner'
FROM stores s
WHERE s.owner_id IS NOT NULL
  AND s.owner_id > 0
  AND NOT EXISTS (
    SELECT 1 FROM store_members sm 
    WHERE sm.store_id = s.id AND sm.user_id = s.owner_id
  );
