/**
 * ============================================
 * WISHLIST MANAGER
 * ============================================
 * Maneja la funcionalidad de wishlist/favoritos
 * ============================================
 */

(function() {
    'use strict';
    
    // Obtener o crear session ID
    function getSessionId() {
        let sessionId = localStorage.getItem('wishlist_session_id');
        if (!sessionId) {
            sessionId = 'wishlist_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('wishlist_session_id', sessionId);
        }
        return sessionId;
    }
    
    // Exponer función para uso global
    window.getWishlistSessionId = getSessionId;
    
    /**
     * Agregar producto a wishlist
     * @param {string} productId - ID del producto
     * @param {Function} callback - Callback opcional
     */
    window.addToWishlist = async function(productId, callback) {
        const sessionId = getSessionId();
        
        try {
            const response = await fetch('/api/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    product_id: productId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                updateWishlistButtons(productId, true);
                if (callback) callback(true, data.message);
                showNotification('❤️ Agregado a favoritos', 'success');
            } else {
                if (callback) callback(false, data.error);
                showNotification(data.error || 'Error al agregar a favoritos', 'error');
            }
        } catch (error) {
            console.error('Error al agregar a wishlist:', error);
            if (callback) callback(false, error.message);
            showNotification('Error al agregar a favoritos', 'error');
        }
    };
    
    /**
     * Eliminar producto de wishlist
     * @param {string} productId - ID del producto
     * @param {Function} callback - Callback opcional
     */
    window.removeFromWishlist = async function(productId, callback) {
        const sessionId = getSessionId();
        
        try {
            const response = await fetch('/api/wishlist.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    product_id: productId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                updateWishlistButtons(productId, false);
                if (callback) callback(true, data.message);
                showNotification('Eliminado de favoritos', 'info');
            } else {
                if (callback) callback(false, data.error);
                showNotification(data.error || 'Error al eliminar de favoritos', 'error');
            }
        } catch (error) {
            console.error('Error al eliminar de wishlist:', error);
            if (callback) callback(false, error.message);
            showNotification('Error al eliminar de favoritos', 'error');
        }
    };
    
    /**
     * Verificar si un producto está en wishlist
     * @param {string} productId - ID del producto
     * @returns {Promise<boolean>}
     */
    window.isInWishlist = async function(productId) {
        const sessionId = getSessionId();
        
        try {
            const response = await fetch(`/api/wishlist.php?session_id=${encodeURIComponent(sessionId)}`);
            const data = await response.json();
            
            if (data.success && data.items) {
                return data.items.some(item => item.product_id === productId);
            }
            return false;
        } catch (error) {
            console.error('Error al verificar wishlist:', error);
            return false;
        }
    };
    
    /**
     * Toggle wishlist (agregar o eliminar)
     * @param {string} productId - ID del producto
     */
    window.toggleWishlist = async function(productId) {
        const isIn = await window.isInWishlist(productId);
        
        if (isIn) {
            await window.removeFromWishlist(productId);
        } else {
            await window.addToWishlist(productId);
        }
    };
    
    /**
     * Actualizar estado de botones de wishlist
     * @param {string} productId - ID del producto
     * @param {boolean} isInWishlist - Si está en wishlist
     */
    function updateWishlistButtons(productId, isInWishlist) {
        const buttons = document.querySelectorAll(`[data-wishlist-id="${productId}"]`);
        buttons.forEach(button => {
            if (isInWishlist) {
                button.classList.add('in-wishlist');
                if (button.classList.contains('wishlist-btn-detail')) {
                    button.innerHTML = '❤️ En favoritos';
                } else {
                    button.innerHTML = '❤️';
                }
                button.title = 'Eliminar de favoritos';
            } else {
                button.classList.remove('in-wishlist');
                if (button.classList.contains('wishlist-btn-detail')) {
                    button.innerHTML = '🤍 Agregar a favoritos';
                } else {
                    button.innerHTML = '🤍';
                }
                button.title = 'Agregar a favoritos';
            }
        });
    }
    
    // Exponer función globalmente
    window.updateWishlistButtons = updateWishlistButtons;
    
    /**
     * Cargar estado inicial de wishlist para todos los productos visibles
     */
    window.loadWishlistStates = async function() {
        const sessionId = getSessionId();
        const wishlistButtons = document.querySelectorAll('[data-wishlist-id]');
        
        if (wishlistButtons.length === 0) return;
        
        try {
            const response = await fetch(`/api/wishlist.php?session_id=${encodeURIComponent(sessionId)}`);
            const data = await response.json();
            
            if (data.success && data.items) {
                const wishlistIds = new Set(data.items.map(item => item.product_id));
                
                wishlistButtons.forEach(button => {
                    const productId = button.getAttribute('data-wishlist-id');
                    if (wishlistIds.has(productId)) {
                        updateWishlistButtons(productId, true);
                    }
                });
            }
        } catch (error) {
            console.error('Error al cargar estados de wishlist:', error);
        }
    };
    
    /**
     * Mostrar notificación
     * @param {string} message - Mensaje
     * @param {string} type - Tipo (success, error, info)
     */
    function showNotification(message, type = 'info') {
        // Detectar si es mobile
        const isMobile = window.innerWidth <= 768;
        
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `wishlist-notification ${type}`;
        notification.textContent = message;
        
        // Estilos base
        const baseStyles = `
            position: fixed;
            background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        // Estilos según dispositivo
        if (isMobile) {
            // Mobile: parte inferior central
            notification.style.cssText = baseStyles + `
                bottom: 20px;
                left: 50%;
                max-width: 90%;
                text-align: center;
            `;
            notification.style.transform = 'translateX(-50%)';
        } else {
            // Desktop: esquina superior derecha
            notification.style.cssText = baseStyles + `
                top: 20px;
                right: 20px;
            `;
        }
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (isMobile) {
                notification.classList.add('slide-out');
            } else {
                notification.style.animation = 'slideOut 0.3s ease';
            }
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    /**
     * Actualizar contador de wishlist en el navbar
     */
    async function updateWishlistCount() {
        const sessionId = getSessionId();
        
        try {
            const response = await fetch(`/api/wishlist.php?session_id=${encodeURIComponent(sessionId)}`);
            const data = await response.json();
            
            const count = data.success && data.items ? data.items.length : 0;
            
            // Actualizar contadores
            const countElements = document.querySelectorAll('#wishlist-count, #wishlist-count-mobile');
            countElements.forEach(el => {
                el.textContent = count;
                el.style.display = count > 0 ? 'block' : 'none';
            });
        } catch (error) {
            console.error('Error al actualizar contador de wishlist:', error);
        }
    }
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            loadWishlistStates();
            updateWishlistCount();
        });
    } else {
        loadWishlistStates();
        updateWishlistCount();
    }
    
    // Actualizar contador después de agregar/eliminar
    const originalAdd = window.addToWishlist;
    const originalRemove = window.removeFromWishlist;
    
    window.addToWishlist = async function(productId, callback) {
        await originalAdd(productId, callback);
        updateWishlistCount();
    };
    
    window.removeFromWishlist = async function(productId, callback) {
        const result = await originalRemove(productId, callback);
        updateWishlistCount();
        return result;
    };
    
    // Agregar estilos de animación
    if (!document.getElementById('wishlist-styles')) {
        const style = document.createElement('style');
        style.id = 'wishlist-styles';
        style.textContent = `
            @media (max-width: 768px) {
                .wishlist-notification {
                    animation: slideInMobile 0.3s ease !important;
                }
                .wishlist-notification.slide-out {
                    animation: slideOutMobile 0.3s ease !important;
                }
                @keyframes slideInMobile {
                    from {
                        opacity: 0;
                        transform: translateX(-50%) translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(-50%) translateY(0);
                    }
                }
                @keyframes slideOutMobile {
                    from {
                        opacity: 1;
                        transform: translateX(-50%) translateY(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(-50%) translateY(20px);
                    }
                }
            }
            @media (min-width: 769px) {
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            }
        `;
        document.head.appendChild(style);
    }
})();

