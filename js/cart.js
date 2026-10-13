/**
 * MYG - Carrito de planes de suscripción + pasarela de pago simulada
 */

(function () {
    const STORAGE_KEY = 'myg_cart_items';
    let cart = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];

    const cartDrawer      = document.getElementById('cart-drawer');
    const drawerOverlay   = document.getElementById('drawer-overlay');
    const btnOpenCart     = document.getElementById('btn-open-cart');
    const btnCloseDrawer  = document.getElementById('btn-close-drawer');
    const cartBadge       = document.getElementById('cart-badge');
    const cartItemsList   = document.getElementById('cart-items-container');
    const emptyCartMsg    = document.getElementById('empty-cart-message');
    const cartSubtotalEl  = document.getElementById('cart-subtotal');
    const cartShippingEl  = document.getElementById('cart-shipping');
    const cartTotalEl     = document.getElementById('cart-total');
    const checkoutSection = document.getElementById('checkout-drawer-section');
    const formCheckout    = document.getElementById('form-drawer-checkout');
    const btnConfirmOrder = document.getElementById('btn-confirm-order');

    const inputCedula     = document.getElementById('checkout_cedula');
    const inputNombre     = document.getElementById('checkout_nombre');
    const inputEmail      = document.getElementById('checkout_email');
    const inputTelefono   = document.getElementById('checkout_telefono');
    const inputDireccion  = document.getElementById('checkout_direccion');
    const cedulaStatus    = document.getElementById('checkout_cedula_status');

    const inputTitular    = document.getElementById('pago_titular');
    const inputNumero     = document.getElementById('pago_numero');
    const inputVence      = document.getElementById('pago_vencimiento');
    const inputCvv        = document.getElementById('pago_cvv');
    const pagoStatus      = document.getElementById('pago-sim-status');

    function saveCart() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
        updateBadge();
        renderCart();
    }

    function updateBadge() {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        if (cartBadge) {
            cartBadge.textContent = totalItems;
            if (totalItems > 0) {
                cartBadge.classList.add('visible');
                cartBadge.classList.remove('bounce');
                void cartBadge.offsetWidth;
                cartBadge.classList.add('bounce');
            } else {
                cartBadge.classList.remove('visible');
            }
        }
    }

    function openDrawer() {
        cartDrawer?.classList.add('open');
        drawerOverlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        cartDrawer?.classList.remove('open');
        drawerOverlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    btnOpenCart?.addEventListener('click', (e) => {
        e.preventDefault();
        openDrawer();
    });

    btnCloseDrawer?.addEventListener('click', closeDrawer);
    drawerOverlay?.addEventListener('click', closeDrawer);

    function formatearCOP(valor) {
        return '$ ' + Math.round(Number(valor)).toLocaleString('es-CO') + ' COP';
    }

    function renderCart() {
        if (!cartItemsList) return;

        if (cart.length === 0) {
            cartItemsList.innerHTML = '';
            if (emptyCartMsg) emptyCartMsg.style.display = 'block';
            if (checkoutSection) checkoutSection.style.display = 'none';
            if (cartSubtotalEl) cartSubtotalEl.textContent = '$ 0 COP';
            if (cartShippingEl) cartShippingEl.textContent = 'Digital';
            if (cartTotalEl) cartTotalEl.textContent = '$ 0 COP';
            return;
        }

        if (emptyCartMsg) emptyCartMsg.style.display = 'none';
        if (checkoutSection) checkoutSection.style.display = 'block';

        let subtotal = 0;

        cartItemsList.innerHTML = cart.map(item => {
            const itemSubtotal = item.price * item.quantity;
            subtotal += itemSubtotal;

            return `
                <div class="cart-item-card" data-id="${item.id}">
                    <img src="${item.image}" alt="${escapeHtml(item.name)}" class="cart-item-img">
                    <div class="cart-item-info">
                        <h4 class="cart-item-title">${escapeHtml(item.name)}</h4>
                        <div class="cart-item-price">${formatearCOP(item.price)} / mes</div>
                        <div class="cart-item-actions">
                            <div class="cart-qty-ctrl">
                                <button type="button" class="btn-qty-minus" data-id="${item.id}">-</button>
                                <span class="cart-qty-val">${item.quantity}</span>
                                <button type="button" class="btn-qty-plus" data-id="${item.id}">+</button>
                            </div>
                            <button type="button" class="btn-remove-item" data-id="${item.id}" title="Quitar plan">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        if (cartSubtotalEl) cartSubtotalEl.textContent = formatearCOP(subtotal);
        if (cartShippingEl) cartShippingEl.textContent = 'Acceso digital';
        if (cartTotalEl) cartTotalEl.textContent = formatearCOP(subtotal);
    }

    window.addToCart = function (product) {
        const existingIndex = cart.findIndex(item => item.id === product.id);

        if (existingIndex > -1) {
            cart[existingIndex].quantity += 1;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                image: product.image,
                quantity: 1
            });
        }

        saveCart();
        openDrawer();
        if (window.mostrarToast) {
            window.mostrarToast(`¡"${product.name}" añadido!`, 'success');
        }
    };

    cartItemsList?.addEventListener('click', (e) => {
        const btnMinus  = e.target.closest('.btn-qty-minus');
        const btnPlus   = e.target.closest('.btn-qty-plus');
        const btnRemove = e.target.closest('.btn-remove-item');

        if (btnMinus) {
            const id = btnMinus.getAttribute('data-id');
            const item = cart.find(i => i.id === id);
            if (item) {
                if (item.quantity > 1) item.quantity -= 1;
                else cart = cart.filter(i => i.id !== id);
                saveCart();
            }
            return;
        }

        if (btnPlus) {
            const id = btnPlus.getAttribute('data-id');
            const item = cart.find(i => i.id === id);
            if (item) {
                item.quantity += 1;
                saveCart();
            }
            return;
        }

        if (btnRemove) {
            const id = btnRemove.getAttribute('data-id');
            cart = cart.filter(i => i.id !== id);
            saveCart();
            if (window.mostrarToast) {
                window.mostrarToast('Plan eliminado del carrito.', 'info');
            }
        }
    });

    // Formato visual de tarjeta / vencimiento
    inputNumero?.addEventListener('input', () => {
        const digits = inputNumero.value.replace(/\D/g, '').slice(0, 19);
        inputNumero.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
    });

    inputVence?.addEventListener('input', () => {
        let v = inputVence.value.replace(/\D/g, '').slice(0, 4);
        if (v.length >= 3) v = v.slice(0, 2) + '/' + v.slice(2);
        inputVence.value = v;
    });

    inputCvv?.addEventListener('input', () => {
        inputCvv.value = inputCvv.value.replace(/\D/g, '').slice(0, 4);
    });

    let cedulaSearchTimer = null;

    inputCedula?.addEventListener('input', () => {
        const cedula = inputCedula.value.trim();
        clearTimeout(cedulaSearchTimer);

        if (!cedula) {
            if (cedulaStatus) {
                cedulaStatus.style.display = 'none';
                cedulaStatus.textContent = '';
            }
            limpiarCamposCliente(false);
            return;
        }

        if (cedulaStatus) {
            cedulaStatus.className = 'cedula-lookup-badge loading';
            cedulaStatus.textContent = 'Buscando cédula...';
            cedulaStatus.style.display = 'inline-flex';
        }

        cedulaSearchTimer = setTimeout(async () => {
            try {
                const res = await fetch(`api_cliente_buscar.php?cedula=${encodeURIComponent(cedula)}`);
                const data = await res.json();

                if (data.success && data.encontrado && data.cliente) {
                    const c = data.cliente;
                    inputNombre.value = c.nombre || '';
                    inputEmail.value = c.email || '';
                    inputTelefono.value = c.telefono || '';
                    if (inputDireccion) inputDireccion.value = c.direccion || '';
                    setCamposReadonly(true);

                    if (cedulaStatus) {
                        cedulaStatus.className = 'cedula-lookup-badge found';
                        cedulaStatus.textContent = `✓ Suscriptor: ${c.nombre}`;
                        cedulaStatus.style.display = 'inline-flex';
                    }
                } else {
                    limpiarCamposCliente(false);
                    if (cedulaStatus) {
                        cedulaStatus.className = 'cedula-lookup-badge new';
                        cedulaStatus.textContent = 'Nuevo suscriptor: complete sus datos';
                        cedulaStatus.style.display = 'inline-flex';
                    }
                }
            } catch (err) {
                console.error(err);
                if (cedulaStatus) cedulaStatus.style.display = 'none';
            }
        }, 300);
    });

    function setCamposReadonly(readonly) {
        [inputNombre, inputTelefono, inputDireccion].forEach(input => {
            if (!input) return;
            input.readOnly = readonly;
            if (readonly) input.classList.add('input-readonly');
            else input.classList.remove('input-readonly');
        });
    }

    function limpiarCamposCliente(keepValues = false) {
        setCamposReadonly(false);
        if (!keepValues) {
            if (inputNombre) inputNombre.value = '';
            if (inputEmail) inputEmail.value = '';
            if (inputTelefono) inputTelefono.value = '';
            if (inputDireccion) inputDireccion.value = '';
        }
    }

    function setPagoStatus(tipo, texto) {
        if (!pagoStatus) return;
        pagoStatus.className = `pago-sim-status ${tipo}`;
        pagoStatus.textContent = texto;
        pagoStatus.style.display = 'block';
    }

    formCheckout?.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (cart.length === 0) {
            if (window.mostrarToast) window.mostrarToast('El carrito está vacío.', 'warning');
            return;
        }

        const cedula    = inputCedula.value.trim();
        const nombre    = inputNombre.value.trim();
        const email     = inputEmail.value.trim();
        const telefono  = inputTelefono.value.trim();
        const direccion = inputDireccion ? inputDireccion.value.trim() : '';
        const titular   = inputTitular?.value.trim() || '';
        const numero    = (inputNumero?.value || '').replace(/\s/g, '');
        const vencimiento = inputVence?.value.trim() || '';
        const cvv       = inputCvv?.value.trim() || '';

        if (!cedula || !nombre || !email) {
            if (window.mostrarToast) window.mostrarToast('Complete cédula, nombre y correo.', 'warning');
            return;
        }

        if (!titular || numero.length < 13 || !/^\d{2}\/\d{2}$/.test(vencimiento) || cvv.length < 3) {
            setPagoStatus('error', 'Complete la pasarela simulada: titular, tarjeta, MM/AA y CVV.');
            if (window.mostrarToast) window.mostrarToast('Datos de pago incompletos.', 'warning');
            return;
        }

        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        btnConfirmOrder.disabled = true;
        btnConfirmOrder.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Procesando pago...';
        setPagoStatus('processing', 'Conectando con pasarela simulada MYG Pay...');

        // Retraso artificial para simular autorización del banco
        await new Promise(r => setTimeout(r, 1200));

        try {
            const payload = {
                cedula,
                nombre,
                email,
                telefono,
                direccion,
                items: cart,
                subtotal: total,
                envio: 0,
                total,
                pago: {
                    metodo: 'tarjeta',
                    titular,
                    numero,
                    vencimiento,
                    cvv
                }
            };

            const response = await fetch('api_checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                setPagoStatus('ok', `Pago aprobado · Ref. ${data.ref_pago || 'SIM'}`);
                cart = [];
                saveCart();
                formCheckout.reset();
                limpiarCamposCliente(false);
                if (cedulaStatus) cedulaStatus.style.display = 'none';

                setTimeout(() => {
                    closeDrawer();
                    if (pagoStatus) pagoStatus.style.display = 'none';
                }, 900);

                if (window.mostrarToast) {
                    window.mostrarToast(data.mensaje || '¡Suscripción activada!', 'success');
                }
            } else {
                setPagoStatus('error', data.mensaje || 'Pago rechazado por la simulación.');
                if (window.mostrarToast) {
                    window.mostrarToast(data.mensaje || 'Error al procesar el pago.', 'danger');
                }
            }
        } catch (err) {
            console.error(err);
            setPagoStatus('error', 'No se pudo contactar la pasarela simulada.');
            if (window.mostrarToast) {
                window.mostrarToast('Error de conexión con el servidor.', 'danger');
            }
        } finally {
            btnConfirmOrder.disabled = false;
            btnConfirmOrder.innerHTML = '<ion-icon name="lock-closed-outline"></ion-icon> Pagar y Activar Suscripción';
        }
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    updateBadge();
    renderCart();
})();
