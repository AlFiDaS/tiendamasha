# 📦 Guía: Subir Build a Hostinger

## ✅ Después de ejecutar `npm run build`

### 📁 **1. CONTENIDO DE `dist/` → Raíz del Sitio**

Sube **TODO** el contenido de la carpeta `dist/` a la **raíz** de tu sitio en Hostinger (reemplaza los archivos existentes):

```
dist/
├── index.html                    → raíz/
├── global.css                    → raíz/
├── sw.js                         → raíz/
├── manifest.json                 → raíz/
├── offline.html                  → raíz/
├── favicon.svg                   → raíz/
├── _astro/                       → raíz/_astro/
│   └── (CSS y JS compilados)
├── js/                           → raíz/js/
│   ├── cart.js
│   ├── product-detail.js
│   ├── products-loader.js
│   └── ...
├── images/                       → raíz/images/
│   └── (todas las imágenes)
├── productos/                    → raíz/productos/
│   ├── index.html
│   └── placeholder/
├── souvenirs/                    → raíz/souvenirs/
│   ├── index.html
│   └── placeholder/
├── navidad/                      → raíz/navidad/
│   ├── index.html
│   └── placeholder/
├── carrito/                      → raíz/carrito/
│   └── index.html
└── ideas/                        → raíz/ideas/
    └── index.html
```

### 📁 **2. ARCHIVOS PHP (NO están en dist/, mantenerlos)**

Estos archivos **NO** se generan en el build, están en la raíz del proyecto. **Mantén estos archivos** en Hostinger:

```
✅ admin/                         (toda la carpeta, incluye categorias/)
✅ api/                           (toda la carpeta, incluye categories.php nuevo)
✅ helpers/                       (toda la carpeta, incluye categories.php nuevo)
✅ config.php                     (configuración de base de datos)
✅ .htaccess                      (configuración Apache)
```

### ⚠️ **IMPORTANTE: Nuevos archivos creados**

Asegúrate de subir estos archivos nuevos que no estaban antes:

- ✅ `api/categories.php` (nuevo endpoint para categorías)
- ✅ `helpers/categories.php` (helper para categorías)
- ✅ `admin/categorias/` (toda la carpeta nueva)
  - `admin/categorias/list.php`
  - `admin/categorias/add.php`
  - `admin/categorias/edit.php`
  - `admin/categorias/delete.php`

### 🔄 **Pasos Recomendados**

1. **Hacer backup** de tu sitio actual en Hostinger (por si acaso)

2. **Subir contenido de dist/**:
   - Conecta por FTP/SFTP a Hostinger
   - Navega a la carpeta raíz del sitio (ej: `public_html/` o `lumetest/`)
   - Sube **todo** el contenido de `dist/` (reemplaza archivos existentes)

3. **Verificar archivos PHP**:
   - Asegúrate de que `admin/`, `api/`, `helpers/`, `config.php` y `.htaccess` estén presentes
   - Si faltan, súbelos desde la raíz del proyecto (no desde dist/)

4. **Verificar archivos nuevos**:
   - Confirma que `api/categories.php` existe
   - Confirma que `helpers/categories.php` existe
   - Confirma que `admin/categorias/` existe con todos sus archivos

5. **Probar**:
   - Visita el sitio y verifica que las categorías se cargan en el navbar
   - Verifica que puedes acceder a `admin/categorias/list.php`
   - Verifica que las categorías ocultas no aparecen en el navbar público

### 📝 **Estructura Final en Hostinger**

```
raíz-del-sitio/
├── index.html                    (desde dist/)
├── global.css                    (desde dist/)
├── .htaccess                     (desde raíz del proyecto)
├── config.php                    (desde raíz del proyecto)
├── admin/                        (desde raíz del proyecto)
│   ├── categorias/               (NUEVO - desde raíz del proyecto)
│   │   ├── list.php
│   │   ├── add.php
│   │   ├── edit.php
│   │   └── delete.php
│   └── ...
├── api/                          (desde raíz del proyecto)
│   ├── categories.php            (NUEVO - desde raíz del proyecto)
│   ├── products.php
│   └── galeria.php
├── helpers/                      (desde raíz del proyecto)
│   ├── categories.php            (NUEVO - desde raíz del proyecto)
│   ├── db.php
│   └── ...
├── _astro/                       (desde dist/)
├── js/                           (desde dist/)
├── images/                       (desde dist/)
└── productos/, souvenirs/, etc.  (desde dist/)
```

### 🎯 **Resumen Rápido**

```
✅ Copiar TODO dist/ → raíz del sitio (reemplazar)
✅ Mantener admin/, api/, helpers/, config.php, .htaccess en la raíz
✅ Asegurarse de subir archivos nuevos: api/categories.php, helpers/categories.php, admin/categorias/
```

