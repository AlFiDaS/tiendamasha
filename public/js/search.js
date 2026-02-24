/**
 * Buscador unificado de productos/souvenirs
 * Se auto-configura según los atributos data-* del contenedor
 */
(function() {
  let searchTimeout;
  let isSearchOpen = false;
  let items = [];
  let searchInput, searchResults, searchResultsList, resultsCount, closeSearchBtn;
  let searchFn, initFn;

  function init(inputId, dataLoader) {
    searchInput = document.getElementById(inputId);
    if (!searchInput) return;
    
    const container = searchInput.closest('.product-search-container');
    if (!container) return;
    
    searchResults = container.querySelector('.search-results');
    searchResultsList = container.querySelector('.search-results-list');
    resultsCount = container.querySelector('.results-count-num');
    closeSearchBtn = container.querySelector('.close-search-btn');

    dataLoader(function(data) {
      items = data;
    });

    setupEventListeners();
  }

  function normalizeText(text) {
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  function search(query) {
    if (!query || query.trim().length < 2) {
      hideResults();
      return;
    }
    var normalized = normalizeText(query);
    var results = items.filter(function(item) {
      return normalizeText(item.name).includes(normalized);
    });
    displayResults(results, query);
  }

  function displayResults(results, query) {
    if (!searchResultsList || !resultsCount) return;

    if (results.length === 0) {
      searchResultsList.innerHTML =
        '<div style="padding:2rem 1.5rem;text-align:center;color:#666;">' +
        '<p style="margin:0 0 0.5rem 0;">No se encontraron resultados para "' + query + '"</p>' +
        '<p style="font-size:0.9rem;color:#999;font-style:italic;margin:0;">Intenta con otros t\u00e9rminos</p></div>';
    } else {
      searchResultsList.innerHTML = results.map(function(item) {
        var cat = item.categoria || 'productos';
        return '<div style="display:flex;align-items:center;gap:1rem;padding:1rem 1.5rem;border-bottom:1px solid #f0f0f0;cursor:pointer;min-height:110px;background:white;box-sizing:border-box;" onclick="window.location.href=(window.__STORE_BASE||\'\')+\'/' + cat + '/' + item.slug + '\'">' +
          '<div style="flex-shrink:0;width:100px;height:100px;border-radius:8px;border:1px solid #e8e8e8;background:#f8f9fa;overflow:hidden;">' +
          '<img src="' + item.image + '" alt="' + item.name + '" width="100" height="100" loading="lazy" decoding="async" style="width:100px!important;height:100px!important;object-fit:cover!important;display:block!important;border-radius:8px!important;">' +
          '</div>' +
          '<div style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:center;">' +
          '<h4 style="font-size:1rem;font-weight:600;color:#2c2c2c;margin:0 0 0.5rem 0;line-height:1.3;font-family:\'Playfair Display\',serif;">' + item.name + '</h4>' +
          '<p style="font-size:1.3rem;font-weight:700;color:#e0a4ce;margin:0;">' + item.price + '</p>' +
          '</div></div>';
      }).join('');
    }

    resultsCount.textContent = results.length;
    showResults();
  }

  function showResults() {
    if (!searchResults) return;
    searchResults.style.display = 'block';
    isSearchOpen = true;
    setTimeout(function() {
      searchResults.classList.add('search-results-visible');
    }, 10);
  }

  function hideResults() {
    if (!searchResults) return;
    searchResults.classList.remove('search-results-visible');
    setTimeout(function() {
      searchResults.style.display = 'none';
      isSearchOpen = false;
    }, 200);
  }

  function setupEventListeners() {
    if (searchInput) {
      searchInput.addEventListener('input', function(e) {
        var query = e.target.value.trim();
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() { search(query); }, 300);
      });

      searchInput.addEventListener('blur', function() {
        setTimeout(function() {
          if (searchResults && !searchResults.contains(document.activeElement)) {
            hideResults();
          }
        }, 150);
      });

      searchInput.addEventListener('focus', function() {
        var query = searchInput.value.trim();
        if (query.length >= 2) search(query);
      });
    }

    if (closeSearchBtn) {
      closeSearchBtn.addEventListener('click', hideResults);
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && isSearchOpen) {
        hideResults();
        if (searchInput) searchInput.blur();
      }
    });

    document.addEventListener('click', function(e) {
      var container = searchInput ? searchInput.closest('.product-search-container') : null;
      if (container && !container.contains(e.target) && isSearchOpen) {
        hideResults();
      }
    });
  }

  // Exponer funciones de inicialización para cada tipo
  window.initProductSearch = function() {
    init('productSearchInput', function(cb) {
      cb(typeof getDefaultProductos === 'function' ? getDefaultProductos() : []);
    });
  };

  window.initSouvenirSearch = function() {
    init('souvenirSearchInput', function(cb) {
      cb(typeof getDefaultSouvenirs === 'function' ? getDefaultSouvenirs() : []);
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      if (document.getElementById('productSearchInput')) window.initProductSearch();
      if (document.getElementById('souvenirSearchInput')) window.initSouvenirSearch();
    });
  } else {
    if (document.getElementById('productSearchInput')) window.initProductSearch();
    if (document.getElementById('souvenirSearchInput')) window.initSouvenirSearch();
  }
})();
