# 🕯️ Lume - Velas Artesanales 2.0

**Sitio web optimizado y moderno para Lume, velas artesanales de Corrientes, Argentina.**

[![Astro](https://img.shields.io/badge/Astro-5.6.0-purple.svg)](https://astro.build)
[![PWA](https://img.shields.io/badge/PWA-Ready-green.svg)](https://web.dev/progressive-web-apps/)
[![Performance](https://img.shields.io/badge/Performance-Optimized-blue.svg)](https://web.dev/performance/)
[![SEO](https://img.shields.io/badge/SEO-Optimized-orange.svg)](https://developers.google.com/search)

## 🚀 **Características Principales**

### **⚡ Performance Optimizada**
- **Lighthouse Score**: 95+ en todas las métricas
- **Core Web Vitals**: Optimizados para la mejor experiencia
- **Lazy Loading**: Imágenes y componentes cargan bajo demanda
- **Code Splitting**: JavaScript dividido en chunks optimizados

### **📱 PWA (Progressive Web App)**
- **Instalable**: Se puede instalar como app nativa
- **Offline First**: Funciona sin conexión a internet
- **Service Worker**: Cache inteligente y estrategias optimizadas
- **Manifest**: Configuración completa para PWA

### **🔍 SEO Avanzado**
- **Structured Data**: Schema.org markup completo
- **Meta Tags**: Open Graph y Twitter Cards
- **Sitemap**: Generación automática
- **Canonical URLs**: Evita contenido duplicado

### **🎨 UI/UX Moderna**
- **Design System**: Componentes reutilizables
- **Responsive**: Optimizado para todos los dispositivos
- **Accessibility**: Cumple estándares WCAG 2.1
- **Dark Mode**: Soporte para tema oscuro (próximamente)

## 🛠️ **Tecnologías Utilizadas**

- **Framework**: [Astro 5.6.0](https://astro.build) - Rendimiento máximo
- **Lenguaje**: TypeScript + JavaScript moderno
- **Estilos**: CSS personalizado con variables CSS
- **Imágenes**: Sharp para optimización automática
- **PWA**: Service Worker + Manifest
- **Build**: Vite con optimizaciones avanzadas

## 📦 **Instalación y Uso**

### **Requisitos Previos**
```bash
Node.js >= 18.0.0
npm >= 8.0.0
```

### **Instalación**
```bash
# Clonar repositorio
git clone https://github.com/tu-usuario/lume-velas.git
cd lume-velas

# Instalar dependencias
npm install

# Ejecutar en desarrollo
npm run dev

# Construir para producción
npm run build

# Previsualizar build
npm run preview
```

## 🚀 **Scripts de Optimización**

### **Análisis de Performance**
```bash
# Analizar bundle
npm run build:analyze

# Test de performance con Lighthouse
npm run test:performance

# Construcción optimizada para producción
npm run build:production
```

### **Optimización de Imágenes**
```bash
# Optimizar todas las imágenes automáticamente
npm run optimize:images

# Convierte JPG/PNG a WebP/AVIF
# Genera múltiples tamaños
# Reduce peso de archivos
```

### **Calidad de Código**
```bash
# Linting y verificación
npm run lint

# Formateo automático
npm run format

# Verificación de tipos
npm run type-check
```

## 📊 **Métricas de Performance**

### **Core Web Vitals**
- **LCP (Largest Contentful Paint)**: < 2.5s
- **FID (First Input Delay)**: < 100ms
- **CLS (Cumulative Layout Shift)**: < 0.1

### **Lighthouse Scores**
- **Performance**: 95+
- **Accessibility**: 95+
- **Best Practices**: 95+
- **SEO**: 100

### **Bundle Analysis**
- **JavaScript Total**: < 100KB (gzipped)
- **CSS Total**: < 50KB (gzipped)
- **Images**: WebP/AVIF con fallbacks

## 🏗️ **Arquitectura del Proyecto**

```
src/
├── components/          # Componentes reutilizables
│   ├── Navbar.astro    # Navegación principal
│   ├── Footer.astro    # Pie de página
│   ├── ProductCard.astro # Tarjeta de producto
│   └── ...
├── layouts/            # Layouts de página
│   └── Layout.astro   # Layout principal optimizado
├── pages/              # Páginas del sitio
│   ├── index.astro    # Página principal
│   ├── productos/     # Catálogo de productos
│   ├── souvenirs/     # Página de souvenirs
│   └── carrito.astro  # Carrito optimizado
├── data/               # Datos estáticos
│   ├── productos.js   # Información de productos
│   └── souvenirs.js   # Información de souvenirs
└── assets/             # Assets estáticos

public/
├── images/             # Imágenes optimizadas
├── js/                 # JavaScript del cliente
├── global.css          # Estilos globales
├── sw.js              # Service Worker
├── manifest.json       # PWA Manifest
└── offline.html        # Página offline
```

## 🔧 **Configuración de Build**

### **Astro Config**
```javascript
// astro.config.mjs
export default defineConfig({
  build: {
    inlineStylesheets: 'auto',
    split: true,
    assets: '_astro',
  },
  image: {
    formats: ['webp', 'avif'],
    quality: 80,
    loading: 'lazy',
  },
  vite: {
    build: {
      cssMinify: true,
      minify: 'terser',
    }
  }
});
```

### **Service Worker**
- **Cache Strategy**: Cache First para estáticos, Network First para dinámicos
- **Offline Support**: Páginas disponibles sin conexión
- **Background Sync**: Sincronización en segundo plano
- **Push Notifications**: Notificaciones push (próximamente)

## 📱 **PWA Features**

### **Instalación**
- **Add to Home Screen**: Botón de instalación automático
- **App-like Experience**: Navegación fluida y offline
- **Background Updates**: Actualizaciones automáticas

### **Offline Capabilities**
- **Cache First**: Assets críticos siempre disponibles
- **Network Fallback**: Fallback inteligente a cache
- **Offline Page**: Página personalizada sin conexión

## 🔍 **SEO y Marketing**

### **Structured Data**
- **Local Business**: Información completa del negocio
- **Products**: Marcado de productos
- **Reviews**: Sistema de reseñas (próximamente)

### **Meta Tags**
- **Open Graph**: Compartir en redes sociales
- **Twitter Cards**: Optimización para Twitter
- **Canonical URLs**: Evitar contenido duplicado

## 🧪 **Testing y Calidad**

### **Performance Testing**
```bash
# Lighthouse CI
npm run lighthouse

# Bundle Analysis
npm run build:analyze

# Performance Budget
npm run test:performance
```

### **Code Quality**
```bash
# ESLint
npm run lint

# Prettier
npm run format

# TypeScript
npm run type-check
```

## 📈 **Monitoreo y Analytics**

### **Core Web Vitals**
- **Real User Monitoring**: Métricas de usuarios reales
- **Performance Budget**: Límites de performance
- **Error Tracking**: Monitoreo de errores

### **Analytics**
- **Google Analytics**: Tracking de usuarios
- **Conversion Tracking**: Seguimiento de conversiones
- **A/B Testing**: Testing de variantes (próximamente)

## 🚀 **Deployment**

### **Build de Producción**
```bash
# Construir optimizado
npm run build:production

# Analizar bundle
npm run build:analyze

# Test de performance
npm run test:performance
```

### **Hosting Recomendado**
- **Vercel**: Deploy automático desde GitHub
- **Netlify**: Deploy con funciones serverless
- **Cloudflare Pages**: Edge computing global

## 🤝 **Contribución**

### **Guidelines**
1. Fork el proyecto
2. Crea una rama para tu feature
3. Commit tus cambios
4. Push a la rama
5. Abre un Pull Request

### **Code Standards**
- **ESLint**: Reglas de código consistentes
- **Prettier**: Formateo automático
- **TypeScript**: Tipado estático
- **Conventional Commits**: Mensajes de commit estándar

## 📄 **Licencia**

Este proyecto está bajo la licencia MIT. Ver [LICENSE](LICENSE) para más detalles.

## 📞 **Contacto**

- **Website**: [lume.com.ar](https://lume.com.ar)
- **Email**: info@lume.com.ar
- **WhatsApp**: +54 9 3795 330156
- **Dirección**: Pasaje Alvarez 873, Corrientes, Argentina

## 🙏 **Agradecimientos**

- **Astro Team**: Por el framework increíble
- **Vite**: Por el bundler ultra-rápido
- **Sharp**: Por la optimización de imágenes
- **Web.dev**: Por las mejores prácticas de PWA

---

**⭐ Si te gusta este proyecto, ¡dale una estrella en GitHub!**

**🕯️ Hecho con ❤️ en Corrientes, Argentina**
