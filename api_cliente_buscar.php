<?php
/**
 * MYG - API de Búsqueda de Cliente por Cédula
 * Retorna información del cliente en formato JSON para autocompletar el checkout
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

$cedula = trim($_GET['cedula'] ?? $_POST['cedula'] ?? '');

if (empty($cedula)) {
    http_response_code(400);
    echo json_encode([
        'success'    => false,
        'mensaje'    => 'El número de cédula es requerido.',
        'encontrado' => false
    ], JSON_UNESCAPED_UNICODE);
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
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success'    => true,
            'encontrado' => false,
            'mensaje'    => 'Cliente no encontrado. Por favor complete los datos para su registro.'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success'    => false,
        'mensaje'    => 'Error al consultar el cliente en la base de datos.',
        'error'      => (bool)($_ENV['APP_DEBUG'] ?? false) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
