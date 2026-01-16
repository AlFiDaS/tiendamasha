# 🚀 Instrucciones para Subir a Hostinger

## ✅ CONFIGURACIÓN YA APLICADA

**¡Buenas noticias!** Ya se aplicaron todos los cambios necesarios para que funcione en Hostinger.

### ✅ Cambios Realizados:

1. **`astro.config.mjs`**: 
   - ✅ Cambiado a `output: 'static'`
   - ✅ Removido adaptador Node.js
   - ✅ Compatible con hosting compartido (solo PHP)

2. **Rutas dinámicas** (`productos/[slug].astro`, `souvenirs/[slug].astro`, `navidad/[slug].astro`):
   - ✅ Cambiado de `prerender = false` a `getStaticPaths() { return []; }`
   - ✅ JavaScript extrae slug de la URL directamente
   - ✅ Funciona con hosting estático

3. **`.htaccess`**:
   - ✅ Reglas para redirigir rutas dinámicas a `placeholder/` (que sirve `placeholder/index.html`)
   - ✅ Soporte para `/productos/slug`, `/souvenirs/slug`, `/navidad/slug`
   - ✅ Astro genera `/productos/placeholder/index.html` (estructura de carpetas)

## 📋 Pasos para Subir a Hostinger

### 1. Build del Proyecto

En `src/pages/productos/[slug].astro`, `src/pages/souvenirs/[slug].astro`, y `src/pages/navidad/[slug].astro`:

**Cambiar de:**
```javascript
export const prerender = false;
```

**A:**
```javascript
export async function getStaticPaths() {
  // Retornar array vacío - JavaScript cargará el contenido dinámicamente
  return [];
}
```

Y actualizar el script para extraer el slug de la URL:
```javascript
<script is:inline>
  (function() {
    // Extraer slug de la URL directamente
    const pathParts = window.location.pathname.split('/').filter(p => p);
    const categoriaIndex = pathParts.findIndex(p => ['productos', 'souvenirs', 'navidad'].includes(p));
    const slug = categoriaIndex >= 0 && pathParts[categoriaIndex + 1] ? pathParts[categoriaIndex + 1] : '';
    
    // ... resto del código
  })();
</script>
```

### 3. Actualizar .htaccess

El `.htaccess` debe redirigir rutas dinámicas a los archivos `index.html` de cada categoría:

```apache
# Redirigir rutas dinámicas de productos a placeholder/index.html
# Astro genera /productos/placeholder/index.html (estructura de carpetas)
RewriteCond %{REQUEST_URI} ^/(productos|souvenirs|navidad)/([^/]+)/?$
RewriteCond %{REQUEST_URI} !^/(productos|souvenirs|navidad)/placeholder(/|$)
RewriteRule ^(productos|souvenirs|navidad)/([^/]+)/?$ $1/placeholder/ [L]
```

### 4. Build y Subida

```bash
# 1. Build estático
npm run build

# 2. Subir contenido de dist/ a la raíz de public_html
# 3. Subir también:
#    - api/
#    - admin/
#    - helpers/
#    - public/images/ (o solo subir las imágenes)
#    - config.php
#    - router.php (si es necesario)
#    - .htaccess
```

### 5. Estructura de Archivos en Hostinger

```
public_html/
├── index.html                    (de dist/)
├── productos/
│   ├── index.html               (de dist/productos/)
│   └── placeholder/
│       └── index.html           (de dist/productos/placeholder/)
├── souvenirs/
│   ├── index.html               (de dist/souvenirs/)
│   └── placeholder/
│       └── index.html           (de dist/souvenirs/placeholder/)
├── navidad/
│   ├── index.html               (de dist/navidad/)
│   └── placeholder/
│       └── index.html           (de dist/navidad/placeholder/)
├── .htaccess                    (actualizado para redirigir a placeholder/)
├── config.php
├── api/
├── admin/
├── helpers/
└── images/                      (o public/images/)
```

## ✅ Verificación

1. Acceder a `https://tudominio.com/productos/vela-xoxo`
2. Debe mostrar el producto (JavaScript extrae el slug de la URL)
3. El admin debe funcionar en `https://tudominio.com/admin/login.php`

## 🔄 Alternativa: Mantener Dos Configuraciones

Si quieres mantener la configuración actual para desarrollo local:

1. Crea `astro.config.dev.mjs` (actual, con `output: 'server'`)
2. Crea `astro.config.prod.mjs` (con `output: 'static'`)
3. Usa scripts en `package.json`:
   ```json
   "build:hostinger": "cp astro.config.prod.mjs astro.config.mjs && npm run build"
   ```

