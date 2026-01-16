/**
 * ============================================
 * Cargador de Galería
 * ============================================
 * Carga imágenes de la galería desde la API
 * ============================================
 */

(function() {
    'use strict';
    
    const isLocalDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const currentPort = window.location.port;
    
    let API_BASE = '/api/galeria.php';
    if (isLocalDev && (currentPort === '4321' || currentPort === '')) {
        API_BASE = 'http://localhost:8080/api/galeria.php';
    }
    
    /**
     * Cargar galería desde la API
     */
    async function loadGaleria() {
        try {
            const response = await fetch(API_BASE);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                console.error('Respuesta recibida:', text.substring(0, 200));
                throw new Error('La respuesta del servidor no es válida JSON. Verifica la consola para más detalles.');
            }
            
            if (!data.success) {
                throw new Error(data.error || 'Error desconocido al cargar la galería');
            }
            
            return data.galeria || [];
        } catch (error) {
            console.error('Error al cargar galería:', error);
            return [];
        }
    }
    
    /**
     * Renderizar galería en el contenedor
     */
    function renderGaleria(galeria, containerSelector) {
        const container = document.querySelector(containerSelector);
        if (!container) {
            console.warn(`Contenedor no encontrado: ${containerSelector}`);
            return;
        }
        
        if (galeria.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 2rem; color: #666;">No hay imágenes en la galería.</p>';
            return;
        }
        
        // Generar HTML del grid
        const gridHTML = galeria.map((item, idx) => `
            <figure class="card">
                <img
                    src="${item.src}"
                    alt="${item.alt || `Idea ${idx + 1}`}"
                    loading="lazy"
                    decoding="async"
                    class="thumb"
                    data-index="${idx}"
                />
            </figure>
        `).join('');
        
        container.innerHTML = gridHTML;
        
        // Generar HTML del lightbox (tira de miniaturas)
        const lightbox = document.getElementById('lightbox');
        if (lightbox) {
            const thumbstrip = document.getElementById('thumbstrip');
            if (thumbstrip) {
                thumbstrip.innerHTML = galeria.map((item, idx) => `
                    <button
                        class="strip-item"
                        data-index="${idx}"
                        aria-label="Ir a ${item.alt || `imagen ${idx + 1}`}"
                        role="option"
                    >
                        <img src="${item.src}" alt="${item.alt || `Idea ${idx + 1}`}" loading="lazy" decoding="async" />
                    </button>
                `).join('');
            }
        }
        
        // Inicializar lightbox después de renderizar
        initLightbox();
    }
    
    /**
     * Inicializar funcionalidad del lightbox
     */
    function initLightbox() {
        const items = Array.from(document.querySelectorAll(".grid img.thumb"));
        const lightbox = document.getElementById("lightbox");
        if (!lightbox) return;
        
        const lightboxImg = document.getElementById("lightbox-img");
        const closeBtn = lightbox.querySelector(".close");
        const prevBtn = lightbox.querySelector(".nav-left");
        const nextBtn = lightbox.querySelector(".nav-right");
        const strip = document.getElementById("thumbstrip");
        
        if (!lightboxImg || !closeBtn || !prevBtn || !nextBtn || !strip) return;
        
        const stripButtons = Array.from(strip.querySelectorAll(".strip-item"));
        let current = 0;
        let touchStartX = 0, touchEndX = 0;
        const total = items.length;
        
        function setActive(index) {
            current = (index + total) % total;
            const img = items[current];
            if (img) {
                lightboxImg.src = img.src;
                lightboxImg.alt = img.alt || "";
            }
            
            // activar miniatura
            stripButtons.forEach(btn => btn.classList.remove("active"));
            const activeBtn = stripButtons[current];
            if (activeBtn) {
                activeBtn.classList.add("active");
                centerThumb(activeBtn);
            }
        }
        
        function centerThumb(el) {
            const container = strip;
            const cRect = container.getBoundingClientRect();
            const eRect = el.getBoundingClientRect();
            const offset = (eRect.left + eRect.width/2) - (cRect.left + cRect.width/2);
            container.scrollBy({ left: offset, behavior: "smooth" });
        }
        
        function openLightbox(index) {
            setActive(index);
            lightbox.classList.remove("hidden");
            lightbox.setAttribute("aria-hidden","false");
            document.documentElement.style.overflow = "hidden";
        }
        
        function closeLightbox() {
            lightbox.classList.add("hidden");
            lightbox.setAttribute("aria-hidden","true");
            lightboxImg.removeAttribute("src");
            document.documentElement.style.overflow = "";
        }
        
        function next() { setActive(current + 1); }
        function prev() { setActive(current - 1); }
        
        // abrir desde grid
        const grid = document.querySelector(".grid");
        if (grid) {
            grid.addEventListener("click", (e) => {
                const img = e.target.closest("img.thumb");
                if (!img) return;
                openLightbox(parseInt(img.dataset.index, 10) || 0);
            });
        }
        
        // cerrar por overlay
        lightbox.addEventListener("click", (e) => {
            const clickedInsideControls =
                e.target.closest(".close") ||
                e.target.closest(".nav") ||
                e.target.closest(".thumbstrip") ||
                e.target === lightboxImg;
            if (!clickedInsideControls) closeLightbox();
        });
        
        closeBtn.addEventListener("click", closeLightbox);
        nextBtn.addEventListener("click", next);
        prevBtn.addEventListener("click", prev);
        
        // miniaturas en tira
        strip.addEventListener("click", (e) => {
            const btn = e.target.closest(".strip-item");
            if (!btn) return;
            const idx = parseInt(btn.dataset.index ?? "-1", 10);
            if (idx >= 0) setActive(idx);
        });
        
        // teclado
        document.addEventListener("keydown", (e) => {
            if (lightbox.classList.contains("hidden")) return;
            if (e.key === "Escape") closeLightbox();
            if (e.key === "ArrowRight") next();
            if (e.key === "ArrowLeft") prev();
        });
        
        // swipe en imagen (mobile)
        lightboxImg.addEventListener("touchstart", (e) => {
            touchStartX = e.changedTouches[0].clientX;
        }, { passive: true });
        lightboxImg.addEventListener("touchend", (e) => {
            touchEndX = e.changedTouches[0].clientX;
            const dx = touchEndX - touchStartX;
            if (Math.abs(dx) > 40) { dx < 0 ? next() : prev(); }
        }, { passive: true });
        
        // Marcar data-index en strip
        stripButtons.forEach((btn, i) => btn.dataset.index = i);
    }
    
    /**
     * Función principal para cargar y renderizar
     */
    async function loadAndRender(containerSelector = '.grid') {
        const galeria = await loadGaleria();
        renderGaleria(galeria, containerSelector);
    }
    
    // Exportar funciones globalmente
    window.loadGaleria = loadGaleria;
    window.renderGaleria = renderGaleria;
    window.loadAndRenderGaleria = loadAndRender;
    
    // Auto-cargar si hay un contenedor con data-galeria="true"
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            const autoLoadContainer = document.querySelector('[data-galeria="true"]');
            if (autoLoadContainer) {
                loadAndRender('[data-galeria="true"]');
            }
        });
    } else {
        const autoLoadContainer = document.querySelector('[data-galeria="true"]');
        if (autoLoadContainer) {
            loadAndRender('[data-galeria="true"]');
        }
    }
})();

