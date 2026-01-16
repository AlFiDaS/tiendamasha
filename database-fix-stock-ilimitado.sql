-- ============================================
-- CORRECCIÓN: Stock Ilimitado
-- ============================================
-- Si todos los productos aparecen sin stock, ejecuta estos comandos
-- ============================================

-- PASO 1: Verificar el estado actual del stock
-- Ejecuta esto primero para ver qué valores tienen:
SELECT 
    stock,
    COUNT(*) as cantidad
FROM products 
GROUP BY stock
ORDER BY stock;

-- PASO 2: Si los productos tienen stock = 0 o valores negativos,
-- convertir TODOS los productos a stock ilimitado (NULL)
-- EXCEPTO los que ya tienen un stock numérico positivo que quieras mantener
UPDATE `products` 
SET `stock` = NULL 
WHERE `stock` <= 0 OR `stock` IS NULL;

-- O si quieres convertir TODOS los productos a ilimitado (recomendado para velas):
-- UPDATE `products` SET `stock` = NULL;

-- PASO 3: Verificar que se actualizó correctamente
SELECT 
    CASE 
        WHEN stock IS NULL THEN 'Ilimitado'
        WHEN stock = 0 THEN 'Sin Stock'
        ELSE CONCAT(stock, ' unidades')
    END as tipo_stock,
    COUNT(*) as cantidad
FROM products 
GROUP BY 
    CASE 
        WHEN stock IS NULL THEN 'Ilimitado'
        WHEN stock = 0 THEN 'Sin Stock'
        ELSE CONCAT(stock, ' unidades')
    END;

