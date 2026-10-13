/**
 * MYG - Suscripción de Películas, Series y Juegos
 * Lógica Frontend Principal:
 * - Slider automático y controles manuales
 * - Navbar scrolled y menú hamburguesa táctil con backdrop blur
 * - Live Search en catálogo con normalize("NFD") (insensible a tildes y mayúsculas)
 * - Filtros por Pills de categorías
 * - Acordeón de Preguntas Frecuentes (FAQ) interactivo
 * - Formulario de Contacto Asíncrono con Fetch/AJAX y Toast instantáneo
 * - Modal de Políticas de Privacidad y Términos
 * - Integración con Carrito de compras
 */

document.addEventListener('DOMContentLoaded', () => {

    // ==============================================================================
    // 1. NAVBAR SCROLLED & MENÚ HAMBURGUESA TÁCTIL CON DRAWER
    // ==============================================================================
    const navbar        = document.querySelector('.navbar');
    const btnHamburger  = document.querySelector('.btn-hamburger');
    const navLinks      = document.querySelector('.nav-links');
    const navOverlay    = document.querySelector('.nav-overlay');
    const navLinkItems  = document.querySelectorAll('.nav-link');

    // Cambiar fondo de la navbar al hacer scroll
    window.addEventListener('scroll', () => {
        if (window.scrollY > 40) {
            navbar?.classList.add('scrolled');
        } else {
            navbar?.classList.remove('scrolled');
        }
    });

    function toggleMobileMenu() {
        navLinks?.classList.toggle('active');
        navOverlay?.classList.toggle('active');
        document.body.style.overflow = navLinks?.classList.contains('active') ? 'hidden' : '';
    }

    btnHamburger?.addEventListener('click', toggleMobileMenu);
    navOverlay?.addEventListener('click', toggleMobileMenu);

    navLinkItems.forEach(link => {
        link.addEventListener('click', () => {
            if (navLinks?.classList.contains('active')) {
                toggleMobileMenu();
            }
        });
    });

    // ==============================================================================
    // 2. HERO SLIDER AUTOMÁTICO (5 SEGUNDOS) Y MANUAL
    // ==============================================================================
    const slides = document.querySelectorAll('.slide');
    const dots   = document.querySelectorAll('.dot');
    const btnPrev = document.querySelector('.slider-btn.prev');
    const btnNext = document.querySelector('.slider-btn.next');
    let currentSlide = 0;
    let sliderInterval = null;
    const slideDuration = 5000;

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));

        if (index >= slides.length) currentSlide = 0;
        else if (index < 0) currentSlide = slides.length - 1;
        else currentSlide = index;

        slides[currentSlide]?.classList.add('active');
        dots[currentSlide]?.classList.add('active');
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
    }

    function startSliderAutoplay() {
        if (slides.length > 0) {
            sliderInterval = setInterval(nextSlide, slideDuration);
        }
    }

    function stopSliderAutoplay() {
        if (sliderInterval) clearInterval(sliderInterval);
    }

    btnNext?.addEventListener('click', () => {
        nextSlide();
        stopSliderAutoplay();
        startSliderAutoplay();
    });

    btnPrev?.addEventListener('click', () => {
        prevSlide();
        stopSliderAutoplay();
        startSliderAutoplay();
    });

    dots.forEach((dot, idx) => {
        dot.addEventListener('click', () => {
            showSlide(idx);
            stopSliderAutoplay();
            startSliderAutoplay();
        });
    });

    const sliderContainer = document.querySelector('.slider-container');
    sliderContainer?.addEventListener('mouseenter', stopSliderAutoplay);
    sliderContainer?.addEventListener('mouseleave', startSliderAutoplay);

    startSliderAutoplay();

    // ==============================================================================
    // 3. CATÁLOGO INTERACTIVO: LIVE SEARCH CON NORMALIZE("NFD") & PILLS
    // ==============================================================================
    const catalogSearchInput = document.getElementById('catalog-search-input');
    const headerSearchInput  = document.getElementById('header-search-input');
    const filterBtns         = document.querySelectorAll('.filter-btn');
    const productCards       = document.querySelectorAll('.product-card');

    let currentCategory = 'todos';
    let currentSearchTerm = '';

    // Función normalizadora: quita diacríticos/tildes y convierte a minúsculas
    function normalizarTexto(texto) {
        return (texto || '')
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .trim();
    }

    function filtrarCatalogo() {
        const queryNorm = normalizarTexto(currentSearchTerm);
        let visibles = 0;

        productCards.forEach(card => {
            const cardCat   = card.getAttribute('data-category') || '';
            const cardTitle = card.querySelector('.product-title')?.textContent || '';
            const cardDesc  = card.querySelector('.product-desc')?.textContent || '';
            const titleNorm = normalizarTexto(cardTitle);
            const descNorm  = normalizarTexto(cardDesc);

            const coincideCategoria = (currentCategory === 'todos' || cardCat === currentCategory);
            const coincideTexto     = !queryNorm || titleNorm.includes(queryNorm) || descNorm.includes(queryNorm);

            if (coincideCategoria && coincideTexto) {
                card.style.display = 'flex';
                card.style.animation = 'fadeIn 0.4s ease forwards';
                visibles++;
            } else {
                card.style.display = 'none';
            }
        });

        // Mensaje de sin resultados si aplica
        let noResultsEl = document.getElementById('catalog-no-results');
        if (!noResultsEl) {
            noResultsEl = document.createElement('div');
            noResultsEl.id = 'catalog-no-results';
            noResultsEl.style.gridColumn = '1 / -1';
            noResultsEl.style.textAlign = 'center';
            noResultsEl.style.padding = '3rem 1rem';
            noResultsEl.style.color = 'var(--text-muted)';
            noResultsEl.innerHTML = `
                <ion-icon name="search-outline" style="font-size: 3rem; color: var(--cyan); margin-bottom: 0.5rem;"></ion-icon>
                <h3 style="color: #fff; margin-bottom: 0.3rem;">No se encontraron productos</h3>
                <p>Intenta con otro término de búsqueda o selecciona otra categoría.</p>
            `;
            const grid = document.querySelector('.products-grid');
            grid?.appendChild(noResultsEl);
        }

        noResultsEl.style.display = visibles === 0 ? 'block' : 'none';
    }

    // Buscador en Catálogo
    catalogSearchInput?.addEventListener('input', () => {
        currentSearchTerm = catalogSearchInput.value;
        if (headerSearchInput) headerSearchInput.value = currentSearchTerm;
        filtrarCatalogo();
    });

    // Buscador en Navbar superior
    headerSearchInput?.addEventListener('input', () => {
        currentSearchTerm = headerSearchInput.value;
        if (catalogSearchInput) catalogSearchInput.value = currentSearchTerm;

        // Desplazar suavemente hacia el catálogo si no está a la vista
        const catalogoSection = document.getElementById('catalogo');
        if (catalogoSection) {
            const rect = catalogoSection.getBoundingClientRect();
            if (rect.top < 0 || rect.top > window.innerHeight) {
                catalogoSection.scrollIntoView({ behavior: 'smooth' });
            }
        }
        filtrarCatalogo();
    });

    // Pills de Categoría
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCategory = btn.getAttribute('data-filter') || 'todos';
            filtrarCatalogo();
        });
    });

    // ==============================================================================
    // 4. BOTONES "AÑADIR AL CARRITO" DESDE LAS TARJETAS DE PRODUCTOS
    // ==============================================================================
    document.addEventListener('click', (e) => {
        const btnAdd = e.target.closest('.btn-add-cart');
        if (btnAdd) {
            e.preventDefault();
            const id    = btnAdd.getAttribute('data-id');
            const name  = btnAdd.getAttribute('data-name');
            const price = btnAdd.getAttribute('data-price');
            const image = btnAdd.getAttribute('data-image');

            if (window.addToCart) {
                window.addToCart({ id, name, price, image });
            }
        }
    });

    // ==============================================================================
    // 5. ACORDEÓN DE PREGUNTAS FRECUENTES (FAQ INTERACTIVO)
    // ==============================================================================
    const faqHeaders = document.querySelectorAll('.faq-header');

    faqHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const item = header.closest('.faq-item');
            const body = item?.querySelector('.faq-body');
            const isActive = item?.classList.contains('active');

            // Cerrar otros ítems para comportamiento de acordeón limpio
            document.querySelectorAll('.faq-item').forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    const otherBody = otherItem.querySelector('.faq-body');
                    if (otherBody) otherBody.style.maxHeight = null;
                }
            });

            if (isActive) {
                item.classList.remove('active');
                if (body) body.style.maxHeight = null;
            } else {
                item.classList.add('active');
                if (body) body.style.maxHeight = body.scrollHeight + 'px';
            }
        });
    });

    // ==============================================================================
    // 6. FORMULARIO DE CONTACTO ASÍNCRONO CON FETCH/AJAX
    // ==============================================================================
    const formContacto = document.getElementById('form-contacto');
    const btnSubmitContacto = document.getElementById('btn-submit-contacto');

    formContacto?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const nombre  = document.getElementById('contacto_nombre')?.value.trim();
        const email   = document.getElementById('contacto_email')?.value.trim();
        const mensaje = document.getElementById('contacto_mensaje')?.value.trim();

        if (!nombre || !email || !mensaje) {
            mostrarToast('Por favor complete todos los campos.', 'warning');
            return;
        }

        btnSubmitContacto.disabled = true;
        const originalHtml = btnSubmitContacto.innerHTML;
        btnSubmitContacto.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Enviando mensaje...';

        try {
            const res = await fetch('api_contacto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ nombre, email, mensaje })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                mostrarToast(data.mensaje || '¡Mensaje enviado con éxito!', 'success');
                formContacto.reset();
            } else {
                mostrarToast(data.mensaje || 'Error al enviar el mensaje.', 'danger');
            }
        } catch (err) {
            console.error(err);
            mostrarToast('Error de conexión al enviar el mensaje.', 'danger');
        } finally {
            btnSubmitContacto.disabled = false;
            btnSubmitContacto.innerHTML = originalHtml;
        }
    });

    // ==============================================================================
    // 7. MODAL DE POLÍTICAS DE PRIVACIDAD Y TÉRMINOS
    // ==============================================================================
    const modalPrivacy   = document.getElementById('modal-privacy');
    const triggersPrivacy = document.querySelectorAll('.trigger-privacy');
    const btnClosePrivacy = document.querySelectorAll('.close-privacy');

    triggersPrivacy.forEach(t => {
        t.addEventListener('click', (e) => {
            e.preventDefault();
            modalPrivacy?.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    btnClosePrivacy.forEach(b => {
        b.addEventListener('click', () => {
            modalPrivacy?.classList.remove('active');
            document.body.style.overflow = '';
        });
    });

    modalPrivacy?.addEventListener('click', (e) => {
        if (e.target === modalPrivacy) {
            modalPrivacy.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // ==============================================================================
    // 8. SCROLL REVEAL (INTERSECTION OBSERVER)
    // ==============================================================================
    const revealElements = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });

        revealElements.forEach(el => revealObserver.observe(el));
    } else {
        revealElements.forEach(el => el.classList.add('active'));
    }

    // ==============================================================================
    // 9. BOTÓN VOLVER ARRIBA (BACK TO TOP)
    // ==============================================================================
    const backToTopBtn = document.getElementById('back-to-top');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 400) {
            backToTopBtn?.classList.add('show');
        } else {
            backToTopBtn?.classList.remove('show');
        }
    });

    backToTopBtn?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // ==============================================================================
    // 10. SISTEMA DE ALERTAS TOAST FLOTANTES
    // ==============================================================================
    function mostrarToast(mensaje, tipo = 'info') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast-message toast-${tipo}`;

        let icono = 'information-circle-outline';
        if (tipo === 'success') icono = 'checkmark-circle-outline';
        if (tipo === 'warning') icono = 'alert-circle-outline';
        if (tipo === 'danger')  icono = 'close-circle-outline';

        toast.innerHTML = `
            <ion-icon name="${icono}"></ion-icon>
            <span>${mensaje}</span>
            <button type="button" class="toast-close">&times;</button>
        `;

        toast.querySelector('.toast-close')?.addEventListener('click', () => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        });

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 400);
        }, 4500);
    }

    window.mostrarToast = mostrarToast;

});
