<?php
/**
 * MYG - API de Contacto Asíncrono
 * Procesa mensajes del formulario web mediante Fetch/AJAX y sentencias preparadas PDO
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

$nombre  = trim($data['nombre'] ?? '');
$email   = trim($data['email'] ?? '');
$mensaje = trim($data['mensaje'] ?? '');

if (empty($nombre) || empty($email) || empty($mensaje)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Por favor complete todos los campos obligatorios.'
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

try {
    $stmt = $conn->prepare("INSERT INTO mensajes_contacto (nombre, email, mensaje) VALUES (:nombre, :email, :mensaje)");
    $stmt->execute([
        ':nombre'  => $nombre,
        ':email'   => $email,
        ':mensaje' => $mensaje,
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'mensaje' => '¡Tu mensaje ha sido enviado exitosamente! Nos pondremos en contacto contigo lo más pronto posible.'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error en el servidor al guardar el mensaje.',
        'error'   => (bool)($_ENV['APP_DEBUG'] ?? false) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
