/**
 * ============================================
 * Cargador de Detalle de Producto
 * ============================================
 * Carga un producto específico por slug
 * Compatible: Navegadores modernos
 * ============================================
 */

(function() {
    'use strict';
    
    // Detectar si estamos en desarrollo local y ajustar la URL de la API
    const isLocalDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const currentPort = window.location.port;
    
    // Si estamos en el servidor de Astro (puerto 4321), usar el servidor PHP directamente (puerto 8080)
    let API_BASE = '/api/products.php';
    if (isLocalDev && (currentPort === '4321' || currentPort === '')) {
        // Desde Astro, usar el servidor PHP directamente
        API_BASE = 'http://localhost:8080/api/products.php';
    }
    
    /**
     * Cargar producto por slug
     * @param {string} slug
     * @returns {Promise<Object>}
     */
    async function loadProductBySlug(slug) {
        try {
            const url = `${API_BASE}?slug=${encodeURIComponent(slug)}`;
            
            const response = await fetch(url);
            
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error('Producto no encontrado');
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error al cargar el producto');
            }
            
            return data.product || null;
            
        } catch (error) {
            console.error('Error al cargar producto:', error);
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
     * Renderizar detalle de producto
     * @param {Object} product
     * @param {HTMLElement} container
     */
    function renderProductDetail(product, container) {
        if (!container) {
            console.error('Contenedor no encontrado');
            return;
        }
        
        // Validar y sanitizar URLs de imágenes
        const placeholderPath = '/images/placeholder.svg';
        const hasValidImage = product.image && product.image.trim() !== '';
        const imageSrc = hasValidImage ? product.image : placeholderPath;
        const hasValidHover = product.hoverImage && product.hoverImage.trim() !== '';
        const hoverImage = hasValidHover ? product.hoverImage : imageSrc;
        
        // Guardar el precio de transferencia original en el carrito (no el precio de tarjeta)
        // Si hay descuento, usar el precio de descuento
        const hasDiscount = product.en_descuento === 1 || product.en_descuento === '1';
        const discountPrice = product.precio_descuento;
        const priceToUse = (hasDiscount && discountPrice) ? discountPrice : product.price;
        
        // Badge de descuento para la imagen
        let discountBadgeHtml = '';
        if (hasDiscount && discountPrice) {
            const originalTransferPrice = extractPriceValue(product.price);
            const discountTransferPrice = extractPriceValue(discountPrice);
            const originalTransferFormatted = originalTransferPrice > 0 ? '$' + originalTransferPrice.toLocaleString('es-AR') : '';
            
            // Calcular porcentaje de descuento
            let discountPercentage = 0;
            if (originalTransferPrice > 0 && discountTransferPrice > 0) {
                discountPercentage = Math.round(((originalTransferPrice - discountTransferPrice) / originalTransferPrice) * 100);
            }
            
            discountBadgeHtml = `
                <div class="discount-badge-detail">
                    <span class="discount-badge-label">ANTES:</span>
                    <span class="discount-badge-value">${escapeHtml(originalTransferFormatted)}</span>
                </div>
                <div class="discount-percentage-badge-detail">
                    - ${discountPercentage}%
                </div>
            `;
        }
        
        // Stock disponible si es NULL (ilimitado) o > 0 (limitado)
        const hasStock = product.stock === null || product.stock > 0;
        const stockButton = hasStock ? 
            `<button 
                class="btn-agregar" 
                onclick="agregarAlCarrito('${escapeHtml(product.name)}', '${escapeHtml(priceToUse)}', '${escapeHtml(imageSrc)}', '${escapeHtml(product.slug)}', '${escapeHtml(product.categoria)}')"
            >
                Agregar al carrito
            </button>` :
            `<button class="btn-agregar" disabled>Sin stock</button>`;
        
        // Determinar nombre de categoría para mostrar (dinámico)
        // Si no está en el mapeo, usar el nombre de la categoría directamente
        const categoriaNames = {
            'productos': 'Producto',
            'souvenirs': 'Souvenir',
            'navidad': 'Navidad',
            'promos': 'Promo'
        };
        // Capitalizar primera letra si no está en el mapeo
        const categoriaName = categoriaNames[product.categoria] || 
            (product.categoria ? product.categoria.charAt(0).toUpperCase() + product.categoria.slice(1) : 'Producto');
        
        container.innerHTML = `
            <div class="product-images">
                <div class="main-image">
                    <img 
                        id="mainProductImage"
                        src="${escapeHtml(imageSrc)}" 
                        alt="${escapeHtml(product.name)}"
                        class="imagen-principal"
                        width="800"
                        height="800"
                        loading="eager"
                        decoding="async"
                        fetchpriority="high"
                        onerror="if(this.src!=='/images/placeholder.svg'){this.onerror=null;this.src='/images/placeholder.svg';}else{this.style.display='none';}"
                    />
                    ${discountBadgeHtml}
                    <button 
                        class="wishlist-btn-detail-image" 
                        data-wishlist-id="${escapeHtml(product.id)}"
                        onclick="toggleWishlist('${escapeHtml(product.id)}')"
                        title="Agregar a favoritos"
                    >
                        🤍
                    </button>
                </div>
                ${hasValidHover ? `
                    <div class="thumbnails">
                        <div class="thumbnail active" data-image="${escapeHtml(imageSrc)}">
                            <img 
                                src="${escapeHtml(imageSrc)}" 
                                alt="Vista principal"
                                width="200"
                                height="200"
                                loading="lazy"
                                decoding="async"
                                onerror="if(this.src!=='/images/placeholder.svg'){this.onerror=null;this.src='/images/placeholder.svg';}else{this.style.display='none';}"
                            />
                        </div>
                        <div class="thumbnail" data-image="${escapeHtml(hoverImage)}">
                            <img 
                                src="${escapeHtml(hoverImage)}" 
                                alt="Vista hover"
                                width="200"
                                height="200"
                                loading="lazy"
                                decoding="async"
                                onerror="if(this.src!=='/images/placeholder.svg'){this.onerror=null;this.src='/images/placeholder.svg';}else{this.style.display='none';}"
                            />
                        </div>
                    </div>
                ` : ''}
            </div>
            
            <div class="producto-info">
                <div class="producto-header">
                    <div class="header-main">
                        <h1>${escapeHtml(product.name)}</h1>
                    </div>
                    <div class="header-badges">
                        <span class="categoria">${categoriaName}</span>
                        ${!hasStock ? '<span class="badge-sin-stock">Sin stock</span>' : ''}
                    </div>
                </div>
                
                ${product.descripcion ? `
                    <div class="producto-description">
                        <p>${escapeHtml(product.descripcion)}</p>
                    </div>
                ` : ''}
                
                <div class="producto-price">
                    ${(() => {
                        // Verificar si hay descuento
                        const hasDiscount = product.en_descuento === 1 || product.en_descuento === '1';
                        const discountPrice = product.precio_descuento;
                        
                        if (hasDiscount && discountPrice) {
                            // Producto en descuento: mostrar precio de descuento (el badge "ANTES" está en la imagen)
                            const discountTransferPrice = extractPriceValue(discountPrice);
                            const discountTransferFormatted = discountTransferPrice > 0 ? '$' + discountTransferPrice.toLocaleString('es-AR') : '';
                            
                            // Calcular precio de tarjeta del precio en descuento
                            const discountCardPrice = discountTransferPrice > 0 ? Math.round((discountTransferPrice * 1.25) / 100) * 100 : 0;
                            const discountCardFormatted = discountCardPrice > 0 ? '$' + discountCardPrice.toLocaleString('es-AR') : '';
                            
                            return `
                                <div class="price-main-row">
                                    <span class="price">${escapeHtml(discountTransferFormatted)}</span>
                                    <span class="price-transfer-label">Transferencia</span>
                                </div>
                                <div class="price-card-row-detail">
                                    <span class="price-card-label-detail">Mercado Pago / Tarjeta:</span>
                                    <span class="price-card-value-detail">${escapeHtml(discountCardFormatted)}</span>
                                    <span class="price-card-text-detail">hasta en 3 cuotas</span>
                                </div>
                            `;
                        } else {
                            // Producto normal
                            if (!product.price) return '<span class="price">N/A</span>';
                            // Formatear precio de transferencia (extraer número y formatear con separadores de miles)
                            const transferPriceValue = extractPriceValue(product.price);
                            const transferPriceFormatted = transferPriceValue > 0 ? '$' + transferPriceValue.toLocaleString('es-AR') : product.price;
                            
                            // Calcular precio de tarjeta (25% más)
                            const cardPrice = Math.round((transferPriceValue * 1.25) / 100) * 100;
                            const cardPriceFormatted = '$' + cardPrice.toLocaleString('es-AR');
                            
                            return `
                                <div class="price-main-row">
                                    <span class="price">${escapeHtml(transferPriceFormatted)}</span>
                                    <span class="price-transfer-label">Transferencia</span>
                                </div>
                                <div class="price-card-row-detail">
                                    <span class="price-card-label-detail">Mercado Pago / Tarjeta:</span>
                                    <span class="price-card-value-detail">${escapeHtml(cardPriceFormatted)}</span>
                                    <span class="price-card-text-detail">hasta en 3 cuotas</span>
                                </div>
                            `;
                        }
                    })()}
                </div>
                
                ${product.categoria === 'souvenirs' ? `
                    <div class="producto-details">
                        <div class="detail-item">
                            <span class="detail-icon">📦</span>
                            <span class="detail-text">Cantidad mínima: 10 unidades</span>
                        </div>
                    </div>
                ` : ''}
                
                <div class="producto-actions">
                    ${stockButton}
                    <a href="/${product.categoria}" class="btn-volver">
                        ← Volver al catálogo
                    </a>
                </div>
            </div>
        `;
        
        // Inicializar thumbnails después de renderizar
        if (hasValidHover) {
            setTimeout(() => {
                const thumbnails = container.querySelectorAll('.thumbnail');
                thumbnails.forEach(thumb => {
                    thumb.addEventListener('click', () => {
                        const thumbImageSrc = thumb.getAttribute('data-image');
                        if (thumbImageSrc) {
                            changeMainImage(thumbImageSrc);
                        }
                    });
                });
            }, 100);
        }
    }
    
    /**
     * Mostrar estado de carga
     * @param {HTMLElement} container
     */
    function showLoading(container) {
        if (!container) return;
        
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #e0a4ce; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 1rem; color: #666;">Cargando producto...</p>
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
            <div style="text-align: center; padding: 3rem; color: #dc3545;">
                <p><strong>Error al cargar el producto</strong></p>
                <p>${escapeHtml(message)}</p>
                <a href="/${getCategoriaFromPath()}" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #e0a4ce; color: white; text-decoration: none; border-radius: 4px;">
                    Volver al catálogo
                </a>
            </div>
        `;
    }
    
    /**
     * Cambiar imagen principal
     * @param {string} imageSrc
     */
    window.changeMainImage = function(imageSrc) {
        const mainImage = document.getElementById('mainProductImage');
        if (mainImage) {
            mainImage.src = imageSrc;
        }
        
        // Actualizar thumbnails activos
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
            if (thumb.src === imageSrc) {
                thumb.classList.add('active');
            }
        });
    };
    
    /**
     * Obtener categoría desde la ruta actual (dinámico)
     * @returns {string}
     */
    function getCategoriaFromPath() {
        const path = window.location.pathname;
        // Formato: /categoria/slug
        const match = path.match(/^\/([^\/]+)\/([^\/]+)\/?$/);
        if (match && match[1]) {
            // Excluir rutas especiales
            const excluded = ['api', 'admin', 'ideas', 'carrito', 'wishlist', 'mis-pedidos'];
            if (!excluded.includes(match[1])) {
                return match[1];
            }
        }
        return 'productos'; // Fallback
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
     * Inicializar cargador de detalle
     * @param {string} slug - Slug del producto
     * @param {Object} options
     */
    window.initProductDetail = async function(slug, options = {}) {
        const {
            containerSelector = '.product-detail-container',
            autoLoad = true
        } = options;
        
        const container = document.querySelector(containerSelector);
        
        if (!container) {
            console.warn(`Contenedor "${containerSelector}" no encontrado`);
            return;
        }
        
        if (!slug) {
            showError(container, 'Slug del producto no proporcionado');
            return;
        }
        
        if (autoLoad) {
            try {
                showLoading(container);
                const product = await loadProductBySlug(slug);
                
                if (!product) {
                    showError(container, 'Producto no encontrado');
                    return;
                }
                
                renderProductDetail(product, container);
                
                // Actualizar estado del botón de wishlist después de renderizar
                if (window.isInWishlist && product.id) {
                    setTimeout(async () => {
                        const isIn = await window.isInWishlist(product.id);
                        if (isIn && window.updateWishlistButtons) {
                            window.updateWishlistButtons(product.id, true);
                        }
                    }, 500);
                }
                
                
            } catch (error) {
                showError(container, error.message || 'Error desconocido');
            }
        }
        
        // Retornar función para recargar manualmente
        return {
            reload: async () => {
                try {
                    showLoading(container);
                    const product = await loadProductBySlug(slug);
                    if (product) {
                        renderProductDetail(product, container);
                    }
                } catch (error) {
                    showError(container, error.message || 'Error desconocido');
                }
            }
        };
    };
    
    // Auto-inicializar si hay un contenedor con clase 'product-detail-container'
    document.addEventListener('DOMContentLoaded', function() {
        const autoContainer = document.querySelector('.product-detail-container');
        if (autoContainer) {
            // Intentar obtener slug desde la URL
            const path = window.location.pathname;
            // Detectar cualquier categoría dinámicamente (formato: /categoria/slug)
            // Excluir rutas especiales
            const excluded = ['api', 'admin', 'ideas', 'carrito', 'wishlist', 'mis-pedidos'];
            const slugMatch = path.match(/^\/([^\/]+)\/([^\/]+)\/?$/);
            
            if (slugMatch && slugMatch[1] && slugMatch[2] && !excluded.includes(slugMatch[1])) {
                const slug = slugMatch[2];
                window.initProductDetail(slug, {
                    containerSelector: '.product-detail-container',
                    autoLoad: true
                });
            }
        }
    });
    
})();

