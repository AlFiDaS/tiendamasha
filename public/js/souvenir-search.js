// Buscador de souvenirs para Lume
// Este archivo maneja toda la funcionalidad de búsqueda de souvenirs

// Variables globales
let searchTimeout;
let isSearchOpen = false;
let souvenirs = [];

// Elementos del DOM
let searchInput;
let searchResults;
let searchResultsList;
let resultsCount;
let closeSearchBtn;

// Función para inicializar el buscador
function initSouvenirSearch() {
  // Obtener elementos del DOM
  searchInput = document.getElementById('souvenirSearchInput');
  searchResults = document.getElementById('searchResults');
  searchResultsList = document.getElementById('searchResultsList');
  resultsCount = document.getElementById('resultsCount');
  closeSearchBtn = document.getElementById('closeSearch');

  // Cargar souvenirs desde el servidor
  loadSouvenirs();

  // Configurar event listeners
  setupEventListeners();
}

// Función para cargar souvenirs
function loadSouvenirs() {
  // Usar directamente los souvenirs hardcodeados para mayor confiabilidad
  souvenirs = getDefaultSouvenirs();
  console.log('Souvenirs cargados:', souvenirs.length);
}

// Souvenirs por defecto (fallback) - Lista completa de souvenirs
function getDefaultSouvenirs() {
  return [
    {
      image: '/images/vela-osito/main.webp',
      hoverImage: '/images/vela-osito/hover.webp',
      name: 'Osito Souvenir',
      price: '$1900',
      stock: true,
      destacado: true,
      slug: 'vela-osito-souvenir',
      descripcion: 'Pequeños ositos de cera. Perfectos como souvenir para eventos y bautismos. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/osito-capucha/main.webp',
      hoverImage: '/images/osito-capucha/hover.webp',
      name: 'Osito con Capucha',
      price: '$2300',
      stock: true,
      destacado: false,
      slug: 'osito-capucha',
      descripcion: 'Vela de osito con capucha. Ideal para decorar o regalar.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-cactus/main.webp',
      hoverImage: '/images/vela-cactus/hover.webp',
      name: 'Cactus 6cm',
      price: '$2200',
      stock: true,
      destacado: false,
      slug: 'vela-cactus-5cm',
      descripcion: 'Vela con forma de cactus de 5cm. Ideal para decorar o regalar.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-cactus/main.webp',
      hoverImage: '/images/vela-cactus/hover.webp',
      name: 'Cactus 7cm',
      price: '$2300',
      stock: true,
      destacado: false,
      slug: 'vela-cactus-7cm',
      descripcion: 'Vela con forma de cactus de 7cm. Ideal para decorar o regalar.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-jirafa/main.webp',
      hoverImage: '/images/vela-jirafa/hover.webp',
      name: 'Jirafa Souvenir',
      price: '$2400',
      stock: true,
      destacado: false,
      slug: 'vela-jirafa-souvenir',
      descripcion: 'Vela con diseño de jirafa. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-leoncito/main.webp',
      hoverImage: '/images/vela-leoncito/hover.webp',
      name: 'Leoncito Souvenir',
      price: '$2200',
      stock: true,
      destacado: false,
      slug: 'vela-leoncito-souvenir',
      descripcion: 'Leoncito de cera, tierno y decorativo. Gran opción para souvenirs infantiles. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-elefantito/main.webp',
      hoverImage: '/images/souvenir-elefantito/hover.webp',
      name: 'Elefantito Souvenir',
      price: '$2500',
      stock: true,
      destacado: false,
      slug: 'vela-elefantito-souvenir',
      descripcion: 'Vela con diseño de elefantito. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-elefantito-gris/main.webp',
      hoverImage: '/images/souvenir-elefantito-gris/hover.webp',
      name: 'Elefantito Gris Souvenir + base de madera',
      price: '$2800',
      stock: true,
      destacado: false,
      slug: 'vela-elefantito-gris-souvenir',
      descripcion: 'Vela con diseño de elefantito gris con base de madera. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-conejita-corazon/main.webp',
      hoverImage: '/images/souvenir-conejita-corazon/hover.webp',
      name: 'Conejita Souvenir',
      price: '$2500',
      stock: true,
      destacado: false,
      slug: 'vela-conejita-souvenir',
      descripcion: 'Vela con diseño de conejita. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-oso-pooh/main.webp',
      hoverImage: '/images/vela-oso-pooh/hover.webp',
      name: 'Oso Pooh Souvenir',
      price: '$2200',
      stock: true,
      destacado: false,
      slug: 'vela-oso-pooh',
      descripcion: 'Vela con diseño de oso Pooh. Ideal para decorar o regalar. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-vaca/main.webp',
      hoverImage: '/images/souvenir-vaca/hover.webp',
      name: 'Vaca Souvenir',
      price: '$2500',
      stock: true,
      destacado: false,
      slug: 'souvenir-vaca',
      descripcion: 'Vela con diseño de vaca. Ideal para decorar o regalar. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-dinosaurio-souvenir/main.webp',
      name: 'Dinosaurio Souvenir',
      price: '$2500',
      stock: true,
      destacado: false,
      slug: 'vela-dinosaurio-souvenir',
      descripcion: 'Vela con diseño de dinosaurio. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/figuras-marinas-souvenir/main.webp',
      name: 'Figuras Marinas Souvenir',
      price: '$2000',
      stock: true,
      destacado: false,
      slug: 'figuras-marinas-souvenir',
      descripcion: 'Precio por unidad. Velas con diseños de figuras marinas. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-pelota-souvenir/main.webp',
      name: 'Pelota Souvenir',
      price: '$2200',
      stock: true,
      destacado: false,
      slug: 'vela-pelota-souvenir',
      descripcion: 'Vela con diseño de pelota. Simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-abeja/main.webp',
      name: 'Abeja Souvenir',
      price: '$2500',
      stock: true,
      destacado: false,
      slug: 'vela-abeja-souvenir',
      descripcion: 'Vela con diseño de abejita. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-mariposa/main.webp',
      name: 'Mariposa Souvenir',
      price: '$2100',
      stock: true,
      destacado: false,
      slug: 'mariposa-souvenir',
      descripcion: 'Vela con diseño de mariposa. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-mariposa-flores/main.webp',
      name: 'Mariposa Floral Souvenir',
      price: '$1600',
      stock: true,
      destacado: false,
      slug: 'mariposa-flores-souvenir',
      descripcion: 'Vela con diseño de mariposa con flores. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-mariposa-angel/main.webp',
      name: 'Mariposa Angel Souvenir',
      price: '$1800',
      stock: true,
      destacado: false,
      slug: 'mariposa-angel-souvenir',
      descripcion: 'Vela con diseño de mariposa con angelito. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-bebe/main.webp',
      hoverImage: '/images/souvenir-bebe/hover.webp',
      name: 'Bebé Souvenir',
      price: '$2400',
      stock: true,
      destacado: false,
      slug: 'vela-bebe-souvenir',
      descripcion: 'Vela con diseño de bebé. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-angelito-2/main.webp',
      hoverImage: '/images/souvenir-angelito-2/hover.webp',
      name: 'Souvenir Angel nena/nene',
      price: '$2600',
      stock: true,
      destacado: false,
      slug: 'souvenir-angel-nena-nene',
      descripcion: 'Vela con diseño de nena/nene. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-angelito-souvenir/main.webp',
      hoverImage: '/images/vela-angelito-souvenir/hover.webp',
      name: 'Angelito Souvenir',
      price: '$2400',
      stock: true,
      destacado: false,
      slug: 'vela-angelito-souvenir',
      descripcion: 'Vela con diseño de angelito. Suave, elegante y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-bubble/main.webp',
      hoverImage: '/images/vela-bubble/hover.webp',
      name: 'Souvenir Bubble Simple',
      price: '$2100',
      stock: true,
      destacado: false,
      slug: 'vela-bubble-sin-caja',
      descripcion: 'Vela con diseño de bubble simple sin caja. Dulce, simpática y lista para sorprender. Viene con cintita decorativa y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-bubble-en-caja/main.webp',
      hoverImage: '/images/vela-bubble-en-caja/hover.webp',
      name: 'Souvenir Bubble en caja',
      price: '$4700',
      stock: true,
      destacado: false,
      slug: 'vela-bubble-en-caja',
      descripcion: 'Vela con diseño de bubble en caja de acetato transparente. Dulce, simpática y lista para sorprender. Viene con cintita decorativa y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-mini-bubble/main.webp',
      hoverImage: '/images/vela-mini-bubble/hover.webp',
      name: 'Mini Bubble Souvenir Pack',
      price: '$2400',
      stock: true,
      destacado: false,
      slug: 'vela-mini-bubble-souvenir-pack',
      descripcion: 'Vela con diseño de mini bubble. Dulce, simpática y lista para sorprender. Viene con envoltorio personalizado para tu evento. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-nube/main.webp',
      hoverImage: '/images/vela-nube/hover.webp',
      name: 'Nube Souvenir',
      price: '$2000',
      stock: true,
      destacado: false,
      slug: 'vela-nube',
      descripcion: 'Vela con diseño de nubecita. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-flores-en-caja/main.webp',
      hoverImage: '/images/vela-flores-en-caja/hover.webp',
      name: 'Souvenir Flores en caja',
      price: '$3600',
      stock: true,
      destacado: false,
      slug: 'vela-flores-en-caja',
      descripcion: 'Vela con diseño de flores en caja. Dulce, simpática y lista para sorprender. Viene con cintita decorativa y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/velas-flor-loto/main.webp',
      name: 'Flor Loto',
      price: '$2400',
      stock: true,
      destacado: false,
      slug: 'velas-flor-lotojpg',
      descripcion: 'Velas con diseño de flor. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/vela-flor-peonia/main.webp',
      hoverImage: '/images/vela-flor-peonia/hover.webp',
      name: 'Flor Peónia',
      price: '$1900',
      stock: true,
      destacado: false,
      slug: 'vela-flor-peonia',
      descripcion: 'Vela con diseño de flor. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/Rosas-souvenir/main.webp',
      hoverImage: '/images/Rosas-souvenir/hover.webp',
      name: 'Rosas Souvenir',
      price: '$1900',
      stock: true,
      destacado: false,
      slug: 'vela-rosa-souvenir',
      descripcion: 'Vela con diseño de rosa. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/clavelina-souvenir/main.webp',
      name: 'Clavelina Souvenir',
      price: '$2000',
      stock: true,
      destacado: false,
      slug: 'vela-clavelina-souvenir',
      descripcion: 'Vela con diseño de clavelina. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/margaritas-souvenir/main.webp',
      hoverImage: '/images/margaritas-souvenir/hover.webp',
      name: 'Margaritas Souvenir',
      price: '$1700',
      stock: true,
      destacado: false,
      slug: 'vela-margaritas-souvenir',
      descripcion: 'Vela con diseño de margaritas. Dulce, simpática y lista para sorprender. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/corazon-souvenir/main.webp',
      hoverImage: '/images/corazon-souvenir/hover.webp',
      name: 'Corazon Souvenir',
      price: '$2400',
      stock: true,
      destacado: false,
      slug: 'vela-corazon-souvenir',
      descripcion: 'Vela con diseño de Corazon. Ideal para decorar o regalar. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/oso-corazon-souvenir/main.webp',
      name: 'Oso Corazon Souvenir',
      price: '$2400',
      stock: true,
      destacado: false,
      slug: 'vela-oso-corazon-souvenir',
      descripcion: 'Vela con diseño de Oso con corazon. Ideal para decorar o regalar. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades..',
      categoria: 'souvenirs',
    },
    {
      image: '/images/oso-panzon/main.webp',
      hoverImage: '/images/oso-panzon/hover.webp',
      name: 'Oso Panzón Souvenir',
      price: '$2400',
      stock: true,
      destacado: false,
      slug: 'vela-oso-panzon',
      descripcion: 'Vela con diseño de Oso panzón. Ideal para decorar o regalar. Viene con bolsita transparente de polipropileno y tarjetita personalizada. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-envase100cc/main.webp',
      hoverImage: '/images/souvenir-envase100cc/hover.webp',
      name: 'Souvenir en envase 100cc',
      price: '$4600',
      stock: true,
      destacado: false,
      slug: 'vela-souvenir-envase100cc',
      descripcion: 'Vela aromatica en envase 100cc. Ideal para decorar o regalar. Viene con vinilo personalizado y cinta del color que elijas. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-envase-40cc/main.webp',
      hoverImage: '/images/souvenir-envase-40cc/hover.webp',
      name: 'Souvenir en envase 40cc',
      price: '$3600',
      stock: true,
      destacado: false,
      slug: 'vela-souvenir-envase40cc',
      descripcion: 'Vela aromatica en envase 40cc. Ideal para decorar o regalar. Viene con vinilo personalizado y cinta del color que elijas. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-caramelera/main.webp',
      name: 'Souvenir caramelera sin tapa',
      price: '$4000',
      stock: true,
      destacado: false,
      slug: 'vela-souvenir-caramelera-sin-tapa',
      descripcion: 'Vela aromatica en caramelera sin tapa. Ideal para decorar o regalar. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
    },
    {
      image: '/images/souvenir-caramelera-con-tapa/main.webp',
      name: 'Souvenir caramelera con tapa',
      price: '$5000',
      stock: true,
      destacado: false,
      slug: 'vela-souvenir-caramelera-con-tapa',
      descripcion: 'Vela aromatica en caramelera con tapa. Ideal para decorar o regalar. Cantidad minima: 10 unidades.',
      categoria: 'souvenirs',
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

// Función para buscar souvenirs
function searchSouvenirs(query) {
  if (!query || query.trim().length < 2) {
    hideSearchResults();
    return;
  }

  const normalizedQuery = normalizeText(query);
  const results = souvenirs.filter(souvenir => {
    const normalizedName = normalizeText(souvenir.name);
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
        <p style="margin: 0 0 0.5rem 0;">No se encontraron souvenirs con "${query}"</p>
        <p style="font-size: 0.9rem; color: #999; font-style: italic; margin: 0;">Intenta con otros términos</p>
      </div>
    `;
  } else {
    searchResultsList.innerHTML = results.map(souvenir => `
      <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid #f0f0f0; cursor: pointer; text-decoration: none; color: inherit; min-height: 110px; background: white; box-sizing: border-box;" onclick="window.location.href='/${souvenir.categoria}/${souvenir.slug}'">
        <div style="flex-shrink: 0; width: 100px; height: 100px; border-radius: 8px; border: 1px solid #e8e8e8; background: #f8f9fa; overflow: hidden; position: relative; box-sizing: border-box;">
          <img src="${souvenir.image}" alt="${souvenir.name}" width="100" height="100" loading="lazy" decoding="async" style="width: 100px !important; height: 100px !important; object-fit: cover !important; display: block !important; max-width: 100px !important; max-height: 100px !important; min-width: 100px !important; min-height: 100px !important; border-radius: 8px !important; transform: none !important; transition: none !important; box-sizing: border-box !important; position: relative !important; z-index: 1 !important;">
        </div>
        <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; box-sizing: border-box;">
          <h4 style="font-size: 1rem; font-weight: 600; color: #2c2c2c; margin: 0 0 0.5rem 0; line-height: 1.3; font-family: 'Playfair Display', serif; box-sizing: border-box;">${souvenir.name}</h4>
          <p style="font-size: 1.3rem; font-weight: 700; color: #e0a4ce; margin: 0; box-sizing: border-box;">${souvenir.price}</p>
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
        searchSouvenirs(query);
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
        searchSouvenirs(query);
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
  document.addEventListener('DOMContentLoaded', initSouvenirSearch);
} else {
  initSouvenirSearch();
}
