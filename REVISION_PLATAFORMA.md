# Revisión completa - Plataforma Somos Tiendi

**Fecha:** 8 de febrero de 2026

## Resumen

Revisión del flujo de autenticación, dashboard, base de datos y configuración de la plataforma multi-tenant.

---

## 1. Base de datos

- **Base:** `u161673556_tiendamasha` (única para plataforma + tiendas)
- **Tablas relevantes:** `platform_users`, `stores`, `store_members`, `shop_settings`
- **Datos actuales:** Usuario 1 (cuchufiorio) → Store 1 (wemasha), Store 7 (tesst2) → Usuario 2 (masha)

### Configuración requerida en `.env`

```env
PLATFORM_DB_HOST=localhost
PLATFORM_DB_NAME=u161673556_tiendamasha
PLATFORM_DB_USER=...
PLATFORM_DB_PASS=...
```

**Importante:** Si `PLATFORM_DB_NAME` no coincide con la base real, el dashboard dará error 500 o mostrará tiendas vacías.

---

## 2. Cambios aplicados

### `helpers/platform-auth.php`
- `platformGetUserStores()`: Consulta sin `GROUP BY` (compatible MySQL estricto)
- Incluye tiendas donde el usuario es **miembro** (`store_members`) O **owner** (`owner_id`)
- `LEFT JOIN shop_settings` para `configuracion_rapida_completada`
- Deduplicación en PHP por `store_id`

### `helpers/plans.php`
- `canUserCreateStore()`: Ahora cuenta igual que `platformGetUserStores` (miembros + owners)
- Agregados planes `basic` y `pro` para coincidir con el enum `stores.plan`

### `.env.example`
- `PLATFORM_DB_NAME` actualizado a `u161673556_tiendamasha` como ejemplo

---

## 3. Flujo del dashboard

1. `platform-config.php` → carga `.env`, define constantes, requiere helpers
2. `platformRequireAuth()` → redirige a login si no hay sesión
3. `platformGetUserStores($userId)` → consulta tiendas
4. `canUserCreateStore($userId)` → decide si mostrar botón "Nueva Tienda"
5. `header.php` → `platformStartSession()`, `icon()`, `platformGetCurrentUser()`, `isSuperAdmin()`

---

## 4. Posibles causas del error 500

| Causa | Solución |
|-------|----------|
| `.env` con `PLATFORM_DB_NAME` incorrecto | Verificar que sea `u161673556_tiendamasha` |
| Carpeta `logs/` inexistente | Crear `logs/` y revisar `logs/platform-errors.log` |
| PHP sin extensión PDO/MySQL | Habilitar en php.ini |
| Sesión no inicia | Revisar permisos de sesión, `session.save_path` |

---

## 5. Debug

- **`/platform/debug-dashboard.php`**: Muestra sesión, usuario, tiendas por owner, store_members y resultado de `platformGetUserStores`. Eliminar después de resolver.
- **`/platform/dashboard.php?_debug=1`**: Muestra cantidad de tiendas cargadas.

---

## 6. Archivos clave

| Archivo | Función |
|---------|---------|
| `platform-config.php` | Config global, BD, errores |
| `helpers/env.php` | Carga `.env` desde raíz del proyecto |
| `helpers/platform-db.php` | Conexión PDO, `platformQuery`, `platformFetchAll` |
| `helpers/platform-auth.php` | Login, sesión, `platformGetUserStores` |
| `helpers/plans.php` | Límites por plan, `canUserCreateStore` |
| `platform/dashboard.php` | Vista "Mis Tiendas" |
| `store-router.php` | Rutas `/{slug}/admin/`, `/{slug}/` |

---

## 7. Checklist para deploy

- [ ] `.env` en servidor con `PLATFORM_DB_NAME=u161673556_tiendamasha`
- [ ] Carpeta `logs/` creada y escribible
- [ ] Subir `helpers/platform-auth.php` y `helpers/plans.php` actualizados
- [ ] Probar login y dashboard
- [ ] Eliminar `platform/debug-dashboard.php` cuando todo funcione
