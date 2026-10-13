<?php
/**
 * MYG - Panel Administrativo & Dashboard Integral
 * Dashboard con KPIs, Sidebar Adaptativo (>1024px fijo / ≤1024px off-canvas),
 * Directorio CRUD con .table-scroll-container, Modales AJAX y Live Search con Debounce (250ms)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';

$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Administrador';
$usuarioRol    = $_SESSION['usuario_rol'] ?? 'admin';

// Cargar métricas iniciales del servidor
try {
    $totalClientes = (int)$conn->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $totalPedidos  = (int)$conn->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
    $totalIngresos = (float)$conn->query("SELECT COALESCE(SUM(total), 0) FROM pedidos")->fetchColumn();
    $totalMensajes = (int)$conn->query("SELECT COUNT(*) FROM mensajes_contacto")->fetchColumn();

    // Últimos 5 pedidos
    $stmtPedidos = $conn->query("SELECT id, nombre_cliente, producto, total, fecha_pedido FROM pedidos ORDER BY id DESC LIMIT 5");
    $ultimosPedidos = $stmtPedidos->fetchAll();

    // Últimos 5 mensajes
    $stmtMensajes = $conn->query("SELECT id, nombre, email, mensaje, fecha FROM mensajes_contacto ORDER BY id DESC LIMIT 5");
    $ultimosMensajes = $stmtMensajes->fetchAll();
} catch (PDOException $e) {
    $totalClientes = $totalPedidos = $totalIngresos = $totalMensajes = 0;
    $ultimosPedidos = $ultimosMensajes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - MYG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="admin.css?v=<?= time() ?>">
</head>
<body>

    <div class="app-container">

        <!-- ==============================================================================
             1. SIDEBAR (> 1024px FIJO / ≤ 1024px OFF-CANVAS TÁCTIL)
             ============================================================================== -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <div class="brand-badge">
                        <ion-icon name="game-controller"></ion-icon>
                    </div>
                    <span>MYG Admin</span>
                </div>
                <!-- Botón de cierre para móviles -->
                <button type="button" id="btn-close-sidebar" class="btn-toggle-sidebar" aria-label="Cerrar menú">
                    <ion-icon name="close-outline"></ion-icon>
                </button>
            </div>

            <div class="sidebar-user">
                <div class="user-avatar">
                    <ion-icon name="person-outline"></ion-icon>
                </div>
                <div class="user-meta">
                    <h4><?= htmlspecialchars($usuarioNombre) ?></h4>
                    <span><?= htmlspecialchars($usuarioRol) ?></span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="#kpis" class="nav-item active">
                    <ion-icon name="grid-outline"></ion-icon>
                    <span>Dashboard & KPIs</span>
                </a>
                <a href="#clientes" class="nav-item">
                    <ion-icon name="people-outline"></ion-icon>
                    <span>Directorio Clientes</span>
                </a>
                <a href="#pedidos-recientes" class="nav-item">
                    <ion-icon name="cart-outline"></ion-icon>
                    <span>Últimos Pedidos</span>
                </a>
                <a href="#mensajes-recientes" class="nav-item">
                    <ion-icon name="chatbubbles-outline"></ion-icon>
                    <span>Consultas Web</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="btn-sidebar-logout">
                    <ion-icon name="log-out-outline"></ion-icon>
                    Cerrar Sesión
                </a>
            </div>
        </aside>

        <!-- Overlay oscuro táctil para móviles -->
        <div id="sidebar-overlay" class="sidebar-overlay"></div>

        <!-- ==============================================================================
             2. CONTENIDO PRINCIPAL & TOPBAR
             ============================================================================== -->
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" id="btn-open-sidebar" class="btn-toggle-sidebar" aria-label="Abrir menú lateral">
                        <ion-icon name="menu-outline"></ion-icon>
                    </button>
                    <span class="topbar-title">Centro de Control MYG</span>
                </div>

                <div class="topbar-actions">
                    <a href="../index.html" class="btn-store-link" target="_blank">
                        <ion-icon name="storefront-outline"></ion-icon>
                        <span>Ver Tienda</span>
                    </a>
                </div>
            </header>

            <main class="content-area">

                <!-- ==============================================================================
                     3. TARJETAS DE MÉTRICAS (KPIS)
                     ============================================================================== -->
                <section id="kpis" class="kpi-grid">
                    <!-- KPI 1: Clientes -->
                    <div class="kpi-card kpi-blue">
                        <div class="kpi-icon">
                            <ion-icon name="people"></ion-icon>
                        </div>
                        <div class="kpi-info">
                            <h3>Total Clientes</h3>
                            <div class="kpi-value" id="kpi-clientes"><?= number_format($totalClientes) ?></div>
                        </div>
                    </div>

                    <!-- KPI 2: Pedidos -->
                    <div class="kpi-card kpi-purple">
                        <div class="kpi-icon">
                            <ion-icon name="bag-check"></ion-icon>
                        </div>
                        <div class="kpi-info">
                            <h3>Total Pedidos</h3>
                            <div class="kpi-value" id="kpi-pedidos"><?= number_format($totalPedidos) ?></div>
                        </div>
                    </div>

                    <!-- KPI 3: Ingresos -->
                    <div class="kpi-card kpi-green">
                        <div class="kpi-icon">
                            <ion-icon name="cash"></ion-icon>
                        </div>
                        <div class="kpi-info">
                            <h3>Total Ingresos</h3>
                            <div class="kpi-value" id="kpi-ingresos">$ <?= number_format($totalIngresos, 0, ',', '.') ?> COP</div>
                        </div>
                    </div>

                    <!-- KPI 4: Contactos -->
                    <div class="kpi-card kpi-amber">
                        <div class="kpi-icon">
                            <ion-icon name="mail-unread"></ion-icon>
                        </div>
                        <div class="kpi-info">
                            <h3>Consultas Contacto</h3>
                            <div class="kpi-value" id="kpi-mensajes"><?= number_format($totalMensajes) ?></div>
                        </div>
                    </div>
                </section>

                <!-- ==============================================================================
                     4. DIRECTORIO CRUD DE CLIENTES CON LIVE SEARCH (DEBOUNCE 250MS)
                     ============================================================================== -->
                <section id="clientes" class="section-crud">
                    <div class="crud-header">
                        <div class="crud-title">
                            <h2>Directorio de Clientes</h2>
                            <p>Gestión de base de datos con búsqueda instantánea y paginación.</p>
                        </div>
                        <button type="button" id="btn-nuevo-cliente" class="btn btn-primary">
                            <ion-icon name="person-add-outline"></ion-icon>
                            Nuevo Cliente
                        </button>
                    </div>

                    <!-- Barra de Búsqueda Reactiva -->
                    <div class="crud-toolbar">
                        <div class="search-box-wrap">
                            <ion-icon name="search-outline" class="search-icon"></ion-icon>
                            <input type="text" id="input-search-clientes" class="search-input" placeholder="Buscar por nombre, cédula o email... (Esc para limpiar)" autocomplete="off">
                            <span id="debounce-spinner" class="debounce-spinner"></span>
                            <button type="button" id="btn-clear-search" class="btn-clear-search" title="Limpiar búsqueda">&times;</button>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">
                            Registros: <strong id="lbl-total-registros" style="color: var(--cyan);"><?= $totalClientes ?></strong>
                        </div>
                    </div>

                    <!-- Contenedor con Scroll Táctil Obligatorio -->
                    <div class="table-scroll-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cédula</th>
                                    <th>Nombre Completo</th>
                                    <th>Teléfono</th>
                                    <th>Correo Electrónico</th>
                                    <th>Dirección</th>
                                    <th style="text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-clientes">
                                <!-- Las filas se pueblan dinámicamente vía AJAX -->
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        Cargando directorio de clientes...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Dinámica (5 por página) -->
                    <div class="pagination">
                        <div id="lbl-info-paginacion" style="color: var(--text-muted); font-size: 0.88rem;">
                            Página 1 de 1
                        </div>
                        <div id="pagination-buttons" class="page-buttons"></div>
                    </div>
                </section>

                <!-- ==============================================================================
                     5. TABLAS COMPACTAS: ÚLTIMOS PEDIDOS Y MENSAJES RECIENTES
                     ============================================================================== -->
                <div class="dashboard-tables-grid">
                    <!-- Últimos Pedidos -->
                    <div id="pedidos-recientes" class="card-box">
                        <div class="card-box-header">
                            <h2>
                                <ion-icon name="bag-handle-outline"></ion-icon>
                                Últimos Pedidos
                            </h2>
                            <span class="badge-top5 cyan">Top 5 Recientes</span>
                        </div>
                        <div class="table-scroll-container" style="margin-bottom: 0;">
                            <table class="admin-table compact-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th>Producto</th>
                                        <th>Total</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ultimosPedidos)): ?>
                                        <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">Sin pedidos aún</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($ultimosPedidos as $p): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($p['id']) ?></td>
                                                <td style="font-weight: 600; color: #fff;"><?= htmlspecialchars($p['nombre_cliente']) ?></td>
                                                <td style="max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($p['producto']) ?></td>
                                                <td style="color: var(--cyan); font-weight: 700;">$ <?= number_format((float)$p['total'], 0, ',', '.') ?> COP</td>
                                                <td style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars(substr($p['fecha_pedido'], 0, 10)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mensajes de Contacto Recientes -->
                    <div id="mensajes-recientes" class="card-box">
                        <div class="card-box-header">
                            <h2>
                                <ion-icon name="chatbubble-ellipses-outline"></ion-icon>
                                Mensajes de Contacto
                            </h2>
                            <span class="badge-top5 amber">Top 5 Recientes</span>
                        </div>
                        <div class="table-scroll-container" style="margin-bottom: 0;">
                            <table class="admin-table compact-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Remitente</th>
                                        <th>Correo</th>
                                        <th>Mensaje</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ultimosMensajes)): ?>
                                        <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">Sin mensajes aún</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($ultimosMensajes as $m): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($m['id']) ?></td>
                                                <td style="font-weight: 600; color: #fff;"><?= htmlspecialchars($m['nombre']) ?></td>
                                                <td style="font-size: 0.85rem;"><?= htmlspecialchars($m['email']) ?></td>
                                                <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($m['mensaje']) ?>">
                                                    <?= htmlspecialchars($m['mensaje']) ?>
                                                </td>
                                                <td style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars(substr($m['fecha'], 0, 10)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ==============================================================================
         6. MODAL: NUEVO CLIENTE (CON VERIFICACIÓN DE CÉDULA ÚNICA EN TIEMPO REAL)
         ============================================================================== -->
    <div id="modal-nuevo-cliente" class="modal-admin-overlay">
        <div class="modal-admin-box">
            <div class="modal-admin-header">
                <h3 class="modal-admin-title">Registrar Nuevo Cliente</h3>
                <button type="button" class="modal-admin-close" data-close-modal>&times;</button>
            </div>

            <form id="form-nuevo-cliente">
                <div class="form-group">
                    <label for="nuevo_cedula">Número de Cédula *</label>
                    <input type="text" id="nuevo_cedula" name="cedula" class="form-control" placeholder="Ej: 1098765432" required autocomplete="off">
                    <div id="feedback-cedula-nuevo" class="cedula-feedback"></div>
                </div>

                <div class="form-group">
                    <label for="nuevo_nombre">Nombre Completo *</label>
                    <input type="text" id="nuevo_nombre" name="nombre" class="form-control" placeholder="Nombre y apellido" required>
                </div>

                <div class="form-group">
                    <label for="nuevo_email">Correo Electrónico</label>
                    <input type="email" id="nuevo_email" name="email" class="form-control" placeholder="cliente@correo.com">
                </div>

                <div class="form-group">
                    <label for="nuevo_telefono">Teléfono / WhatsApp</label>
                    <input type="tel" id="nuevo_telefono" name="telefono" class="form-control" placeholder="+57 300 123 4567">
                </div>

                <div class="form-group">
                    <label for="nuevo_direccion">Dirección de Entrega</label>
                    <input type="text" id="nuevo_direccion" name="direccion" class="form-control" placeholder="Calle, Carrera o Edificio">
                </div>

                <div class="modal-admin-footer">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                    <button type="submit" id="btn-submit-nuevo" class="btn btn-primary">
                        <ion-icon name="save-outline"></ion-icon>
                        Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==============================================================================
         7. MODAL: EDITAR CLIENTE (CON VERIFICACIÓN EXCLUYENDO ID ACTUAL)
         ============================================================================== -->
    <div id="modal-editar-cliente" class="modal-admin-overlay">
        <div class="modal-admin-box">
            <div class="modal-admin-header">
                <h3 class="modal-admin-title">Editar Cliente</h3>
                <button type="button" class="modal-admin-close" data-close-modal>&times;</button>
            </div>

            <form id="form-editar-cliente">
                <input type="hidden" id="edit_id" name="id">

                <div class="form-group">
                    <label for="edit_cedula">Número de Cédula *</label>
                    <input type="text" id="edit_cedula" name="cedula" class="form-control" required autocomplete="off">
                    <div id="feedback-cedula-edit" class="cedula-feedback"></div>
                </div>

                <div class="form-group">
                    <label for="edit_nombre">Nombre Completo *</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_email">Correo Electrónico</label>
                    <input type="email" id="edit_email" name="email" class="form-control">
                </div>

                <div class="form-group">
                    <label for="edit_telefono">Teléfono / WhatsApp</label>
                    <input type="tel" id="edit_telefono" name="telefono" class="form-control">
                </div>

                <div class="form-group">
                    <label for="edit_direccion">Dirección de Entrega</label>
                    <input type="text" id="edit_direccion" name="direccion" class="form-control">
                </div>

                <div class="modal-admin-footer">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                    <button type="submit" id="btn-submit-edit" class="btn btn-primary">
                        <ion-icon name="checkmark-done-outline"></ion-icon>
                        Actualizar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==============================================================================
         8. SCRIPT JS: LOGICA DEL DASHBOARD, CRUD Y EVENT DELEGATION
         ============================================================================== -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Elementos DOM
            const sidebar         = document.getElementById('sidebar');
            const sidebarOverlay  = document.getElementById('sidebar-overlay');
            const btnOpenSidebar  = document.getElementById('btn-open-sidebar');
            const btnCloseSidebar = document.getElementById('btn-close-sidebar');

            const tbodyClientes   = document.getElementById('tbody-clientes');
            const searchInput     = document.getElementById('input-search-clientes');
            const btnClearSearch  = document.getElementById('btn-clear-search');
            const debounceSpinner = document.getElementById('debounce-spinner');
            const lblTotalReg     = document.getElementById('lbl-total-registros');
            const lblInfoPag      = document.getElementById('lbl-info-paginacion');
            const paginationBtns  = document.getElementById('pagination-buttons');

            // Modales
            const modalNuevo      = document.getElementById('modal-nuevo-cliente');
            const modalEditar     = document.getElementById('modal-editar-cliente');
            const btnOpenNuevo    = document.getElementById('btn-nuevo-cliente');
            const formNuevo       = document.getElementById('form-nuevo-cliente');
            const formEditar      = document.getElementById('form-editar-cliente');
            const closeButtons    = document.querySelectorAll('[data-close-modal]');

            // Feedbacks de Cédula
            const inputNuevoCed   = document.getElementById('nuevo_cedula');
            const feedbackNuevo   = document.getElementById('feedback-cedula-nuevo');
            const btnSubmitNuevo  = document.getElementById('btn-submit-nuevo');

            const inputEditCed    = document.getElementById('edit_cedula');
            const inputEditId     = document.getElementById('edit_id');
            const feedbackEdit    = document.getElementById('feedback-cedula-edit');
            const btnSubmitEdit   = document.getElementById('btn-submit-edit');

            let paginaActual = 1;
            let debounceTimer = null;
            let buscandoModo = false;

            // ==============================================================================
            // A. SIDEBAR OFF-CANVAS RESPONSIVO
            // ==============================================================================
            function openSidebar() {
                sidebar.classList.add('open');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            btnOpenSidebar?.addEventListener('click', openSidebar);
            btnCloseSidebar?.addEventListener('click', closeSidebar);
            sidebarOverlay?.addEventListener('click', closeSidebar);

            // Cerrar sidebar al hacer clic en un enlace de navegación en móviles/tablets
            document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
                item.addEventListener('click', () => {
                    if (window.innerWidth <= 1024) {
                        closeSidebar();
                    }
                });
            });

            // Cerrar con la tecla Escape si está abierto
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                    closeSidebar();
                }
            });

            // ==============================================================================
            // B. CARGA DE CLIENTES CON PAGINACIÓN DINÁMICA (5 POR PÁGINA)
            // ==============================================================================
            async function cargarClientes(pagina = 1) {
                paginaActual = pagina;
                buscandoModo = false;

                try {
                    const res = await fetch(`api_clientes.php?action=list&page=${pagina}`);
                    if (!res.ok) throw new Error('Error al consultar lista');
                    const data = await res.json();

                    if (data.success) {
                        renderizarTabla(data.clientes);
                        lblTotalReg.textContent = data.total_registros;
                        renderizarPaginacion(data.total_paginas, data.pagina_actual);
                    }
                } catch (err) {
                    console.error(err);
                    tbodyClientes.innerHTML = `<tr><td colspan="7" style="text-align:center; color: var(--rose); padding: 2rem;">Error al cargar datos.</td></tr>`;
                }
            }

            function renderizarTabla(clientes) {
                if (!clientes || clientes.length === 0) {
                    tbodyClientes.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                                <ion-icon name="people-outline" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></ion-icon>
                                <div>No se encontraron registros de clientes.</div>
                            </td>
                        </tr>
                    `;
                    return;
                }

                tbodyClientes.innerHTML = clientes.map(c => `
                    <tr id="row-cliente-${c.id}" data-id="${c.id}">
                        <td><span style="color: var(--text-muted);">#${c.id}</span></td>
                        <td><strong style="color: var(--cyan);">${escapeHtml(c.cedula)}</strong></td>
                        <td style="font-weight: 600; color: #fff;">${escapeHtml(c.nombre)}</td>
                        <td>${escapeHtml(c.telefono || 'N/A')}</td>
                        <td>${escapeHtml(c.email || 'N/A')}</td>
                        <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(c.direccion || '')}">
                            ${escapeHtml(c.direccion || 'N/A')}
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button type="button" class="btn-action-edit" data-id="${c.id}" title="Editar">
                                <ion-icon name="create-outline"></ion-icon>
                                Editar
                            </button>
                            <button type="button" class="btn-action-delete" data-id="${c.id}" data-name="${escapeHtml(c.nombre)}" title="Eliminar">
                                <ion-icon name="trash-outline"></ion-icon>
                                Eliminar
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            function renderizarPaginacion(totalPaginas, actual) {
                lblInfoPag.textContent = `Página ${actual} de ${totalPaginas}`;
                paginationBtns.innerHTML = '';

                if (totalPaginas <= 1) return;

                // Botón Anterior
                const btnPrev = document.createElement('button');
                btnPrev.className = `page-btn ${actual <= 1 ? 'disabled' : ''}`;
                btnPrev.innerHTML = '<ion-icon name="chevron-back-outline"></ion-icon>';
                btnPrev.onclick = () => { if (actual > 1) cargarClientes(actual - 1); };
                paginationBtns.appendChild(btnPrev);

                // Botones numéricos
                for (let i = 1; i <= totalPaginas; i++) {
                    const btn = document.createElement('button');
                    btn.className = `page-btn ${i === actual ? 'active' : ''}`;
                    btn.textContent = i;
                    btn.onclick = () => cargarClientes(i);
                    paginationBtns.appendChild(btn);
                }

                // Botón Siguiente
                const btnNext = document.createElement('button');
                btnNext.className = `page-btn ${actual >= totalPaginas ? 'disabled' : ''}`;
                btnNext.innerHTML = '<ion-icon name="chevron-forward-outline"></ion-icon>';
                btnNext.onclick = () => { if (actual < totalPaginas) cargarClientes(actual + 1); };
                paginationBtns.appendChild(btnNext);
            }

            // ==============================================================================
            // C. LIVE SEARCH CON DEBOUNCE (250MS) & SOPORTE TECLA ESCAPE
            // ==============================================================================
            searchInput.addEventListener('input', () => {
                const query = searchInput.value.trim();
                btnClearSearch.style.display = query.length > 0 ? 'block' : 'none';

                clearTimeout(debounceTimer);

                if (!query) {
                    debounceSpinner.style.display = 'none';
                    cargarClientes(1);
                    return;
                }

                debounceSpinner.style.display = 'block';

                debounceTimer = setTimeout(async () => {
                    buscandoModo = true;
                    try {
                        const res = await fetch(`api_clientes.php?action=search&q=${encodeURIComponent(query)}`);
                        const data = await res.json();
                        if (data.success) {
                            renderizarTabla(data.clientes);
                            lblTotalReg.textContent = data.total_registros;
                            lblInfoPag.textContent = `Resultados para "${query}" (${data.total_registros})`;
                            paginationBtns.innerHTML = '';
                        }
                    } catch (err) {
                        console.error(err);
                    } finally {
                        debounceSpinner.style.display = 'none';
                    }
                }, 250); // Debounce de 250 milisegundos
            });

            // Limpieza con botón 'X'
            btnClearSearch.addEventListener('click', () => {
                searchInput.value = '';
                btnClearSearch.style.display = 'none';
                debounceSpinner.style.display = 'none';
                cargarClientes(1);
                searchInput.focus();
            });

            // Limpieza con tecla Escape
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && document.activeElement === searchInput) {
                    searchInput.value = '';
                    btnClearSearch.style.display = 'none';
                    debounceSpinner.style.display = 'none';
                    cargarClientes(1);
                }
            });

            // ==============================================================================
            // D. DELEGACIÓN DE EVENTOS (e.target.closest) PARA EDITAR Y ELIMINAR
            // ==============================================================================
            tbodyClientes.addEventListener('click', async (e) => {
                // Editar Cliente
                const btnEdit = e.target.closest('.btn-action-edit');
                if (btnEdit) {
                    const id = btnEdit.getAttribute('data-id');
                    abrirModalEditar(id);
                    return;
                }

                // Eliminar Cliente
                const btnDel = e.target.closest('.btn-action-delete');
                if (btnDel) {
                    const id = btnDel.getAttribute('data-id');
                    const nombre = btnDel.getAttribute('data-name');
                    eliminarCliente(id, nombre);
                    return;
                }
            });

            // ==============================================================================
            // E. MODALES Y VALIDACIÓN DE CÉDULA ÚNICA EN TIEMPO REAL
            // ==============================================================================
            function openModal(modal) {
                modal.classList.add('active');
            }

            function closeModal(modal) {
                modal.classList.remove('active');
            }

            closeButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    closeModal(modalNuevo);
                    closeModal(modalEditar);
                });
            });

            [modalNuevo, modalEditar].forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(modal);
                });
            });

            btnOpenNuevo.addEventListener('click', () => {
                formNuevo.reset();
                feedbackNuevo.className = 'cedula-feedback';
                feedbackNuevo.textContent = '';
                btnSubmitNuevo.disabled = false;
                openModal(modalNuevo);
                inputNuevoCed.focus();
            });

            // Validación en tiempo real de cédula para Nuevo Cliente
            let cedulaNuevoTimer = null;
            inputNuevoCed.addEventListener('input', () => {
                const cedula = inputNuevoCed.value.trim();
                clearTimeout(cedulaNuevoTimer);

                if (!cedula) {
                    feedbackNuevo.className = 'cedula-feedback';
                    feedbackNuevo.textContent = '';
                    btnSubmitNuevo.disabled = false;
                    return;
                }

                cedulaNuevoTimer = setTimeout(async () => {
                    try {
                        const res = await fetch(`api_clientes.php?action=check_cedula&cedula=${encodeURIComponent(cedula)}`);
                        const data = await res.json();
                        if (data.exists) {
                            feedbackNuevo.className = 'cedula-feedback error';
                            feedbackNuevo.textContent = '⚠️ Esta cédula ya está registrada en el sistema.';
                            btnSubmitNuevo.disabled = true;
                        } else {
                            feedbackNuevo.className = 'cedula-feedback valid';
                            feedbackNuevo.textContent = '✓ Cédula disponible.';
                            btnSubmitNuevo.disabled = false;
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }, 250);
            });

            // Guardar Nuevo Cliente vía AJAX
            formNuevo.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formNuevo);
                formData.append('action', 'create');

                btnSubmitNuevo.disabled = true;

                try {
                    const res = await fetch('api_clientes.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        closeModal(modalNuevo);
                        formNuevo.reset();
                        cargarClientes(1);
                        actualizarKpis();
                        alert('¡Cliente creado exitosamente!');
                    } else {
                        alert(data.mensaje || 'Error al guardar el cliente.');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Error de conexión al registrar cliente.');
                } finally {
                    btnSubmitNuevo.disabled = false;
                }
            });

            // Abrir y cargar datos en Modal Editar
            async function abrirModalEditar(id) {
                formEditar.reset();
                feedbackEdit.className = 'cedula-feedback';
                feedbackEdit.textContent = '';
                btnSubmitEdit.disabled = false;

                try {
                    const res = await fetch(`api_clientes.php?action=get&id=${id}`);
                    const data = await res.json();

                    if (data.success && data.cliente) {
                        const c = data.cliente;
                        inputEditId.value = c.id;
                        inputEditCed.value = c.cedula;
                        document.getElementById('edit_nombre').value = c.nombre;
                        document.getElementById('edit_email').value = c.email || '';
                        document.getElementById('edit_telefono').value = c.telefono || '';
                        document.getElementById('edit_direccion').value = c.direccion || '';

                        openModal(modalEditar);
                        inputEditCed.focus();
                    }
                } catch (err) {
                    console.error(err);
                    alert('No se pudo cargar la información del cliente.');
                }
            }

            // Validación en tiempo real para Modal Editar excluyendo ID actual
            let cedulaEditTimer = null;
            inputEditCed.addEventListener('input', () => {
                const cedula = inputEditCed.value.trim();
                const id = inputEditId.value;
                clearTimeout(cedulaEditTimer);

                if (!cedula) {
                    feedbackEdit.className = 'cedula-feedback';
                    feedbackEdit.textContent = '';
                    btnSubmitEdit.disabled = false;
                    return;
                }

                cedulaEditTimer = setTimeout(async () => {
                    try {
                        const res = await fetch(`api_clientes.php?action=check_cedula&cedula=${encodeURIComponent(cedula)}&exclude_id=${id}`);
                        const data = await res.json();
                        if (data.exists) {
                            feedbackEdit.className = 'cedula-feedback error';
                            feedbackEdit.textContent = '⚠️ Esta cédula ya pertenece a otro cliente.';
                            btnSubmitEdit.disabled = true;
                        } else {
                            feedbackEdit.className = 'cedula-feedback valid';
                            feedbackEdit.textContent = '✓ Cédula disponible.';
                            btnSubmitEdit.disabled = false;
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }, 250);
            });

            // Actualizar Cliente vía AJAX
            formEditar.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formEditar);
                formData.append('action', 'update');

                btnSubmitEdit.disabled = true;

                try {
                    const res = await fetch('api_clientes.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        closeModal(modalEditar);
                        cargarClientes(paginaActual);
                        alert('¡Cliente actualizado correctamente!');
                    } else {
                        alert(data.mensaje || 'Error al actualizar el cliente.');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Error de conexión al actualizar cliente.');
                } finally {
                    btnSubmitEdit.disabled = false;
                }
            });

            // ==============================================================================
            // F. ELIMINACIÓN CON CONFIRMACIÓN Y REMOCIÓN ANIMADA EN EL DOM
            // ==============================================================================
            async function eliminarCliente(id, nombre) {
                if (!confirm(`¿Desea eliminar permanentemente al cliente "${nombre}"?\nEsta acción no se puede deshacer.`)) {
                    return;
                }

                const fila = document.getElementById(`row-cliente-${id}`);

                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    const res = await fetch('api_clientes.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        if (fila) {
                            // Animación de salida en el DOM
                            fila.classList.add('row-removing');
                            setTimeout(() => {
                                fila.remove();
                                // Si la página quedó vacía, recargar
                                if (tbodyClientes.children.length === 0) {
                                    cargarClientes(Math.max(1, paginaActual - 1));
                                }
                            }, 320);
                        }
                        actualizarKpis();
                    } else {
                        alert(data.mensaje || 'No se pudo eliminar el cliente.');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Error de conexión al intentar eliminar.');
                }
            }

            // Actualizar métricas KPIs en tiempo real
            async function actualizarKpis() {
                try {
                    const res = await fetch('api_dashboard.php');
                    const data = await res.json();
                    if (data.success && data.kpis) {
                        document.getElementById('kpi-clientes').textContent = Number(data.kpis.total_clientes).toLocaleString();
                        document.getElementById('kpi-pedidos').textContent = Number(data.kpis.total_pedidos).toLocaleString();
                        document.getElementById('kpi-ingresos').textContent = '$ ' + Math.round(Number(data.kpis.total_ingresos)).toLocaleString('es-CO') + ' COP';
                        document.getElementById('kpi-mensajes').textContent = Number(data.kpis.total_mensajes).toLocaleString();
                        lblTotalReg.textContent = data.kpis.total_clientes;
                    }
                } catch (err) {
                    console.error(err);
                }
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Inicializar carga de clientes
            cargarClientes(1);
        });
    </script>
</body>
</html>
