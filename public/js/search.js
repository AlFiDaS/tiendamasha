// Buscador de productos para Lume
// Este archivo maneja toda la funcionalidad de búsqueda

// Variables globales
let searchTimeout;
let isSearchOpen = false;
let productos = [];

// Elementos del DOM
let searchInput;
let searchResults;
let searchResultsList;
let resultsCount;
let closeSearchBtn;

// Función para inicializar el buscador
function initProductSearch() {
  // Obtener elementos del DOM
  searchInput = document.getElementById('productSearchInput');
  searchResults = document.getElementById('searchResults');
  searchResultsList = document.getElementById('searchResultsList');
  resultsCount = document.getElementById('resultsCount');
  closeSearchBtn = document.getElementById('closeSearch');

  // Cargar productos desde el servidor
  loadProductos();

  // Configurar event listeners
  setupEventListeners();
}

// Función para cargar productos
function loadProductos() {
  // Usar directamente los productos hardcodeados para mayor confiabilidad
  productos = getDefaultProductos();
  console.log('Productos cargados:', productos.length);
}

// Productos por defecto (fallback) - Lista completa de productos
function getDefaultProductos() {
  return [
    {
      image: '/images/vela-xoxo/main.webp',
      hoverImage: '/images/vela-xoxo/hover.webp',
      name: 'Vela XOXO',
      price: '$15900',
      stock: true,
      destacado: true,
      slug: 'vela-xoxo',
      descripcion: 'Vela Coleccion XOXO by Lume. Dulce, simpática y lista para sorprender. Incluye caja.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-cuenco-con-mariposa/main.webp', 
      name: 'Cuenco con mariposa. Coleccion XOXO',
      price: '$9000',
      stock: true,
      destacado: false,
      slug: 'vela-cuenco-con-mariposa',
      descripcion: 'Coleccion XOXO by Lume. Vela dulce, simpática y lista para sorprender.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-bubble-con-base-bubble/main.webp',
      name: 'Vela bubble con base bubble. Coleccion XOXO',
      price: '$9900',
      stock: true,
      destacado: false,
      slug: 'vela-bubble-con-base-bubble',
      descripcion: 'Coleccion XOXO by Lume. Vela bubble con base bubble. Dulce, simpática y lista para sorprender.',
      categoria: 'productos',
    },
    {
      image: '/images/ramo-flores-7/main.webp',
      name: 'Ramo con 7 flores. Coleccion XOXO',
      price: '$14900',
      stock: true,
      destacado: true,
      slug: 'ramo-flores-7',
      descripcion: 'Ramo con 7 flores. Coleccion XOXO by Lume. Dulce, simpática y lista para sorprender.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-aromatica/main.webp',
      hoverImage: '/images/vela-aromatica/hover.webp',
      name: 'Vela Aromatica',
      price: '$10900',
      stock: true,
      destacado: false,
      slug: 'vela-aromatica',
      descripcion: 'Vela aromatica en frasco. Ideal para decorar o regalar.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-iced-coffe/hover.webp',
      hoverImage: '/images/vela-iced-coffe/main.webp',
      name: 'Iced Coffee',
      price: '$17900',
      stock: true,
      destacado: false,
      slug: 'velas-iced-coffe',
      descripcion: 'Velas con diseño de iced coffee. Tiene aroma a café.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-lavanda-tea/hover.webp',
      hoverImage: '/images/vela-lavanda-tea/main.webp',
      name: 'Lavanda Tea',
      price: '$17900',
      stock: true,
      destacado: false,
      slug: 'velas-lavanda-tea',
      descripcion: 'Velas con diseño de lavanda tea. Tiene aroma a lavanda.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-strawberry-smothie/hover.webp',
      hoverImage: '/images/vela-strawberry-smothie/main.webp',
      name: 'Strawberry Smothie',
      price: '$17900',
      stock: true,
      destacado: false,
      slug: 'velas-strawberry-smothie',
      descripcion: 'Velas con diseño de strawberry smothie. Tiene aroma a frutilla.',
      categoria: 'productos',
    },
    {
      image: '/images/Set-elegance/main.webp',
      name: 'Set Elegance',
      price: '$29900',
      stock: true,
      destacado: false,
      slug: 'set-elegance',
      descripcion: 'Set de velas decorativas ELEGANCE 🌿 creadas para transformar cualquier espacio con elegancia, calidez y estilo.',
      categoria: 'productos',
    },
    {
      image: '/images/elegance-18cm/main.webp',
      name: 'Elegance 18cm',
      price: '$16900',
      stock: true,
      destacado: false,
      slug: 'elegance-18cm',
      descripcion: 'Velas decorativas de la colección ELEGANCE 🌿 creadas para transformar cualquier espacio con elegancia, calidez y estilo.',
      categoria: 'productos',
    },
    {
      image: '/images/elegance-11cm/main.webp',
      name: 'Elegance 11cm',
      price: '$7900',
      stock: true,
      destacado: false,
      slug: 'elegance-11cm',
      descripcion: 'Velas decorativas de la colección ELEGANCE 🌿 creadas para transformar cualquier espacio con elegancia, calidez y estilo.',
      categoria: 'productos',
    },
    {
      image: '/images/elegance-7cm/main.webp',
      name: 'Elegance 7cm',
      price: '$6900',
      stock: true,
      destacado: false,
      slug: 'elegance-7cm',
      descripcion: 'Velas decorativas de la colección ELEGANCE 🌿 creadas para transformar cualquier espacio con elegancia, calidez y estilo.',
      categoria: 'productos',
    },
    {
      image: '/images/velon-bubble/main.webp',
      name: 'Velón Bubble',
      price: '$7900',
      stock: true,
      destacado: false,
      slug: 'velon-bubble',
      descripcion: 'Velón bubble especial para decorar. Dulce, simpática y lista para sorprender.',
      categoria: 'productos',
    },
    {
      image: '/images/tornasol/main.webp',
      hoverImage: '/images/tornasol/hover.webp',
      name: 'Vela tornasol',
      price: '$16900',
      stock: true,
      destacado: false,
      slug: 'tornasol',
      descripcion: 'Vela aromatica en envase tornasolada 320ml. Ideal para decorar o regalar.',
      categoria: 'productos',
    },
    {
      image: '/images/tornasol-ondulada/main.webp',
      hoverImage: '/images/tornasol-ondulada/hover.webp',
      name: 'Vela tornasol ondulada',
      price: '$16900',
      stock: true,
      destacado: false,
      slug: 'tornasol-ondulada',
      descripcion: 'Vela aromatica en envase tornasolada 320ml. Ideal para decorar o regalar.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-amore/main.webp',
      hoverImage: '/images/vela-amore/hover.webp',
      name: 'Velas Amore',
      price: '$16900',
      stock: true,
      destacado: false,
      slug: 'velas-amore',
      descripcion: 'Velas en frasco con un corazón en su interior. Especial para san valentin o regalos especiales.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-amore-mini/main.webp',
      name: 'Velas Amore Oval',
      price: '$14900',
      stock: true,
      destacado: false,
      slug: 'velas-amore-oval',
      descripcion: 'Velas con frasco ovalado y corazones en su interior. Especial para san valentin o regalos especiales.',
      categoria: 'productos',
    },
    {
      image: '/images/duo-osito/main.webp',
      hoverImage: '/images/duo-osito/hover.webp',
      name: 'Duo Cápsula Love Osito',
      price: '$15500',
      stock: true,
      destacado: false,
      slug: 'duo-capsula-osito',
      descripcion: 'Set de dos velas en cápsula con diseño de osito. Románticas y adorables.',
      categoria: 'productos',
    },
    {
      image: '/images/duo-corazon/main.webp',
      hoverImage: '/images/duo-corazon/hover.webp',
      name: 'Duo Cápsula Love Corazón',
      price: '$15500',
      stock: true,
      destacado: false,
      slug: 'duo-capsula-corazon',
      descripcion: 'Set romántico de velas con forma de corazón. Ideal para regalos especiales.',
      categoria: 'productos',
    },
    {
      image: '/images/set-velas-20cm-amore/main.webp',
      name: 'Set 3 Velas Amore de 20 cm',
      price: '$10500',
      stock: true,
      destacado: false,
      slug: 'set-velas-20cm-amore',
      descripcion: 'Set de 3 velas Amore de 20 cm . Especial para san valentin o regalos especiales.',
      categoria: 'productos',
    },
    {
      image: '/images/set-velas-20cm-verde/main.webp',
      name: 'Set 3 Velas Verde de 20 cm',
      price: '$10500',
      stock: true,
      destacado: false,
      slug: 'set-velas-20cm-verde',
      descripcion: 'Set de 3 velas verdes de 20 cm . Especial para san valentin o regalos especiales.',
      categoria: 'productos',
    },
    {
      image: '/images/velon-torsionado-20cm/main.webp',
      name: 'Velon Torsionado de 20cm',
      price: '$3900',
      stock: true,
      destacado: false,
      slug: 'velon-torsionado-20cm',
      descripcion: 'Velon torsionado de 20cm, el precio publicado es por unidad. Color a elección.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-portacandelabro/main.webp',
      hoverImage: '/images/vela-portacandelabro/hover.webp',
      name: 'Vela 20cm con Portacandelabro',
      price: '$9900',
      stock: true,
      destacado: false,
      slug: 'vela-portacandelabro',
      descripcion: 'Hermoso portacandelabro con una vela de 20cm. Ideal para decorar centro de mesa o para eventos.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-pearl/main.webp',
      name: 'Vela Pearl',
      price: '$9900',
      stock: true,
      destacado: false,
      slug: 'vela-pearl', 
      descripcion: 'Vela artesanal en recipiente de yeso perlado, con tapa y lazo decorativo.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-pearl-mayor/main.webp',
      name: 'Vela Pearl por mayor',
      price: '$0',
      stock: true,
      destacado: false,
      slug: 'vela-pearl-mayor', 
      descripcion: 'Vela artesanal en recipiente de yeso perlado, con tapa y lazo decorativo. Consultar precio al por mayor.',
      categoria: 'productos',
    },
    {
      image: '/images/velas-set-aromaticas/main.webp',
      hoverImage: '/images/velas-set-aromaticas/hover.webp',
      name: 'Set de Velas Aromaticas',
      price: '$14900',
      stock: true,
      destacado: false,
      slug: 'vela-set-aromaticas',
      descripcion: 'Set de velas aromaticas en recipiente de yeso perlado, con tapa. El set incluye base de yeso perlado y 2 velas en recipientes de yeso con aromas a elección.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-cocker/main.webp',
      name: 'Cocker Personalizado',
      price: '$8500',
      stock: true,
      destacado: false,
      slug: 'vela-cocker',
      descripcion: 'Vela artesanal con forma de Cocker. Ideal para regalar a quienes aman a sus mascotas.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-labrador/main.webp',
      name: 'Labrador Personalizado',
      price: '$9500',
      stock: true,
      destacado: false,
      slug: 'vela-labrador',
      descripcion: 'Vela artesanal con forma de labrador. Ideal para regalar a quienes aman a sus mascotas.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-caniche/main.webp',
      hoverImage: '/images/vela-caniche/hover.webp',
      name: 'Caniche Personalizado',
      price: '$9500',
      stock: true,
      destacado: true,
      slug: 'vela-caniche',
      descripcion: 'Vela artesanal con forma de caniche. Ideal para regalar a quienes aman a sus mascotas.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-caniche-gris/main.webp',
      hoverImage: '/images/vela-caniche-gris/hover.webp',
      name: 'Caniche 2 Personalizado',
      price: '$8500',
      stock: true,
      destacado: false,
      slug: 'vela-caniche-2',
      descripcion: 'Vela artesanal con forma de caniche. Ideal para regalar a quienes aman a sus mascotas.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-yorkshire/main.webp',
      hoverImage: '/images/vela-yorkshire/hover.webp',
      name: 'Yorkshire Terrier Personalizado',
      price: '$8500',
      stock: true,
      destacado: false,
      slug: 'vela-yorkshire',
      descripcion: 'Tierna vela personalizada con forma de Yorkshire. Detalle perfecto para los pet lovers.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-golden/main.webp',
      hoverImage: '/images/vela-golden/hover.webp',
      name: 'Golden Personalizado',
      price: '$9500',
      stock: true,
      destacado: false,
      slug: 'vela-golden',
      descripcion: 'Representación de un golden retriever en cera. Detalles realistas y acabado brillante.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-rottwailer/main.webp',
      hoverImage: '/images/vela-rottwailer/hover.webp',
      name: 'Rottweiler Personalizado',
      price: '$9500',
      stock: true,
      destacado: false,
      slug: 'vela-rottweiler',
      descripcion: 'Representación de un Rottweiler en cera. Detalles realistas y acabado brillante.',
      categoria: 'productos',
    },
    {
      image: '/images/velas-gatito/main.webp',
      hoverImage: '/images/velas-gatito/hover.webp',
      name: 'Gatito Personalizado',
      price: '$8500',
      stock: true,
      destacado: true,
      slug: 'vela-gatito',
      descripcion: 'Pequeña vela con forma de gato. Pintadas a mano, perfecta para decorar o regalar.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-salchicha/main.webp',
      hoverImage: '/images/vela-salchicha/hover.webp',
      name: 'Perro Salchicha',
      price: '$8500',
      stock: true,
      destacado: false,
      slug: 'vela-salchicha',
      descripcion: 'Un divertido homenaje en forma de vela a los queridos perros salchicha.', 
      categoria: 'productos',
    },
    {
      image: '/images/vela-terrier/main.webp',
      hoverImage: '/images/vela-terrier/hover.webp',
      name: 'Terrier Personalizado',
      price: '$9500',
      stock: true,
      destacado: false,
      slug: 'vela-terrier',
      descripcion: 'Vela elegante y simpática con forma de terrier. Perfecta para decorar espacios.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-bulldog-frances/main.webp',
      name: 'Bulldog Frances Personalizado',
      price: '$7500',
      stock: true,
      destacado: false,
      slug: 'vela-bulldog-frances',
      descripcion: 'Vela elegante y simpática con forma de bulldog frances. Perfecta para decorar espacios.',
      categoria: 'productos',
    },
    {
      image: '/images/vela-pug/main.webp',
      hoverImage: '/images/vela-pug/hover.webp',
      name: 'Pug Personalizado',
      price: '$7500',
      stock: true,
      destacado: false,
      slug: 'vela-pug',
      descripcion: 'Vela elegante y simpática con forma de pug. Perfecta para decorar espacios.',
      categoria: 'productos',
    },
    {
      image: '/images/bombones-aromaticos/main.webp',
      hoverImage: '/images/bombones-aromaticos/hover.webp',
      name: 'Bombones Aromáticos',
      price: '$4600',
      stock: true,
      destacado: false,
      slug: 'bombones-aromaticos',
      descripcion: 'Bombones aromáticos en bolsita de organza. Consultar aromas disponibles',
      categoria: 'productos',
    }
  ];
}

// Función para normalizar texto (remover acentos y convertir a minúsculas)
function normalizeText(text) {
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

// Función para buscar productos
function searchProducts(query) {
  if (!query || query.trim().length < 2) {
    hideSearchResults();
    return;
  }

  const normalizedQuery = normalizeText(query);
  const results = productos.filter(producto => {
    const normalizedName = normalizeText(producto.name);
    return normalizedName.includes(normalizedQuery);
  });

  displaySearchResults(results, query);
}

// Función para mostrar resultados
function displaySearchResults(results, query) {
  if (!searchResultsList || !resultsCount) return;

  if (results.length === 0) {
         searchResultsList.innerHTML = `
       <div style="padding: 2rem 1.5rem; text-align: center; color: #666;">
         <p style="margin: 0 0 0.5rem 0;">No se encontraron productos con "${query}"</p>
         <p style="font-size: 0.9rem; color: #999; font-style: italic; margin: 0;">Intenta con otros términos</p>
       </div>
     `;
  } else {
         searchResultsList.innerHTML = results.map(producto => `
       <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid #f0f0f0; cursor: pointer; text-decoration: none; color: inherit; min-height: 110px; background: white; box-sizing: border-box;" onclick="window.location.href='/${producto.categoria}/${producto.slug}'">
         <div style="flex-shrink: 0; width: 100px; height: 100px; border-radius: 8px; border: 1px solid #e8e8e8; background: #f8f9fa; overflow: hidden; position: relative; box-sizing: border-box;">
           <img src="${producto.image}" alt="${producto.name}" width="100" height="100" loading="lazy" decoding="async" style="width: 100px !important; height: 100px !important; object-fit: cover !important; display: block !important; max-width: 100px !important; max-height: 100px !important; min-width: 100px !important; min-height: 100px !important; border-radius: 8px !important; transform: none !important; transition: none !important; box-sizing: border-box !important; position: relative !important; z-index: 1 !important;">
         </div>
         <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; box-sizing: border-box;">
           <h4 style="font-size: 1rem; font-weight: 600; color: #2c2c2c; margin: 0 0 0.5rem 0; line-height: 1.3; font-family: 'Playfair Display', serif; box-sizing: border-box;">${producto.name}</h4>
           <p style="font-size: 1.3rem; font-weight: 700; color: #e0a4ce; margin: 0; box-sizing: border-box;">${producto.price}</p>
         </div>
       </div>
     `).join('');
  }

  resultsCount.textContent = results.length;
  showSearchResults();
}

// Función para mostrar el contenedor de resultados
function showSearchResults() {
  if (!searchResults) return;
  
  searchResults.style.display = 'block';
  isSearchOpen = true;
  
  // Agregar clase para animación
  setTimeout(() => {
    searchResults.classList.add('search-results-visible');
  }, 10);
}

// Función para ocultar el contenedor de resultados
function hideSearchResults() {
  if (!searchResults) return;
  
  searchResults.classList.remove('search-results-visible');
  setTimeout(() => {
    searchResults.style.display = 'none';
    isSearchOpen = false;
  }, 200);
}

// Configurar event listeners
function setupEventListeners() {
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const query = e.target.value.trim();
      
      // Limpiar timeout anterior
      if (searchTimeout) {
        clearTimeout(searchTimeout);
      }
      
      // Nuevo timeout para evitar búsquedas excesivas
      searchTimeout = setTimeout(() => {
        searchProducts(query);
      }, 300);
    });

    // Cerrar búsqueda al hacer clic fuera
    searchInput.addEventListener('blur', () => {
      // Pequeño delay para permitir clics en los resultados
      setTimeout(() => {
        if (searchResults && !searchResults.contains(document.activeElement)) {
          hideSearchResults();
        }
      }, 150);
    });

    // Mantener abierto al hacer focus
    searchInput.addEventListener('focus', () => {
      const query = searchInput.value.trim();
      if (query.length >= 2) {
        searchProducts(query);
      }
    });
  }

  // Cerrar búsqueda con el botón
  if (closeSearchBtn) {
    closeSearchBtn.addEventListener('click', hideSearchResults);
  }

  // Cerrar con Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isSearchOpen) {
      hideSearchResults();
      if (searchInput) {
        searchInput.blur();
      }
    }
  });

  // Cerrar al hacer clic fuera del buscador
  document.addEventListener('click', (e) => {
    const searchContainer = document.querySelector('.product-search-container');
    if (searchContainer && !searchContainer.contains(e.target) && isSearchOpen) {
      hideSearchResults();
    }
  });
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initProductSearch);
} else {
  initProductSearch();
}
