# 🔍 ANÁLISIS: Rutas Dinámicas de Productos - Problema y Solución

## 1. ESTRUCTURA ACTUAL IDENTIFICADA

### Rutas Dinámicas Encontradas:
- ✅ `src/pages/productos/[slug].astro`
- ✅ `src/pages/souvenirs/[slug].astro`
- ✅ `src/pages/navidad/[slug].astro`

### Configuración Actual:
```javascript
// En cada [slug].astro:
export const prerender = false;
const slug = Astro.params.slug || '';
```

### Problema Detectado:
- ❌ **NO tienen `getStaticPaths()`**
- ❌ **NO tienen `output: 'static'` en `astro.config.mjs`**
- ✅ Tienen `prerender = false` que funciona en desarrollo pero puede fallar en producción

---

## 2. ANÁLISIS DEL PROBLEMA

### ¿Por qué funciona EN DESARROLLO pero puede fallar EN PRODUCCIÓN?

#### Estado Actual (Funciona en Dev):
- `prerender = false` permite renderizado dinámico en desarrollo
- Sin `output: 'static'`, Astro usa modo SSR/híbrido por defecto
- El servidor de desarrollo (`astro dev`) soporta SSR
- `Astro.params.slug` funciona porque la página se renderiza dinámicamente

#### ¿Por qué puede fallar en producción?
1. **Hosting compartido NO soporta SSR**: Si intentas hacer `npm run build` sin `output: 'static'`, Astro intentará generar un servidor Node.js que requiere SSR
2. **Sin archivos HTML estáticos**: Al hacer build, no se generan archivos HTML para las rutas dinámicas
3. **Modo híbrido no funciona**: Si configuras `output: 'static'` pero mantienes `prerender = false`, el build fallará o no generará los archivos necesarios

---

## 3. CAUSA RAÍZ DEL ERROR

### Error Principal:
**Las rutas dinámicas NO están pre-generadas para producción estática**

### Escenario Problemático:
1. Usuario hace `npm run build`
2. Astro NO genera archivos HTML para `/productos/[slug]` porque:
   - No hay `getStaticPaths()` que defina qué slugs generar
   - `prerender = false` le dice a Astro que NO pre-renderice
3. En producción (hosting compartido):
   - No hay servidor Node.js (no soporta SSR)
   - No hay archivos HTML estáticos generados
   - Resultado: **404 Not Found**

---

## 4. SOLUCIÓN CORRECTA PARA HOSTING COMPARTIDO

Para que funcione en **hosting compartido** (solo archivos estáticos), necesitas:

### Opción A: Modo Estático con Carga del Cliente (Recomendada)

**Configuración necesaria:**

1. **`astro.config.mjs`**:
```javascript
export default defineConfig({
  output: 'static',  // ✅ CRÍTICO para hosting compartido
  // ... resto de config
});
```

2. **`[slug].astro`** - Debe usar `getStaticPaths()` que retorna `[]`:
```javascript
export async function getStaticPaths() {
  return []; // Array vacío = no pre-generar rutas
}
```

3. **`.htaccess`** en la raíz (para redirecciones):
```apache
RewriteRule ^productos/(.+)$ /productos.html?slug=$1 [L,QSA]
RewriteRule ^souvenirs/(.+)$ /souvenirs.html?slug=$1 [L,QSA]
RewriteRule ^navidad/(.+)$ /navidad.html?slug=$1 [L,QSA]
```

4. **Archivos HTML estáticos** en `public/`:
   - `public/productos.html`
   - `public/souvenirs.html`
   - `public/navidad.html`

### Opción B: Mantener Modo SSR (NO funciona en hosting compartido)

Si mantienes `prerender = false` sin `output: 'static'`:
- ✅ Funciona en desarrollo
- ❌ NO funciona en hosting compartido (requiere servidor Node.js)
- ❌ Build falla o genera código SSR que no se puede ejecutar

---

## 5. ARCHIVOS QUE NECESITAN CAMBIOS

### Archivos a modificar:

1. ✅ `astro.config.mjs` - Agregar `output: 'static'`
2. ✅ `src/pages/productos/[slug].astro` - Agregar `getStaticPaths()` que retorne `[]`
3. ✅ `src/pages/souvenirs/[slug].astro` - Agregar `getStaticPaths()` que retorne `[]`
4. ✅ `src/pages/navidad/[slug].astro` - Agregar `getStaticPaths()` que retorne `[]`
5. ⚠️ `.htaccess` - Agregar reglas de rewrite (si no existen)
6. ⚠️ Crear archivos HTML estáticos en `public/` O usar una estrategia diferente

---

## 6. PROBLEMA ESPECÍFICO CON `prerender = false`

### Con `prerender = false` y `output: 'static'`:
- ⚠️ **INCOMPATIBLE**: Astro no puede generar archivos estáticos si `prerender = false`
- Error en build: `Cannot use prerender = false with output = 'static'`

### Solución:
- **Para desarrollo**: Puedes usar `prerender = false` (como está ahora)
- **Para producción estática**: Necesitas:
  1. Quitar `prerender = false` O
  2. Usar `getStaticPaths()` que retorne `[]` y archivos HTML en `public/`

---

## 7. CONCLUSIÓN

### Estado Actual:
✅ Funciona en desarrollo (modo SSR/híbrido)
❌ NO funcionará en producción con hosting compartido

### Para que funcione en producción estática:
1. Configurar `output: 'static'` en `astro.config.mjs`
2. Quitar `prerender = false` y usar `getStaticPaths()` con `[]`
3. Crear archivos HTML estáticos en `public/` O configurar `.htaccess` correctamente

### Recomendación:
Usar la **Opción A** descrita arriba para tener una solución que funcione tanto en desarrollo como en producción estática.
