/**
 * ============================================
 * Cargador Dinámico de Productos
 * ============================================
 * Carga productos desde la API y los renderiza
 * Compatible: Navegadores modernos
 * ============================================
 */

(function() {
    'use strict';
    
    // Detectar si estamos en desarrollo local y ajustar la URL de la API
    const isLocalDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const currentPort = window.location.port;
    
    // Si estamos en el servidor de Astro (puerto 4321), usar el servidor PHP directamente (puerto 8080)
    // Si estamos en producción o el servidor PHP, usar ruta relativa
    let API_BASE = '/api/products.php';
    if (isLocalDev && (currentPort === '4321' || currentPort === '')) {
        // Desde Astro, usar el servidor PHP directamente
        API_BASE = 'http://localhost:8080/api/products.php';
    }
    
    /**
     * Cargar productos desde la API
     * @param {Object} filters - Filtros de búsqueda
     * @returns {Promise<Array>}
     */
    async function loadProducts(filters = {}) {
        try {
            // Construir URL con parámetros
            const params = new URLSearchParams();
            
            if (filters.categoria) params.append('categoria', filters.categoria);
            if (filters.destacado !== undefined) params.append('destacado', filters.destacado ? 1 : 0);
            if (filters.stock !== undefined) params.append('stock', filters.stock ? 1 : 0);
            if (filters.limit) params.append('limit', filters.limit);
            
            const url = API_BASE + (params.toString() ? '?' + params.toString() : '');
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                console.error('Respuesta recibida:', text.substring(0, 500));
                throw new Error('La respuesta del servidor no es válida JSON. Verifica la consola para más detalles.');
            }
            
            if (!data.success) {
                throw new Error(data.error || 'Error al cargar productos');
            }
            
            return data.products || [];
            
        } catch (error) {
            console.error('Error al cargar productos:', error);
            throw error;
        }
    }
    
    /**
     * Extraer valor numérico del precio
     * @param {string} priceString - Precio como string (ej: "$15900")
     * @returns {number} Valor numérico
     */
    function extractPriceValue(priceString) {
        if (!priceString) return 0;
        // Remover símbolos y espacios, mantener solo números
        const cleaned = priceString.replace(/[^0-9]/g, '');
        return parseInt(cleaned, 10) || 0;
    }
    
    /**
     * Calcular precio con tarjeta (25% más, redondeado al 100 más cercano)
     * @param {string} priceString - Precio como string (ej: "$15900")
     * @returns {string} Precio con tarjeta formateado (ej: "$19900")
     */
    function calculateCardPrice(priceString) {
        const basePrice = extractPriceValue(priceString);
        if (basePrice === 0) return '';
        const cardPrice = Math.round((basePrice * 1.25) / 100) * 100;
        return '$' + cardPrice.toLocaleString('es-AR');
    }
    
    /**
     * Calcular precio de transferencia (25% menos = 80% del precio de tarjeta)
     * @param {string} priceString - Precio de tarjeta como string (ej: "$19875")
     * @returns {string} Precio de transferencia formateado (ej: "$15900")
     */
    function calculateTransferPrice(priceString) {
        const cardPrice = extractPriceValue(priceString);
        if (cardPrice === 0) return '';
        const transferPrice = Math.round(cardPrice * 0.8);
        return '$' + transferPrice.toLocaleString('es-AR');
    }
    
    /**
     * Renderizar producto como card
     * @param {Object} product
     * @returns {string} HTML del producto
     */
    function renderProductCard(product) {
        // Stock disponible si es NULL (ilimitado) o > 0 (limitado)
        // Stock no disponible solo si es 0
        const hasStock = product.stock === null || product.stock > 0;
        const stockClass = !hasStock ? 'sin-stock' : '';
        const stockText = !hasStock ? 'Sin stock' : 'Agregar al carrito';
        const disabledAttr = !hasStock ? 'disabled' : '';
        
        // Validar y sanitizar URLs de imágenes
        const placeholderPath = '/images/placeholder.svg';
        const hasValidImage = product.image && product.image.trim() !== '';
        const imageSrc = hasValidImage ? product.image : placeholderPath;
        
        // Hover image: usar hoverImage si existe y es válido, sino usar la imagen principal
        const hasValidHover = product.hoverImage && product.hoverImage.trim() !== '';
        const hoverImage = hasValidHover ? product.hoverImage : imageSrc;
        // Solo agregar hover attributes si hay una imagen hover válida Y diferente a la principal
        const hoverAttr = (hasValidHover && hasValidImage && hoverImage !== imageSrc) ? 
            `onmouseover="this.src='${escapeHtml(hoverImage)}'" onmouseout="this.src='${escapeHtml(imageSrc)}'"` : 
            '';
        
        // Verificar si hay descuento
        const hasDiscount = product.en_descuento === 1 || product.en_descuento === '1';
        const discountPrice = product.precio_descuento;
        
        let priceHtml = '';
        let discountBadgeHtml = '';
        
        if (hasDiscount && discountPrice) {
            // Producto en descuento: mostrar badge en imagen y precio de descuento
            const originalTransferPrice = extractPriceValue(product.price);
            const discountTransferPrice = extractPriceValue(discountPrice);
            const originalTransferFormatted = originalTransferPrice > 0 ? '$' + originalTransferPrice.toLocaleString('es-AR') : '';
            const discountTransferFormatted = discountTransferPrice > 0 ? '$' + discountTransferPrice.toLocaleString('es-AR') : '';
            
            // Calcular porcentaje de descuento
            let discountPercentage = 0;
            if (originalTransferPrice > 0 && discountTransferPrice > 0) {
                discountPercentage = Math.round(((originalTransferPrice - discountTransferPrice) / originalTransferPrice) * 100);
            }
            
            // Calcular precio de tarjeta del precio en descuento
            const discountCardPrice = discountTransferPrice > 0 ? Math.round((discountTransferPrice * 1.25) / 100) * 100 : 0;
            const discountCardFormatted = discountCardPrice > 0 ? '$' + discountCardPrice.toLocaleString('es-AR') : '';
            
            // Badges para la imagen
            // Solo mostrar el badge de porcentaje si hay stock
            discountBadgeHtml = `
                <div class="discount-badge">
                    <span class="discount-badge-label">ANTES:</span>
                    <span class="discount-badge-value">${escapeHtml(originalTransferFormatted)}</span>
                </div>
                ${hasStock ? `<div class="discount-percentage-badge">
                    - ${discountPercentage}%
                </div>` : ''}
            `;
            
            priceHtml = `
                <div class="price-container">
                    ${discountTransferFormatted ? `<p class="price">${escapeHtml(discountTransferFormatted)}</p>` : ''}
                    ${discountCardFormatted ? `
                        <p class="price-card">
                            <span class="price-label">Mercado Pago / Tarjeta:</span> ${escapeHtml(discountCardFormatted)}
                        </p>
                    ` : ''}
                    <p class="price-card-text">hasta en 3 cuotas</p>
                </div>
            `;
        } else {
            // Producto normal: mostrar precio de transferencia como principal y precio de tarjeta como secundario
            const cardPrice = calculateCardPrice(product.price);
            // Formatear precio de transferencia (extraer número y formatear con separadores de miles)
            let transferPriceFormatted = '';
            if (product.price) {
                const transferPriceValue = extractPriceValue(product.price);
                if (transferPriceValue > 0) {
                    transferPriceFormatted = '$' + transferPriceValue.toLocaleString('es-AR');
                }
            }
            priceHtml = product.price ? `
                <div class="price-container">
                    ${transferPriceFormatted ? `<p class="price">${escapeHtml(transferPriceFormatted)}</p>` : ''}
                    ${cardPrice ? `
                        <p class="price-card">
                            <span class="price-label">Mercado Pago / Tarjeta:</span> ${escapeHtml(cardPrice)}
                        </p>
                    ` : ''}
                    <p class="price-card-text">hasta en 3 cuotas</p>
                </div>
            ` : '<p class="price">N/A</p>';
        }
        
        const productId = product.id || product.slug;
        
        return `
            <div class="product-card">
                <div class="image-container">
                    <a href="/${escapeHtml(product.categoria)}/${escapeHtml(product.slug)}" class="card-link">
                        <img
                            src="${escapeHtml(imageSrc)}"
                            alt="${escapeHtml(product.name)} - Lume Velas Artesanales"
                            class="imagen-con-transicion"
                            width="400"
                            height="400"
                            ${hoverAttr}
                            loading="eager"
                            decoding="sync"
                            fetchpriority="high"
                            style="opacity: 1; visibility: visible; display: block;"
                            onload="this.style.opacity='1'; this.style.visibility='visible'; this.classList.add('loaded');"
                            onerror="if(this.src!=='${placeholderPath}'){this.onerror=null;this.src='${placeholderPath}';}else{this.style.display='none';}"
                        />
                    </a>
                    ${discountBadgeHtml}
                    <button 
                        class="wishlist-btn" 
                        data-wishlist-id="${escapeHtml(productId)}"
                        onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist('${escapeHtml(productId)}'); return false;"
                        title="Agregar a favoritos"
                        aria-label="Agregar a favoritos"
                    >
                        🤍
                    </button>
                    ${!hasStock ? '<div class="sin-stock">Sin stock</div>' : ''}
                </div>
                
                <div class="info">
                    <h3>${escapeHtml(product.name)}</h3>
                    ${priceHtml}
                    <button 
                        class="btn-agregar" 
                        onclick="agregarAlCarrito('${escapeHtml(product.name)}', '${escapeHtml(hasDiscount && discountPrice ? discountPrice : product.price)}', '${product.image}', '${product.slug}', '${product.categoria}')"
                        ${disabledAttr}
                    >
                        ${stockText}
                    </button>
                </div>
            </div>
        `;
    }
    
    /**
     * Renderizar grid de productos
     * @param {Array} products
     * @param {HTMLElement} container
     */
    function renderProductsGrid(products, container) {
        if (!container) {
            console.error('Contenedor no encontrado');
            return;
        }
        
        if (products.length === 0) {
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #666;">
                    <p>No se encontraron productos en esta categoría.</p>
                </div>
            `;
            return;
        }
        
        // Verificar si es el contenedor de destacados y si hay número impar
        const isDestacados = container.hasAttribute('data-destacados') && container.getAttribute('data-destacados') === 'true';
        const isOdd = products.length % 2 !== 0;
        
        // Renderizar productos
        let html = '';
        products.forEach((product, index) => {
            const isLast = index === products.length - 1;
            // Aplicar clase especial si es el último producto impar en destacados
            // El CSS se encargará de aplicarlo solo en mobile
            const shouldCenter = isDestacados && isOdd && isLast;
            
            let cardHtml = renderProductCard(product);
            
            // Si es el último y es impar en destacados, agregar clase especial
            if (shouldCenter) {
                // Reemplazar la clase product-card para agregar la clase especial
                cardHtml = cardHtml.replace('class="product-card"', 'class="product-card product-card-last-odd"');
            }
            
            html += cardHtml;
        });
        
        container.innerHTML = html;
        
        // Forzar carga inmediata de imágenes después de renderizar
        requestAnimationFrame(() => {
            const images = container.querySelectorAll('img.imagen-con-transicion');
            images.forEach((img, index) => {
                // Para las primeras 8 imágenes visibles, usar fetchpriority="high"
                if (index < 8) {
                    img.setAttribute('fetchpriority', 'high');
                }
                
                // Forzar carga inmediata creando un nuevo Image object
                const imgLoader = new Image();
                imgLoader.onload = function() {
                    // Una vez cargada, asegurar que se muestre
                    if (img.src !== imgLoader.src) {
                        img.src = imgLoader.src;
                    }
                    img.style.opacity = '1';
                    img.style.visibility = 'visible';
                };
                imgLoader.onerror = function() {
                    // Si falla, usar placeholder
                    img.src = '/images/placeholder.svg';
                };
                imgLoader.src = img.src;
                
                // También forzar carga del elemento img directamente
                if (!img.complete) {
                    img.loading = 'eager';
                }
            });
        });
        
        // Actualizar estados de wishlist después de renderizar
        if (window.loadWishlistStates && typeof window.loadWishlistStates === 'function') {
            setTimeout(() => {
                window.loadWishlistStates();
            }, 200);
        }
    }
    
    /**
     * Mostrar estado de carga
     * @param {HTMLElement} container
     */
    function showLoading(container) {
        if (!container) return;
        
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #e0a4ce; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 1rem; color: #666;">Cargando productos...</p>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;
    }
    
    /**
     * Mostrar error
     * @param {HTMLElement} container
     * @param {string} message
     */
    function showError(container, message) {
        if (!container) return;
        
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #dc3545;">
                <p><strong>Error al cargar productos</strong></p>
                <p>${escapeHtml(message)}</p>
                <button onclick="location.reload()" style="margin-top: 1rem; padding: 0.75rem 1.5rem; background: #e0a4ce; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Reintentar
                </button>
            </div>
        `;
    }
    
    /**
     * Escape HTML para prevenir XSS
     * @param {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Inicializar cargador de productos
     * @param {Object} options
     */
    window.initProductsLoader = function(options = {}) {
        const {
            containerSelector = '.products-grid',
            categoria = null,
            destacado = null,
            stock = null,
            limit = null,
            autoLoad = true
        } = options;
        
        const container = document.querySelector(containerSelector);
        
        if (!container) {
            console.warn(`Contenedor "${containerSelector}" no encontrado`);
            return;
        }
        
        if (autoLoad) {
            loadAndRender();
        }
        
        async function loadAndRender() {
            try {
                showLoading(container);
                
                const filters = {};
                if (categoria) filters.categoria = categoria;
                if (destacado !== null) filters.destacado = destacado;
                if (stock !== null) filters.stock = stock;
                if (limit) filters.limit = limit;
                
                const products = await loadProducts(filters);
                renderProductsGrid(products, container);
                
            } catch (error) {
                showError(container, error.message || 'Error desconocido');
            }
        }
        
        // Retornar función para recargar manualmente
        return {
            reload: loadAndRender,
            loadProducts: loadProducts,
            renderProductsGrid: renderProductsGrid
        };
    };
    
    // Auto-inicializar si hay un contenedor con clase 'products-grid'
    document.addEventListener('DOMContentLoaded', function() {
        // Primero verificar si hay contenedores de destacados (en la página de inicio)
        const destacadosContainer = document.querySelector('.products-grid[data-destacados="true"]');
        if (destacadosContainer) {
            const limit = parseInt(destacadosContainer.dataset.limit || '5');
            window.initProductsLoader({
                containerSelector: '.products-grid[data-destacados="true"]',
                destacado: true,
                limit: limit,
                autoLoad: true
            });
            return; // No procesar otros contenedores si encontramos uno de destacados
        }
        
        // Luego verificar contenedores normales (sin destacados)
        const autoContainer = document.querySelector('.products-grid');
        if (autoContainer) {
            // Intentar detectar categoría desde la URL dinámicamente
            let categoria = null;
            const path = window.location.pathname;
            
            // Excluir rutas especiales que no son categorías
            const excludedPaths = ['/api', '/admin', '/ideas', '/carrito', '/wishlist', '/mis-pedidos', '/'];
            
            // Si la ruta no está excluida y tiene formato /categoria, extraer la categoría
            if (!excludedPaths.some(excluded => path === excluded || path.startsWith(excluded + '/'))) {
                const match = path.match(/^\/([^\/]+)\/?$/);
                if (match && match[1]) {
                    categoria = match[1];
                }
            }
            
            window.initProductsLoader({
                containerSelector: '.products-grid',
                categoria: categoria,
                autoLoad: true
            });
        }
    });
    
})();

