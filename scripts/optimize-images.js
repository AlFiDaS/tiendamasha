#!/usr/bin/env node

/**
 * 🖼️ SCRIPT AVANZADO DE OPTIMIZACIÓN DE IMÁGENES
 * Optimiza automáticamente todas las imágenes del proyecto con múltiples formatos y tamaños
 */

import fs from 'fs/promises';
import path from 'path';
import sharp from 'sharp';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// 📁 DIRECTORIOS A OPTIMIZAR
const IMAGE_DIRS = [
  'public/images',
  'src/assets'
];

// 🎯 FORMATOS DE SALIDA OPTIMIZADOS
const OUTPUT_FORMATS = ['webp', 'avif'];
const QUALITY = {
  webp: 85,
  avif: 80
};

// 📏 TAMAÑOS RESPONSIVE PARA DIFERENTES DISPOSITIVOS
const IMAGE_SIZES = {
  xs: 150,      // Extra small (móviles pequeños)
  sm: 300,      // Small (móviles)
  md: 600,      // Medium (tablets)
  lg: 900,      // Large (desktops pequeños)
  xl: 1200,     // Extra large (desktops)
  xxl: 1600     // 2x para pantallas de alta densidad
};

// 🔍 EXTENSIONES DE IMAGEN SOPORTADAS
const IMAGE_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.tiff'];

// 📊 ESTADÍSTICAS DE OPTIMIZACIÓN
class ImageOptimizer {
  constructor() {
    this.stats = {
      processed: 0,
      optimized: 0,
      errors: 0,
      totalSizeBefore: 0,
      totalSizeAfter: 0,
      timeSaved: 0
    };
  }

  /**
   * Verifica si un archivo es una imagen
   */
  isImageFile(filename) {
    const ext = path.extname(filename).toLowerCase();
    return IMAGE_EXTENSIONS.includes(ext);
  }

  /**
   * Obtiene información de la imagen original
   */
  async getImageInfo(inputPath) {
    try {
      const metadata = await sharp(inputPath).metadata();
      const stats = await fs.stat(inputPath);
      return {
        width: metadata.width,
        height: metadata.height,
        format: metadata.format,
        size: stats.size
      };
    } catch (error) {
      console.error(`❌ Error obteniendo info de ${inputPath}:`, error.message);
      return null;
    }
  }

  /**
   * Optimiza una imagen individual con múltiples formatos y tamaños
   */
  async optimizeImage(inputPath, outputDir) {
    try {
      const filename = path.basename(inputPath, path.extname(inputPath));
      const imageInfo = await this.getImageInfo(inputPath);
      
      if (!imageInfo) return;

      this.stats.totalSizeBefore += imageInfo.size;
      
      // Crear directorio de salida si no existe
      await fs.mkdir(outputDir, { recursive: true });

      // Optimizar en diferentes formatos y tamaños
      for (const format of OUTPUT_FORMATS) {
        for (const [sizeName, size] of Object.entries(IMAGE_SIZES)) {
          const outputPath = path.join(outputDir, `${filename}-${sizeName}.${format}`);
          
          // Calcular proporciones para mantener aspect ratio
          const aspectRatio = imageInfo.width / imageInfo.height;
          let targetWidth = size;
          let targetHeight = Math.round(size / aspectRatio);
          
          // Asegurar que no exceda las dimensiones originales
          if (targetWidth > imageInfo.width) {
            targetWidth = imageInfo.width;
            targetHeight = imageInfo.height;
          }
          
          await sharp(inputPath)
            .resize(targetWidth, targetHeight, {
              fit: 'inside',
              withoutEnlargement: true,
              background: { r: 255, g: 255, b: 255, alpha: 1 }
            })
            .toFormat(format, { 
              quality: QUALITY[format],
              effort: format === 'avif' ? 6 : undefined, // Máximo esfuerzo para AVIF
              preset: format === 'webp' ? 'photo' : undefined
            })
            .toFile(outputPath);

          // Obtener tamaño del archivo optimizado
          const optimizedStats = await fs.stat(outputPath);
          this.stats.totalSizeAfter += optimizedStats.size;
          
          this.stats.optimized++;
        }
      }

      this.stats.processed++;
      console.log(`✅ Optimizada: ${inputPath} → ${Object.keys(IMAGE_SIZES).length * OUTPUT_FORMATS.length} archivos`);
      
    } catch (error) {
      console.error(`❌ Error optimizando ${inputPath}:`, error.message);
      this.stats.errors++;
    }
  }

  /**
   * Procesa un directorio completo recursivamente
   */
  async processDirectory(dirPath) {
    try {
      const files = await fs.readdir(dirPath);
      
      for (const file of files) {
        const fullPath = path.join(dirPath, file);
        const stat = await fs.stat(fullPath);
        
        if (stat.isDirectory()) {
          // Procesar subdirectorios
          await this.processDirectory(fullPath);
        } else if (this.isImageFile(file)) {
          // Optimizar imagen
          const outputDir = path.join(dirPath, 'optimized');
          await this.optimizeImage(fullPath, outputDir);
        }
      }
    } catch (error) {
      console.error(`❌ Error procesando directorio ${dirPath}:`, error.message);
    }
  }

  /**
   * Crea un archivo de configuración para las imágenes optimizadas
   */
  async createImageConfig() {
    const configPath = path.join(__dirname, '..', 'public', 'image-config.json');
    const config = {
      formats: OUTPUT_FORMATS,
      sizes: IMAGE_SIZES,
      quality: QUALITY,
      lastUpdated: new Date().toISOString(),
      stats: this.stats
    };
    
    try {
      await fs.writeFile(configPath, JSON.stringify(config, null, 2));
      console.log('📝 Configuración de imágenes guardada');
    } catch (error) {
      console.error('❌ Error guardando configuración:', error.message);
    }
  }

  /**
   * Ejecuta la optimización completa
   */
  async run() {
    console.log('🚀 Iniciando optimización avanzada de imágenes...\n');
    console.log('📊 Configuración:');
    console.log(`   • Formatos: ${OUTPUT_FORMATS.join(', ')}`);
    console.log(`   • Tamaños: ${Object.keys(IMAGE_SIZES).join(', ')}`);
    console.log(`   • Calidad WebP: ${QUALITY.webp}%`);
    console.log(`   • Calidad AVIF: ${QUALITY.avif}%\n`);
    
    const startTime = Date.now();
    
    for (const dir of IMAGE_DIRS) {
      const fullPath = path.join(__dirname, '..', dir);
      
      try {
        await fs.access(fullPath);
        console.log(`📁 Procesando: ${dir}`);
        await this.processDirectory(fullPath);
      } catch (error) {
        console.log(`⚠️  Directorio no encontrado: ${dir}`);
      }
    }
    
    const endTime = Date.now();
    const duration = ((endTime - startTime) / 1000).toFixed(2);
    
    // Calcular ahorro de espacio
    const spaceSaved = this.stats.totalSizeBefore - this.stats.totalSizeAfter;
    const spaceSavedKB = Math.round(spaceSaved / 1024);
    const spaceSavedMB = (spaceSaved / (1024 * 1024)).toFixed(2);
    
    this.printStats(duration, spaceSavedKB, spaceSavedMB);
    await this.createImageConfig();
  }

  /**
   * Muestra estadísticas finales detalladas
   */
  printStats(duration, spaceSavedKB, spaceSavedMB) {
    console.log('\n📊 ESTADÍSTICAS FINALES:');
    console.log('═'.repeat(60));
    console.log(`⏱️  Duración total: ${duration}s`);
    console.log(`📁 Archivos procesados: ${this.stats.processed}`);
    console.log(`✨ Imágenes optimizadas: ${this.stats.optimized}`);
    console.log(`❌ Errores: ${this.stats.errors}`);
    console.log(`💾 Espacio ahorrado: ${spaceSavedKB}KB (${spaceSavedMB}MB)`);
    console.log(`📈 Reducción: ${Math.round((spaceSavedKB / (this.stats.totalSizeBefore / 1024)) * 100)}%`);
    console.log('═'.repeat(60));
    
    if (this.stats.errors === 0) {
      console.log('🎉 ¡Optimización completada exitosamente!');
      console.log(`💡 Tip: Las imágenes optimizadas están en carpetas 'optimized'`);
    } else {
      console.log('⚠️  Optimización completada con algunos errores');
    }
  }
}

// 🚀 EJECUTAR OPTIMIZADOR
const optimizer = new ImageOptimizer();
optimizer.run().catch(console.error);
