// @ts-check
import { defineConfig } from 'astro/config';

// Detectar si estamos en modo build o desarrollo
const isBuild = process.env.NODE_ENV === 'production' || process.argv.includes('build');

// https://astro.build/config
export default defineConfig({
  // 📦 OUTPUT CONDICIONAL:
  // - En desarrollo: sin output (default SSR, permite prerender=false sin adapter)
  // - En build: 'static' para hosting compartido como Hostinger (solo PHP, no Node.js)
  // Las rutas dinámicas usan prerender=false en dev y placeholder en build
  ...(isBuild ? { output: 'static' } : {}),
  
  // 🚀 OPTIMIZACIONES DE RENDIMIENTO
  build: {
    // Minificar HTML, CSS y JS
    inlineStylesheets: 'auto',
    split: true,
    assets: '_astro',
  },
  
  // ⚡ COMPRESIÓN Y MINIFICACIÓN
  vite: {
    build: {
      // Minificar CSS
      cssMinify: true,
      // Chunk splitting optimizado
      rollupOptions: {
        output: {
          manualChunks: {
            vendor: ['@splidejs/splide'],
          }
        }
      }
    },
    // Optimizaciones de Vite
    optimizeDeps: {
      include: ['@splidejs/splide']
    },
    // 🔧 DESHABILITAR TRANSFORMACIONES DE CONSOLE NINJA
    esbuild: {
      legalComments: 'none',
      // Ignorar código inyectado por extensiones de debugging
      exclude: [],
    },
    // 🔄 PROXY: Redirigir peticiones a /api/ al servidor PHP
    server: {
      proxy: {
        '/api': {
          target: 'http://localhost:8081',
          changeOrigin: true,
          secure: false,
        }
      }
    }
  },
  
  // 🔍 SEO Y METADATOS
  site: 'https://lume.com.ar',
  trailingSlash: 'never',
});
