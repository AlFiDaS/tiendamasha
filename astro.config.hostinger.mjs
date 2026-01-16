// @ts-check
import { defineConfig } from 'astro/config';

// Configuración para HOSTINGER (hosting compartido, solo estático)
// https://astro.build/config
export default defineConfig({
  // 📦 OUTPUT ESTÁTICO: necesario para hosting compartido sin Node.js
  output: 'static',
  
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
      exclude: [],
    },
  },
  
  // 🔍 SEO Y METADATOS
  site: 'https://lume.com.ar',
  trailingSlash: 'never',
});

