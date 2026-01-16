/**
 * Script de deploy a Hostinger
 * Copia todo el contenido de dist/ excepto la carpeta /images
 * para evitar sobrescribir las imágenes existentes en el servidor
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const distDir = path.join(__dirname, '..', 'dist');
const deployDir = path.join(__dirname, '..', 'deploy');

console.log('📦 Preparando archivos para deploy a Hostinger...\n');

// Crear directorio de deploy si no existe
if (!fs.existsSync(deployDir)) {
  fs.mkdirSync(deployDir, { recursive: true });
}

function copyDirectory(src, dest, excludeDirs = []) {
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
      copyDirectory(srcPath, destPath, excludeDirs);
    } else {
      fs.copyFileSync(srcPath, destPath);
      console.log(`✅ Copiado: ${path.relative(distDir, destPath)}`);
    }
  });
}

// Copiar todo excepto /images
try {
  console.log('🔄 Copiando archivos desde dist/...\n');
  copyDirectory(distDir, deployDir, ['images']);
  
  console.log('\n✨ ¡Deploy preparado exitosamente!');
  console.log(`📁 Los archivos están en: ${path.relative(process.cwd(), deployDir)}`);
  console.log('\n📤 Para subir a Hostinger:');
  console.log('1. Sube todo el contenido de la carpeta deploy/');
  console.log('2. NO incluyas la carpeta /images (ya está en el servidor)');
  console.log('3. Las imágenes existentes no se sobrescribirán\n');
} catch (error) {
  console.error('❌ Error al preparar deploy:', error.message);
  process.exit(1);
}

