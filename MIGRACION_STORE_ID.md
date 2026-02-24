# Migración: de prefijos de tabla a store_id

## Resumen

Se migra de **tablas con prefijo** (`test1_products`, `test1_categories`, etc.) a **una sola BD con store_id** en cada tabla.

## Pasos para migrar

### 1. Backup

Hacé un backup completo de la base de datos antes de empezar.

### 2. Ejecutar el SQL de migración

```bash
mysql -u usuario -p nombre_bd < database-migrate-store-id.sql
```

O desde phpMyAdmin: importar el archivo `database-migrate-store-id.sql`.

**Nota:** Si tenés foreign keys a `products`, ejecutá antes:

```sql
ALTER TABLE reviews DROP FOREIGN KEY reviews_ibfk_1;
ALTER TABLE wishlist DROP FOREIGN KEY wishlist_ibfk_1;
ALTER TABLE stock_movements DROP FOREIGN KEY stock_movements_ibfk_1;
```

(Verificá los nombres con `SHOW CREATE TABLE`.)

### 3. Migrar datos de tablas prefijadas (test1_, etc.)

El script incluye migración para `test1_`. Si tenés otros prefijos, agregá bloques similares o ejecutá manualmente.

### 4. Eliminar tablas prefijadas (opcional)

Descomentá en el SQL las líneas `DROP TABLE IF EXISTS test1_*` y ejecutá de nuevo.

### 5. Eliminar columna table_prefix de stores (opcional)

Descomentá y ejecutá:

```sql
ALTER TABLE stores DROP COLUMN table_prefix;
```

### 6. Subir el código nuevo

Subí los archivos modificados:

- `helpers/db.php`
- `helpers/store-context.php`
- `helpers/platform-db.php`
- `helpers/superadmin.php`
- `platform/create-store.php`
- `platform/superadmin/stores.php`

### 7. Probar

- Crear una tienda nueva
- Verificar que el admin y la tienda funcionen
- Eliminar una tienda de prueba desde el superadmin

## Cambios en el código

- **getDB():** usa PDO normal; `executeQuery` inyecta `store_id` automáticamente.
- **create-store:** inserta en `stores` y llama a `createStoreData()`.
- **deleteStore:** borra filas con `DELETE ... WHERE store_id = ?` en lugar de `DROP TABLE`.
- **getStoreStats:** consulta por `store_id` en lugar de prefijo.
