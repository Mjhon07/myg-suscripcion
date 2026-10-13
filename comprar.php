<?php
/**
 * MYG - Página de Compra Inteligente
 * Paso 1: Búsqueda y Validación AJAX de Cédula de Cliente
 * Paso 2: Cálculo Dinámico y Confirmación de Pedido
 */

require_once __DIR__ . '/conexion.php';

// ==============================================================================
// 1. ENDPOINT AJAX INTEGRADO (Búsqueda por Cédula)
// ==============================================================================
if (isset($_GET['buscar_cedula'])) {
    header('Content-Type: application/json; charset=utf-8');
    $cedula = trim($_GET['buscar_cedula']);

    if (empty($cedula)) {
        echo json_encode([
            'success'    => false,
            'mensaje'    => 'La cédula no puede estar vacía.',
            'encontrado' => false
        ]);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT id, nombre, cedula, telefono, email, direccion FROM clientes WHERE cedula = :cedula LIMIT 1");
        $stmt->execute([':cedula' => $cedula]);
        $cliente = $stmt->fetch();

        if ($cliente) {
            echo json_encode([
                'success'    => true,
                'encontrado' => true,
                'cliente'    => $cliente
            ]);
        } else {
            echo json_encode([
                'success'    => true,
                'encontrado' => false,
                'mensaje'    => 'Cédula no encontrada en el sistema.'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success'    => false,
            'mensaje'    => 'Error de consulta en la base de datos.',
            'encontrado' => false
        ]);
    }
    exit;
}

// ==============================================================================
// 2. PARÁMETROS DEL PRODUCTO DESDE LA URL
// ==============================================================================
$productoNombre = trim($_GET['producto'] ?? 'Marvel\'s Spider-Man 2 - PS5');
$productoPrecio = (float)($_GET['precio'] ?? 289900);
if ($productoPrecio <= 0) {
    $productoPrecio = 289900;
}
$errorParam = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - MYG Videojuegos y Películas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .checkout-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: radial-gradient(circle at 10% 20%, rgba(121, 40, 202, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(0, 242, 254, 0.15) 0%, transparent 40%),
                        var(--bg-dark);
            padding: 100px 1.5rem 3rem;
        }

        .checkout-container {
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .checkout-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 30%, var(--cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .checkout-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .step-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0, 242, 254, 0.1);
            color: var(--cyan);
            border: 1px solid rgba(0, 242, 254, 0.3);
            padding: 0.35rem 0.9rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .step-title {
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .step-title ion-icon {
            color: var(--purple-light);
            font-size: 1.6rem;
        }

        .search-group {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .search-group input {
            flex: 1;
        }

        .btn-search {
            padding: 0.85rem 1.6rem;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-dark));
            color: #060913;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 242, 254, 0.4);
        }

        .btn-reset {
            padding: 0.85rem 1.2rem;
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: none;
        }

        .btn-reset:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .client-banner {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: none;
            align-items: center;
            gap: 0.85rem;
            font-size: 0.95rem;
            line-height: 1.4;
            animation: fadeIn 0.4s ease forwards;
        }

        .client-banner.found {
            display: flex;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
        }

        .client-banner.new-user {
            display: flex;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #60a5fa;
        }

        .client-banner ion-icon {
            font-size: 1.6rem;
            flex-shrink: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .readonly-highlight {
            background: rgba(255, 255, 255, 0.03) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #94a3b8 !important;
            cursor: not-allowed;
        }

        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 2.5rem 0;
        }

        .order-summary {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 1rem;
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-top: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .summary-row.total-row {
            color: var(--cyan);
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 0.25rem 0.5rem;
            width: fit-content;
        }

        .qty-btn {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.2rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .qty-input {
            width: 45px;
            text-align: center;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .btn-confirm {
            width: 100%;
            padding: 1.1rem;
            background: linear-gradient(135deg, var(--purple-light), var(--cyan));
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 10px 25px rgba(121, 40, 202, 0.4);
            transition: all 0.3s ease;
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 242, 254, 0.4);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .btn-back:hover {
            color: var(--cyan);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .checkout-card {
                padding: 1.5rem;
            }
            .search-group {
                flex-direction: column;
            }
            .btn-search {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="checkout-page">

    <!-- Notificaciones Toast -->
    <div id="toast-container" class="toast-container"></div>

    <div class="checkout-container">
        <a href="index.html#catalogo" class="btn-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Volver al catálogo
        </a>

        <div class="checkout-header">
            <h1>Finalizar tu Pedido en MYG</h1>
            <p style="color: var(--text-muted);">Proceso de compra rápido, inteligente y seguro en 2 simples pasos.</p>
        </div>

        <div class="checkout-card">
            <form id="form-compra" action="procesar_compra.php" method="POST">
                <!-- ========================================== -->
                <!-- PASO 1: IDENTIFICACIÓN DEL CLIENTE -->
                <!-- ========================================== -->
                <div class="step-badge">Paso 1 de 2</div>
                <h2 class="step-title">
                    <ion-icon name="person-circle-outline"></ion-icon>
                    Identificación del Cliente
                </h2>

                <label for="buscar_cedula_input" style="display:block; margin-bottom: 0.5rem; font-weight: 600;">
                    Número de Cédula / Identificación <span style="color: var(--cyan);">*</span>
                </label>
                <div class="search-group">
                    <input type="text" id="buscar_cedula_input" class="form-control" placeholder="Ej: 1098765432" required autocomplete="off">
                    <button type="button" id="btn-buscar-cedula" class="btn-search">
                        <ion-icon name="search-outline"></ion-icon>
                        Buscar
                    </button>
                    <button type="button" id="btn-reset-cedula" class="btn-reset" title="Buscar otra cédula">
                        <ion-icon name="refresh-outline"></ion-icon>
                        Cambiar
                    </button>
                </div>

                <!-- Banners de Notificación de Búsqueda -->
                <div id="banner-encontrado" class="client-banner found">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                    <div>
                        <strong>¡Cliente Registrado!</strong>
                        <p id="banner-encontrado-texto" style="margin: 0; font-size: 0.9rem;">Hemos cargado tus datos automáticamente para agilizar tu compra.</p>
                    </div>
                </div>

                <div id="banner-nuevo" class="client-banner new-user">
                    <ion-icon name="information-circle-outline"></ion-icon>
                    <div>
                        <strong>Cliente Nuevo</strong>
                        <p style="margin: 0; font-size: 0.9rem;">No encontramos registros con esta cédula. Por favor completa tus datos para registrarte.</p>
                    </div>
                </div>

                <!-- Campo oculto para enviar la cédula con el formulario -->
                <input type="hidden" name="cedula" id="cedula_hidden">

                <!-- Campos de información personal -->
                <div id="campos-cliente" class="form-grid" style="margin-top: 1.5rem;">
                    <div class="form-group">
                        <label for="nombre">Nombre Completo *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Tu nombre y apellido" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico *</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="ejemplo@correo.com" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono / WhatsApp</label>
                        <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="+57 300 123 4567">
                    </div>

                    <div class="form-group">
                        <label for="direccion">Dirección de Entrega</label>
                        <input type="text" name="direccion" id="direccion" class="form-control" placeholder="Calle, Carrera o Edificio">
                    </div>
                </div>

                <div class="divider"></div>

                <!-- ========================================== -->
                <!-- PASO 2: DETALLE DEL PEDIDO -->
                <!-- ========================================== -->
                <div class="step-badge" style="background: rgba(121, 40, 202, 0.15); color: var(--purple-light); border-color: rgba(121, 40, 202, 0.4);">Paso 2 de 2</div>
                <h2 class="step-title">
                    <ion-icon name="cart-outline"></ion-icon>
                    Detalle del Pedido
                </h2>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="producto">Producto Seleccionado</label>
                        <input type="text" name="producto" id="producto" class="form-control readonly-highlight" value="<?= htmlspecialchars($productoNombre) ?>" readonly required>
                    </div>
                </div>

                <div class="order-summary" style="margin-top: 1.5rem;">
                    <div class="summary-row">
                        <span>Precio Unitario:</span>
                        <strong id="precio-unitario-text">$ <?= number_format($productoPrecio, 0, ',', '.') ?> COP</strong>
                    </div>

                    <div class="summary-row">
                        <span>Cantidad:</span>
                        <div class="qty-controls">
                            <button type="button" class="qty-btn" id="qty-minus" aria-label="Disminuir">-</button>
                            <input type="number" name="cantidad" id="cantidad" class="qty-input" value="1" min="1" max="10" readonly>
                            <button type="button" class="qty-btn" id="qty-plus" aria-label="Aumentar">+</button>
                        </div>
                    </div>

                    <div class="summary-row total-row">
                        <span>Total a Pagar:</span>
                        <span id="total-text">$ <?= number_format($productoPrecio, 0, ',', '.') ?> COP</span>
                    </div>
                </div>

                <!-- Campo oculto con el valor numérico del total -->
                <input type="hidden" name="total" id="total_hidden" value="<?= htmlspecialchars($productoPrecio) ?>">

                <button type="submit" id="btn-submit-compra" class="btn-confirm">
                    <ion-icon name="bag-check-outline"></ion-icon>
                    Confirmar Compra
                </button>
            </form>
        </div>
    </div>

    <script>
        // Configuración de Precio
        const precioUnitario = <?= json_encode($productoPrecio) ?>;
        
        // Elementos del DOM
        const cedulaInput    = document.getElementById('buscar_cedula_input');
        const cedulaHidden   = document.getElementById('cedula_hidden');
        const btnBuscar      = document.getElementById('btn-buscar-cedula');
        const btnReset       = document.getElementById('btn-reset-cedula');
        const bannerFound    = document.getElementById('banner-encontrado');
        const bannerFoundTxt = document.getElementById('banner-encontrado-texto');
        const bannerNew      = document.getElementById('banner-nuevo');
        
        const inputNombre    = document.getElementById('nombre');
        const inputEmail     = document.getElementById('email');
        const inputTelefono  = document.getElementById('telefono');
        const inputDireccion = document.getElementById('direccion');

        const qtyMinus       = document.getElementById('qty-minus');
        const qtyPlus        = document.getElementById('qty-plus');
        const qtyInput       = document.getElementById('cantidad');
        const totalText      = document.getElementById('total-text');
        const totalHidden    = document.getElementById('total_hidden');
        const formCompra     = document.getElementById('form-compra');

        // Búsqueda AJAX de Cédula
        async function buscarCedula() {
            const cedula = cedulaInput.value.trim();
            if (!cedula) {
                mostrarToast('Por favor ingrese una cédula para continuar.', 'warning');
                cedulaInput.focus();
                return;
            }

            btnBuscar.disabled = true;
            btnBuscar.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Consultando...';

            try {
                const response = await fetch(`comprar.php?buscar_cedula=${encodeURIComponent(cedula)}`);
                const data = await response.json();

                cedulaHidden.value = cedula;
                btnBuscar.style.display = 'none';
                btnReset.style.display = 'inline-flex';
                cedulaInput.readOnly = true;
                cedulaInput.classList.add('readonly-highlight');

                if (data.success && data.encontrado && data.cliente) {
                    // Cliente Existente
                    const c = data.cliente;
                    inputNombre.value = c.nombre || '';
                    inputEmail.value = c.email || '';
                    inputTelefono.value = c.telefono || '';
                    inputDireccion.value = c.direccion || '';

                    // Modo Readonly
                    setCamposReadonly(true);

                    bannerNew.style.display = 'none';
                    bannerFoundTxt.textContent = `¡Hola de nuevo, ${c.nombre}! Tus datos han sido cargados exitosamente.`;
                    bannerFound.style.display = 'flex';
                    mostrarToast('Cliente encontrado exitosamente.', 'success');
                } else {
                    // Cliente Nuevo
                    setCamposReadonly(false);
                    bannerFound.style.display = 'none';
                    bannerNew.style.display = 'flex';
                    mostrarToast('Cédula no registrada. Por favor ingrese sus datos.', 'info');
                    inputNombre.focus();
                }
            } catch (err) {
                console.error(err);
                mostrarToast('Error al consultar la cédula. Intente nuevamente.', 'danger');
            } finally {
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = '<ion-icon name="search-outline"></ion-icon> Buscar';
            }
        }

        function setCamposReadonly(readonly) {
            [inputNombre, inputEmail, inputTelefono, inputDireccion].forEach(input => {
                input.readOnly = readonly;
                if (readonly) {
                    input.classList.add('readonly-highlight');
                } else {
                    input.classList.remove('readonly-highlight');
                }
            });
        }

        function reiniciarBusqueda() {
            cedulaInput.readOnly = false;
            cedulaInput.classList.remove('readonly-highlight');
            cedulaInput.value = '';
            cedulaHidden.value = '';

            btnBuscar.style.display = 'inline-flex';
            btnReset.style.display = 'none';

            bannerFound.style.display = 'none';
            bannerNew.style.display = 'none';

            setCamposReadonly(false);
            inputNombre.value = '';
            inputEmail.value = '';
            inputTelefono.value = '';
            inputDireccion.value = '';

            cedulaInput.focus();
        }

        // Eventos para Búsqueda
        btnBuscar.addEventListener('click', buscarCedula);
        btnReset.addEventListener('click', reiniciarBusqueda);
        cedulaInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarCedula();
            }
        });

        // Cálculo Dinámico de Totales
        function actualizarTotal() {
            let cantidad = parseInt(qtyInput.value) || 1;
            if (cantidad < 1) cantidad = 1;
            if (cantidad > 10) cantidad = 10;
            qtyInput.value = cantidad;

            const total = (precioUnitario * cantidad);
            totalText.textContent = '$ ' + Math.round(total).toLocaleString('es-CO') + ' COP';
            totalHidden.value = total;
        }

        qtyMinus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) {
                qtyInput.value = val - 1;
                actualizarTotal();
            }
        });

        qtyPlus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val < 10) {
                qtyInput.value = val + 1;
                actualizarTotal();
            }
        });

        // Validación final antes de enviar
        formCompra.addEventListener('submit', (e) => {
            const cedula = cedulaHidden.value.trim() || cedulaInput.value.trim();
            if (!cedula) {
                e.preventDefault();
                mostrarToast('Por favor busque o ingrese su número de cédula.', 'warning');
                cedulaInput.focus();
                return;
            }
            cedulaHidden.value = cedula;

            if (!inputNombre.value.trim() || !inputEmail.value.trim()) {
                e.preventDefault();
                mostrarToast('Por favor complete los campos obligatorios.', 'warning');
                return;
            }
        });

        // Sistema de Toast Flotante
        function mostrarToast(mensaje, tipo = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast-message toast-${tipo}`;

            let icono = 'information-circle-outline';
            if (tipo === 'success') icono = 'checkmark-circle-outline';
            if (tipo === 'warning') icono = 'alert-circle-outline';
            if (tipo === 'danger') icono = 'close-circle-outline';

            toast.innerHTML = `
                <ion-icon name="${icono}"></ion-icon>
                <span>${mensaje}</span>
                <button type="button" onclick="this.parentElement.remove()" class="toast-close">&times;</button>
            `;

            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 400);
            }, 4500);
        }

        // Revisar parámetros de error en URL
        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const err = params.get('error');
            if (err === 'compra') {
                mostrarToast('Hubo un error al procesar su compra. Por favor intente nuevamente.', 'danger');
            } else if (err === 'incompleto') {
                mostrarToast('Por favor complete todos los datos requeridos.', 'warning');
            } else if (err === 'email') {
                mostrarToast('El correo electrónico no es válido.', 'warning');
            }
        });
    </script>
</body>
</html>
