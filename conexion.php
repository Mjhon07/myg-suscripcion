<?php
/**
 * MYG - Conexión Segura a Base de Datos con PDO
 */

require_once __DIR__ . '/env.php';

$host     = $_ENV['DB_HOST'] ?? 'localhost';
$port     = $_ENV['DB_PORT'] ?? 3306;
$db       = $_ENV['DB_NAME'] ?? 'myg_db';
$user     = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$debug    = (bool)($_ENV['APP_DEBUG'] ?? false);

// Contraseña vacía en .env puede llegar como null o false
if ($password === null || $password === false) {
    $password = '';
}

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, (string)$password, $opciones);
} catch (PDOException $e) {
    $wantsJson = (
        (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
        (isset($_SERVER['CONTENT_TYPE']) && str_contains((string)$_SERVER['CONTENT_TYPE'], 'application/json')) ||
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'api_')
    );

    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensaje' => 'No se pudo conectar con la base de datos. Verifique la configuración en .env',
            'error'   => $debug ? $e->getMessage() : null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($debug) {
        die("Error de conexión a la base de datos: " . $e->getMessage());
    }

    error_log("Error de conexión PDO: " . $e->getMessage());
    die("Lo sentimos, ocurrió un error interno al conectar con los servicios de datos. Por favor, intente más tarde.");
}
