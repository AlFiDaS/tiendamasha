// 🚀 SERVICE WORKER OPTIMIZADO - Lume 2.1.10
const CACHE_NAME = 'lume-2.1.10-2026-01-03T17-30-00';
const STATIC_CACHE = 'lume-static-2.1.10-2026-01-03T17-30-00';
const DYNAMIC_CACHE = 'lume-dynamic-2.1.10-2026-01-03T17-30-00';

// 📱 ESTRATEGIAS DE CACHE
const STATIC_ASSETS = [
  '/',
  '/global.css',
  '/js/cart.js',
  '/js/carrito.js',
  '/js/slider.js',
  '/js/search.js',
  '/js/souvenir-search.js',
  '/favicon.svg',
  '/images/lume-logo.png',
  '/images/lume-logo-blanco.png',
  '/images/hero.webp',
  '/images/hero2.webp',
  '/images/hero3.webp'
];

// 🎯 INSTALACIÓN DEL SW
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('🔄 Cacheando assets estáticos...');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('✅ Service Worker instalado correctamente');
        return self.skipWaiting();
      })
  );
});

// 🔄 ACTIVACIÓN DEL SW
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          // Eliminar TODOS los caches que no coincidan con los nombres actuales
          if (!cacheName.startsWith('lume-2.1.10-') || 
              (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE && cacheName !== CACHE_NAME)) {
            console.log('🗑️ Eliminando cache obsoleto:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('🚀 Service Worker activado, caches limpiados');
      // Limpiar todo el contenido del DYNAMIC_CACHE para forzar actualización
      return caches.open(DYNAMIC_CACHE).then(cache => {
        return cache.keys().then(keys => {
          return Promise.all(keys.map(key => cache.delete(key)));
        });
      }).then(() => {
        return self.clients.claim();
      });
    })
  );
});

// 🌐 ESTRATEGIA DE CACHE: Cache First para estáticos, Network First para dinámicos
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // 🖼️ IMÁGENES: Network First para imágenes con cache busting, Cache First para otras
  if (request.destination === 'image') {
    // Si tiene query string v= (cache busting), siempre cargar desde red
    if (url.search.includes('v=')) {
      event.respondWith(
        fetch(request, { 
          cache: 'no-store',
          headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache'
          }
        }).then(response => {
          // No cachear imágenes con cache busting
          return response;
        }).catch(() => {
          // Si falla, intentar desde cache como último recurso
          return caches.match(request);
        })
      );
      return;
    }
    
    // Para imágenes sin cache busting, usar Cache First
    event.respondWith(
      caches.match(request)
        .then(response => {
          if (response) {
            return response;
          }
          return fetch(request).then(response => {
            if (response.status === 200) {
              const responseClone = response.clone();
              caches.open(DYNAMIC_CACHE).then(cache => {
                cache.put(request, responseClone);
              });
            }
            return response;
          });
        })
    );
    return;
  }

  // 📄 PÁGINAS: Network First con fallback a cache (sin cachear para evitar problemas)
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request, { cache: 'no-store' })
        .then(response => {
          return response;
        })
        .catch(() => {
          return caches.match(request);
        })
    );
    return;
  }

  // 🎨 CSS/JS: Network First (NUNCA cachear archivos con ?v=)
  if (request.destination === 'style' || request.destination === 'script') {
    // Si tiene query string v=, NUNCA usar cache - siempre forzar recarga
    if (url.search.includes('v=')) {
      event.respondWith(
        fetch(request, { 
          cache: 'no-store',
          headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache'
          }
        }).catch(() => {
          // Si falla la red, no usar cache, devolver error
          return new Response('Network error', { status: 408 });
        })
      );
      return;
    }
    
    // Sin query string, usar Network First pero sin cachear
    event.respondWith(
      fetch(request, { cache: 'no-store' })
        .then(response => response)
        .catch(() => {
          // Fallback a cache solo si falla la red (para archivos sin versión)
          return caches.match(request);
        })
    );
    return;
  }

  // 🌐 API: Network First (solo cachear GET requests)
  if (url.pathname.startsWith('/api/')) {
    // Solo cachear requests GET (POST, DELETE, PUT no se pueden cachear)
    const isGetRequest = request.method === 'GET';
    
    event.respondWith(
      fetch(request)
        .then(response => {
          // Solo cachear si es GET y la respuesta es exitosa
          if (isGetRequest && response.status === 200) {
            const responseClone = response.clone();
            caches.open(DYNAMIC_CACHE).then(cache => {
              cache.put(request, responseClone).catch(() => {
                // Ignorar errores de cache silenciosamente
              });
            });
          }
          return response;
        })
        .catch(() => {
          // Solo intentar cache si es GET
          if (isGetRequest) {
            return caches.match(request);
          }
          // Para POST/DELETE, devolver error de red
          return new Response('Network error', { status: 408 });
        })
    );
    return;
  }

  // 🔄 FALLBACK: Cache First para todo lo demás
  event.respondWith(
    caches.match(request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(request);
      })
  );
});

// 📊 MÉTRICAS DE PERFORMANCE
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// Nota: El manejo de navigate ya está arriba, no duplicar
