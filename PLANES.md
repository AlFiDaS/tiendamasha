# Sistema de Planes - Somos Tiendi

## Resumen de planes

| Plan | Precio | Tiendas | Productos | Templates | Dominio propio | Diseño personalizado |
|------|--------|---------|-----------|-----------|----------------|----------------------|
| **Gratuito** | $0 | 1 | 4 | No | No | No |
| **Bronze** | $20.000/mes | 1 | 100 | No | No | No |
| **Silver** | $30.000/mes | 1 | 500 | Sí | No | No |
| **Gold** | $50.000/mes | 1 | Ilimitados | Sí | Sí* | No |
| **Platinum** | $1.000.000/mes | 1 | Ilimitados | Sí | Sí | Sí (100%) |

*Dominio personalizado: se configurará más adelante.

## Límite Platinum

El plan Platinum tiene un máximo de **2 clientes** inicialmente. Esta cantidad se puede aumentar según la capacidad operativa.

## Cambiar plan de una tienda

1. Accedé al panel **Super Admin** (solo usuarios con email en SUPERADMIN_EMAILS).
2. Ir a **Tiendas** → hacer clic en **Detalle** de la tienda.
3. En la sección "Información de la tienda", usar el desplegable **Plan** y hacer clic en **Actualizar**.

## Validaciones implementadas

- **Crear tienda**: Plan gratuito = máximo 1 tienda por usuario. Si ya tiene 1 tienda, no puede crear más (debe actualizar el plan de su tienda existente).
- **Agregar producto**: Se valida el límite según el plan de la tienda antes de insertar.
- **Dashboard**: El botón "Nueva Tienda" se deshabilita cuando el usuario alcanzó el límite.

## Archivos modificados/creados

- `helpers/plans.php` - Definición de planes y funciones de validación
- `platform/create-store.php` - Validación al crear tienda
- `platform/dashboard.php` - Ocultar botón cuando límite alcanzado
- `admin/add.php` - Validación de límite de productos + mostrar contador
- `platform/superadmin/store-detail.php` - Formulario para cambiar plan
- `platform/superadmin/api/update-store-plan.php` - API para actualizar plan
