<?php
/**
 * MYG - API de Métricas y KPIs del Dashboard
 * Retorna contadores agregados, últimos pedidos y mensajes recientes
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';

try {
    // 1. KPI: Total Clientes
    $stmtClientes = $conn->query("SELECT COUNT(*) FROM clientes");
    $totalClientes = (int)$stmtClientes->fetchColumn();

    // 2. KPI: Total Pedidos
    $stmtPedidos = $conn->query("SELECT COUNT(*) FROM pedidos");
    $totalPedidos = (int)$stmtPedidos->fetchColumn();

    // 3. KPI: Total Ingresos en $
    $stmtIngresos = $conn->query("SELECT COALESCE(SUM(total), 0) FROM pedidos");
    $totalIngresos = (float)$stmtIngresos->fetchColumn();

    // 4. KPI: Consultas de Contacto
    $stmtMensajes = $conn->query("SELECT COUNT(*) FROM mensajes_contacto");
    $totalMensajes = (int)$stmtMensajes->fetchColumn();

    // 5. Últimos 5 Pedidos
    $stmtUltimosPedidos = $conn->query("
        SELECT id, nombre_cliente, producto, cantidad, total, fecha_pedido 
        FROM pedidos 
        ORDER BY id DESC 
        LIMIT 5
    ");
    $ultimosPedidos = $stmtUltimosPedidos->fetchAll();

    // 6. Últimos 5 Mensajes de Contacto
    $stmtUltimosMensajes = $conn->query("
        SELECT id, nombre, email, mensaje, fecha 
        FROM mensajes_contacto 
        ORDER BY id DESC 
        LIMIT 5
    ");
    $ultimosMensajes = $stmtUltimosMensajes->fetchAll();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'kpis' => [
            'total_clientes' => $totalClientes,
            'total_pedidos'  => $totalPedidos,
            'total_ingresos' => $totalIngresos,
            'total_mensajes' => $totalMensajes
        ],
        'ultimos_pedidos'  => $ultimosPedidos,
        'ultimos_mensajes' => $ultimosMensajes
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al recopilar métricas del dashboard.',
        'error'   => (bool)($_ENV['APP_DEBUG'] ?? false) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
