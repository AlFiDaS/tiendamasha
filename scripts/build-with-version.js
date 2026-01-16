#!/usr/bin/env node

// 🚀 SCRIPT DE BUILD CON VERSIÓN - Lume 2.1.0
// Genera un timestamp único para cada build y actualiza las versiones

import { execSync } from 'child_process';
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const projectRoot = join(__dirname, '..');

// 📅 GENERAR TIMESTAMP ÚNICO
const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
const version = `2.1.0-${timestamp}`;

console.log(`🚀 Iniciando build de Lume ${version}`);
console.log(`📅 Timestamp: ${timestamp}`);

// 🔄 ACTUALIZAR VERSIONES EN ARCHIVOS
function updateVersions() {
  console.log('📝 Actualizando versiones...');
  
  // Actualizar package.json
  const packagePath = join(projectRoot, 'package.json');
  const packageContent = readFileSync(packagePath, 'utf8');
  const updatedPackage = packageContent.replace(/"version": "[^"]*"/, `"version": "${version}"`);
  writeFileSync(packagePath, updatedPackage);
  
  // Actualizar manifest.json
  const manifestPath = join(projectRoot, 'public', 'manifest.json');
  const manifestContent = readFileSync(manifestPath, 'utf8');
  const updatedManifest = manifestContent.replace(/"version": "[^"]*"/, `"version": "${version}"`);
  writeFileSync(manifestPath, updatedManifest);
  
  // Actualizar Layout.astro
  const layoutPath = join(projectRoot, 'src', 'layouts', 'Layout.astro');
  let layoutContent = readFileSync(layoutPath, 'utf8');
  layoutContent = layoutContent.replace(/v=2\.1\.0/g, `v=${version}`);
  writeFileSync(layoutPath, layoutContent);
  
  // Actualizar force-update.js
  const forceUpdatePath = join(projectRoot, 'public', 'js', 'force-update.js');
  let forceUpdateContent = readFileSync(forceUpdatePath, 'utf8');
  forceUpdateContent = forceUpdateContent.replace(/CURRENT_VERSION = '2\.1\.0'/, `CURRENT_VERSION = '${version}'`);
  writeFileSync(forceUpdatePath, forceUpdateContent);
  
  // Actualizar sw.js
  const swPath = join(projectRoot, 'public', 'sw.js');
  let swContent = readFileSync(swPath, 'utf8');
  swContent = swContent.replace(/const CACHE_NAME = '[^']*';/, `const CACHE_NAME = 'lume-${version}';`);
  swContent = swContent.replace(/const STATIC_CACHE = '[^']*';/, `const STATIC_CACHE = 'lume-static-${version}';`);
  swContent = swContent.replace(/const DYNAMIC_CACHE = '[^']*';/, `const DYNAMIC_CACHE = 'lume-dynamic-${version}';`);
  writeFileSync(swPath, swContent);
  
  console.log('✅ Versiones actualizadas');
}

// 🏗️ EJECUTAR BUILD
function runBuild() {
  console.log('🏗️ Ejecutando build...');
  
  try {
    // Limpiar dist (compatible con Windows y Unix)
    try {
      if (process.platform === 'win32') {
        execSync('if exist dist rmdir /s /q dist', { cwd: projectRoot, stdio: 'inherit' });
      } else {
        execSync('rm -rf dist', { cwd: projectRoot, stdio: 'inherit' });
      }
      console.log('🧹 Dist limpio');
    } catch (error) {
      console.log('ℹ️ Dist ya estaba limpio o no existía');
    }
    
    // Instalar dependencias si es necesario
    if (!existsSync(join(projectRoot, 'node_modules'))) {
      console.log('📦 Instalando dependencias...');
      execSync('npm install', { cwd: projectRoot, stdio: 'inherit' });
    }
    
    // Ejecutar build
    execSync('npm run build', { cwd: projectRoot, stdio: 'inherit' });
    console.log('✅ Build completado exitosamente');
    
    // Mostrar información del build
    console.log('\n🎉 BUILD COMPLETADO');
    console.log(`📱 Versión: ${version}`);
    console.log(`📅 Timestamp: ${timestamp}`);
    console.log(`📁 Output: dist/`);
    console.log('\n🚀 Para subir a producción:');
    console.log('1. Subir todo el contenido de dist/');
    console.log('2. Incluir el archivo .htaccess');
    console.log('3. Verificar que el service worker se actualice');
    
  } catch (error) {
    console.error('❌ Error durante el build:', error.message);
    process.exit(1);
  }
}

// 🚀 EJECUTAR SCRIPT
try {
  updateVersions();
  runBuild();
} catch (error) {
  console.error('❌ Error fatal:', error.message);
  process.exit(1);
}
