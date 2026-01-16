# 📋 Instrucciones para Instalar el Sistema de Categorías Dinámicas

## Pasos para Migración

### 1. Ejecutar el Script SQL

Para **nuevas instalaciones**, usa `database.sql` que incluye todas las tablas.

Para **migrar una base de datos existente**, ejecuta la sección correspondiente de `database-migrations.sql`:

**Migración 1: Sistema de Categorías Dinámicas**

```sql
-- Este script:
-- 1. Crea la tabla `categories`
-- 2. Inserta las 3 categorías iniciales (productos, souvenirs, navidad)
-- 3. Modifica la tabla `products` para cambiar `categoria` de ENUM a VARCHAR
```

**⚠️ IMPORTANTE:** 
- Haz un backup de tu base de datos antes de ejecutar el script
- El script es seguro y no elimina datos existentes
- Las categorías existentes se migrarán automáticamente

### 2. Verificar la Instalación

Después de ejecutar el script, verifica que:

1. La tabla `categories` existe y tiene las 3 categorías iniciales
2. La tabla `products` tiene el campo `categoria` como VARCHAR(100)
3. Todos los productos mantienen sus categorías

### 3. Acceder al Panel de Categorías

1. Inicia sesión en el panel de admin
2. Verás un nuevo enlace "Categorías" en el menú superior
3. Haz clic en "Categorías" para gestionar las categorías

## Funcionalidades Disponibles

### ✅ Gestionar Categorías

- **Listar**: Ver todas las categorías con información (nombre, slug, productos asociados, visibilidad)
- **Agregar**: Crear nuevas categorías (ej: "Día de la Madre")
- **Editar**: Modificar nombre, slug, orden y visibilidad
- **Eliminar**: Eliminar categorías que no tengan productos asociados

### ✅ Visibilidad de Categorías

- **Visible**: Aparece en el sitio web y en el selector al agregar productos
- **Oculta**: No aparece en el sitio web, pero SÍ en el selector del admin
  - Útil para crear categorías y productos antes de publicarlos

### ✅ Selector Dinámico

- En "Agregar Producto" y "Editar Producto", el selector de categorías ahora carga todas las categorías desde la base de datos
- Las categorías ocultas aparecen marcadas como "(Oculta)"
- Puedes crear productos en categorías ocultas y luego hacerlas visibles cuando estés listo

## Ejemplo de Uso

### Crear una Nueva Categoría Temporal (Oculta)

1. Ir a "Categorías" → "Agregar Categoría"
2. Nombre: "Día de la Madre"
3. Slug: "dia-de-la-madre" (se genera automáticamente)
4. **Desmarcar** "Visible en la Web"
5. Guardar

### Agregar Productos a la Categoría Oculta

1. Ir a "Productos" → "Agregar Producto"
2. En el selector de "Categoría", verás "Día de la Madre (Oculta)"
3. Agregar todos los productos que necesites
4. La categoría NO aparecerá en el sitio web aún

### Hacer Visible la Categoría

1. Ir a "Categorías" → Buscar "Día de la Madre" → "Editar"
2. **Marcar** "Visible en la Web"
3. Guardar
4. Ahora la categoría y todos sus productos aparecerán en el sitio web

## Notas Importantes

- ⚠️ **No se pueden eliminar categorías que tengan productos asociados**
- ⚠️ **Cambiar el slug de una categoría puede afectar las URLs existentes**
- ✅ Las categorías visibles aparecen automáticamente en el sitio web
- ✅ El orden de las categorías se controla con el campo "Orden" (menor = primero)

## Soporte

Si encuentras algún problema:
1. Verifica que el script SQL se ejecutó correctamente
2. Verifica que la tabla `categories` existe y tiene datos
3. Verifica que el campo `categoria` en `products` es VARCHAR(100)

