<?php
/**
 * MYG - API de Inicio de Sesión Administrativa (JSON / Fetch)
 * Valida credenciales contra la base de datos e inicia sesión segura
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

$sessionName = $_ENV['SESSION_NAME'] ?? 'MYG_session';

if (session_status() === PHP_SESSION_NONE) {
    session_name($sessionName);
    session_start();
}

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

$usuario  = trim($data['usuario'] ?? '');
$password = $data['password'] ?? '';

if (empty($usuario) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Por favor ingrese su usuario y contraseña.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, nombre, usuario, password, rol FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->execute([':usuario' => $usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Regenerar id de sesión para prevenir Session Fixation
        session_regenerate_id(true);

        $_SESSION['usuario_id']     = (int)$user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['usuario_user']   = $user['usuario'];
        $_SESSION['usuario_rol']    = $user['rol'];

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'mensaje' => '¡Bienvenido al panel de MYG!',
            'usuario' => [
                'id'     => $user['id'],
                'nombre' => $user['nombre'],
                'rol'    => $user['rol']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Credenciales inválidas. Verifique su usuario y contraseña.'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error en el servidor al autenticar.',
        'error'   => (bool)($_ENV['APP_DEBUG'] ?? false) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
