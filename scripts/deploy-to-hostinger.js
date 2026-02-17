/**
 * Script de deploy a Hostinger
 * Copia todo el contenido de dist/ excepto:
 * - La carpeta /images (para no sobrescribir imágenes del servidor)
 * - El archivo .htaccess (configuración Apache del servidor)
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const distDir = path.join(__dirname, '..', 'dist');
const deployDir = path.join(__dirname, '..', 'deploy');

const EXCLUDE_DIRS = ['images'];
const EXCLUDE_FILES = ['.htaccess'];

console.log('📦 Preparando archivos para deploy a Hostinger...\n');

// Crear directorio de deploy si no existe
if (!fs.existsSync(deployDir)) {
  fs.mkdirSync(deployDir, { recursive: true });
}

function copyDirectory(src, dest, excludeDirs = [], excludeFiles = []) {
  // Verificar si el directorio debe ser excluido
  const relativePath = path.relative(src, path.dirname(dest));
  const dirName = path.basename(dest);
  
  if (excludeDirs.includes(dirName) || excludeDirs.some(exclude => relativePath.includes(exclude))) {
    console.log(`⏭️  Excluyendo: ${path.relative(distDir, dest)}`);
    return;
  }

  if (!fs.existsSync(dest)) {
    fs.mkdirSync(dest, { recursive: true });
  }

  const files = fs.readdirSync(src);

  files.forEach((file) => {
    const srcPath = path.join(src, file);
    const destPath = path.join(dest, file);
    const stat = fs.statSync(srcPath);

    if (stat.isDirectory()) {
      copyDirectory(srcPath, destPath, excludeDirs, excludeFiles);
    } else if (excludeFiles.includes(file)) {
      console.log(`⏭️  Excluyendo: ${path.relative(distDir, destPath)}`);
    } else {
      fs.copyFileSync(srcPath, destPath);
      console.log(`✅ Copiado: ${path.relative(distDir, destPath)}`);
    }
  });
}

// Copiar todo excepto /images y .htaccess
try {
  console.log('🔄 Copiando archivos desde dist/...\n');
  copyDirectory(distDir, deployDir, EXCLUDE_DIRS, EXCLUDE_FILES);
  
  console.log('\n✨ ¡Deploy preparado exitosamente!');
  console.log(`📁 Los archivos están en: ${path.relative(process.cwd(), deployDir)}`);
  console.log('\n📤 Para subir a Hostinger:');
  console.log('1. Sube todo el contenido de la carpeta deploy/');
  console.log('2. NO incluyas la carpeta /images (ya está en el servidor)');
  console.log('3. NO incluyas el archivo .htaccess (configuración del servidor)');
  console.log('4. Las imágenes existentes no se sobrescribirán\n');
} catch (error) {
  console.error('❌ Error al preparar deploy:', error.message);
  process.exit(1);
}

