<?php
/**
 * MYG - API de Checkout de Suscripción
 * Procesa planes con pasarela de pago simulada (sin integraciones externas)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Método no permitido. Se requiere POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$cedula    = trim($data['cedula'] ?? '');
$nombre    = trim($data['nombre'] ?? '');
$email     = trim($data['email'] ?? '');
$telefono  = trim($data['telefono'] ?? '');
$direccion = trim($data['direccion'] ?? '');
$items     = $data['items'] ?? [];
$total     = (float)($data['total'] ?? 0);

// Datos de pasarela simulada
$pago = $data['pago'] ?? [];
$titular      = trim($pago['titular'] ?? '');
$numeroTarjeta = preg_replace('/\D+/', '', (string)($pago['numero'] ?? ''));
$vencimiento  = trim($pago['vencimiento'] ?? '');
$cvv          = preg_replace('/\D+/', '', (string)($pago['cvv'] ?? ''));
$metodoPago   = trim($pago['metodo'] ?? 'tarjeta');

if (empty($cedula) || empty($nombre) || empty($email)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'La cédula, el nombre y el correo electrónico son campos obligatorios.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'El formato del correo electrónico no es válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_array($items) || empty($items)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Seleccione al menos un plan de suscripción.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($titular === '' || strlen($numeroTarjeta) < 13 || strlen($numeroTarjeta) > 19) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Complete los datos de la tarjeta (simulación). Use un número de 13 a 19 dígitos.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $vencimiento)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'El vencimiento debe tener formato MM/AA.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($cvv) < 3 || strlen($cvv) > 4) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'El CVV debe tener 3 o 4 dígitos.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Simulación: tarjetas que terminan en 0000 se rechazan (para probar el flujo de error)
$aprobado = !str_ends_with($numeroTarjeta, '0000');
$refPago = 'SIM-' . strtoupper(bin2hex(random_bytes(6)));

if (!$aprobado) {
    http_response_code(402);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Pago simulado rechazado. Pruebe con otra tarjeta (evite números que terminen en 0000).',
        'estado_pago' => 'rechazado',
        'ref_pago' => $refPago
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$resumenProductos = [];
$cantidadTotal = 0;
$calculoTotalEsperado = 0;

foreach ($items as $item) {
    $itemNombre = trim($item['name'] ?? $item['nombre'] ?? 'Plan');
    $itemCant   = max(1, (int)($item['quantity'] ?? $item['cantidad'] ?? 1));
    $itemPrecio = (float)($item['price'] ?? $item['precio'] ?? 0);

    $resumenProductos[] = "{$itemNombre} (x{$itemCant})";
    $cantidadTotal += $itemCant;
    $calculoTotalEsperado += ($itemPrecio * $itemCant);
}

$productoCadena = implode(', ', $resumenProductos);
if (mb_strlen($productoCadena) > 150) {
    $productoCadena = mb_substr($productoCadena, 0, 147) . '...';
}

if ($total <= 0) {
    $total = $calculoTotalEsperado;
}

$ultimos4 = substr($numeroTarjeta, -4);
$metodoLabel = $metodoPago === 'tarjeta' ? "Tarjeta ****{$ultimos4}" : $metodoPago;

try {
    $conn->beginTransaction();

    $stmtCliente = $conn->prepare("SELECT id FROM clientes WHERE cedula = :cedula LIMIT 1");
    $stmtCliente->execute([':cedula' => $cedula]);
    $clienteExistente = $stmtCliente->fetch();

    if ($clienteExistente) {
        $clienteId = (int)$clienteExistente['id'];
        $stmtUpdate = $conn->prepare("
            UPDATE clientes
            SET nombre = :nombre, telefono = :telefono, email = :email, direccion = :direccion
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':nombre'    => $nombre,
            ':telefono'  => $telefono,
            ':email'     => $email,
            ':direccion' => $direccion,
            ':id'        => $clienteId
        ]);
    } else {
        $stmtInsert = $conn->prepare("
            INSERT INTO clientes (nombre, cedula, telefono, email, direccion)
            VALUES (:nombre, :cedula, :telefono, :email, :direccion)
        ");
        $stmtInsert->execute([
            ':nombre'    => $nombre,
            ':cedula'    => $cedula,
            ':telefono'  => $telefono,
            ':email'     => $email,
            ':direccion' => $direccion
        ]);
        $clienteId = (int)$conn->lastInsertId();
    }

    $stmtPedido = $conn->prepare("
        INSERT INTO pedidos (cliente_id, nombre_cliente, email, producto, cantidad, total, metodo_pago, estado_pago, ref_pago)
        VALUES (:cliente_id, :nombre_cliente, :email, :producto, :cantidad, :total, :metodo_pago, :estado_pago, :ref_pago)
    ");
    $stmtPedido->execute([
        ':cliente_id'     => $clienteId,
        ':nombre_cliente' => $nombre,
        ':email'          => $email,
        ':producto'       => $productoCadena,
        ':cantidad'       => $cantidadTotal,
        ':total'          => $total,
        ':metodo_pago'    => $metodoLabel,
        ':estado_pago'    => 'aprobado',
        ':ref_pago'       => $refPago
    ]);
    $pedidoId = (int)$conn->lastInsertId();

    $conn->commit();

    http_response_code(200);
    echo json_encode([
        'success'   => true,
        'mensaje'   => "¡Suscripción #{$pedidoId} activada! Pago simulado aprobado (ref. {$refPago}).",
        'pedido_id' => $pedidoId,
        'estado_pago' => 'aprobado',
        'ref_pago' => $refPago,
        'cliente'   => [
            'id'     => $clienteId,
            'nombre' => $nombre,
            'email'  => $email
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Ocurrió un error al procesar la suscripción en el servidor.',
        'error'   => (bool)($_ENV['APP_DEBUG'] ?? false) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
