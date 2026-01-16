// 🔄 FORZAR ACTUALIZACIÓN - Versión agresiva para limpiar cache
const CURRENT_VERSION = '2.1.0-2025-09-03T22-17-43-2025-09-03T22-16-50-2025-09-03T22-13-49';

(function() {
  'use strict';
  
  // Prevenir múltiples ejecuciones
  if (window.__FORCE_UPDATE_LOADED) {
    return;
  }
  window.__FORCE_UPDATE_LOADED = true;
  
  console.log('🔄 Force Update iniciado, versión:', CURRENT_VERSION);
  
  const VERSION_KEY = 'lume_cache_version';
  const RELOAD_FLAG = 'lume_reload_in_progress';
  const storedVersion = localStorage.getItem(VERSION_KEY);
  const isReloading = sessionStorage.getItem(RELOAD_FLAG);
  
  // Si ya estamos en proceso de recarga, no hacer nada más
  if (isReloading) {
    console.log('⏸️ Recarga en progreso, esperando...');
    // Limpiar el flag después de un momento
    setTimeout(() => {
      sessionStorage.removeItem(RELOAD_FLAG);
    }, 2000);
    return;
  }
  
  // Función para limpiar TODOS los caches
  function clearAllCaches() {
    if ('caches' in window) {
      return caches.keys().then(cacheNames => {
        console.log('🗑️ Eliminando', cacheNames.length, 'caches:', cacheNames);
        return Promise.all(cacheNames.map(name => {
          console.log('🗑️ Eliminando cache:', name);
          return caches.delete(name);
        }));
      }).then(() => {
        console.log('✅ Todos los caches eliminados');
      });
    }
    return Promise.resolve();
  }
  
  // Función para desregistrar TODOS los Service Workers
  function unregisterAllSWs() {
    if ('serviceWorker' in navigator) {
      return navigator.serviceWorker.getRegistrations().then(registrations => {
        console.log('🗑️ Desregistrando', registrations.length, 'Service Workers');
        return Promise.all(registrations.map(reg => {
          console.log('🗑️ Desregistrando SW:', reg.scope);
          return reg.unregister();
        }));
      }).then(() => {
        console.log('✅ Todos los Service Workers desregistrados');
      });
    }
    return Promise.resolve();
  }
  
  // Si la versión cambió O si no hay versión guardada, limpiar TODO
  if (!storedVersion || storedVersion !== CURRENT_VERSION) {
    console.log('🔄 NUEVA VERSIÓN DETECTADA - Limpiando TODO...', {
      stored: storedVersion,
      current: CURRENT_VERSION
    });
    
    // Marcar que estamos recargando
    sessionStorage.setItem(RELOAD_FLAG, '1');
    
    // Limpiar todo
    Promise.all([
      clearAllCaches(),
      unregisterAllSWs()
    ]).then(() => {
      console.log('✅ Limpieza completa');
      
      // Actualizar versión
      localStorage.setItem(VERSION_KEY, CURRENT_VERSION);
      
      // Recargar página forzando sin cache
      console.log('🔄 Recargando página sin cache...');
      setTimeout(() => {
        // Usar location.href con timestamp para evitar cache
        const url = new URL(window.location.href);
        url.searchParams.set('nocache', Date.now());
        window.location.href = url.toString();
      }, 300);
    }).catch(err => {
      console.error('❌ Error al limpiar:', err);
      // Aun así, actualizar versión y recargar
      localStorage.setItem(VERSION_KEY, CURRENT_VERSION);
      setTimeout(() => {
        const url = new URL(window.location.href);
        url.searchParams.set('nocache', Date.now());
        window.location.href = url.toString();
      }, 300);
    });
    
    return; // Salir
  }
  
  // Si hay flag de recarga, limpiarlo
  if (isReloading) {
    sessionStorage.removeItem(RELOAD_FLAG);
  }
  
  // Verificar actualización del SW periódicamente (sin recargar)
  if ('serviceWorker' in navigator) {
    function checkSW() {
      navigator.serviceWorker.getRegistration().then(reg => {
        if (reg) {
          reg.update().catch(() => {});
        }
      }).catch(() => {});
    }
    
    // Verificar cada 5 minutos
    setInterval(checkSW, 5 * 60 * 1000);
    
    // Verificar cuando vuelve a primer plano
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        setTimeout(checkSW, 1000);
      }
    });
  }
})();
