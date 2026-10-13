<?php
/**
 * MYG - Conexión PDO
 * MySQL/MariaDB (Docker / InfinityFree) o SQLite (Render / un solo servicio)
 */

require_once __DIR__ . '/env.php';

$driver   = strtolower((string)($_ENV['DB_DRIVER'] ?? 'mysql'));
$host     = $_ENV['DB_HOST'] ?? 'localhost';
$port     = $_ENV['DB_PORT'] ?? 3306;
$db       = $_ENV['DB_NAME'] ?? 'myg_db';
$user     = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$debug    = (bool)($_ENV['APP_DEBUG'] ?? false);

if ($password === null || $password === false) {
    $password = '';
}

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

/**
 * Crea tablas e inserta admin si la SQLite está vacía.
 * Usuario: admin | Contraseña: admin123
 */
function inicializarSqliteSiVacio(PDO $conn): void
{
    $existe = (int)$conn->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetchColumn();
    if ($existe > 0) {
        return;
    }

    $conn->exec("
        CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            usuario TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            rol TEXT DEFAULT 'admin',
            fecha_registro TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE mensajes_contacto (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            email TEXT NOT NULL,
            mensaje TEXT NOT NULL,
            fecha TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE clientes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            cedula TEXT NOT NULL UNIQUE,
            telefono TEXT NULL,
            email TEXT NULL,
            direccion TEXT NULL,
            fecha_registro TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE pedidos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cliente_id INTEGER NULL,
            nombre_cliente TEXT NOT NULL,
            email TEXT NOT NULL,
            producto TEXT NOT NULL,
            cantidad INTEGER NOT NULL,
            total REAL NOT NULL,
            metodo_pago TEXT NULL,
            estado_pago TEXT DEFAULT 'aprobado',
            ref_pago TEXT NULL,
            fecha_pedido TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL ON UPDATE CASCADE
        );
    ");

    $hash = '$2y$10$FcRT1f6cYPZd5XJiiiD21O0YeR8JgUtSyfLBWq8GDKZwG3c2ahcJm';
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (:n, :u, :p, :r)");
    $stmt->execute([
        ':n' => 'Administrador MYG',
        ':u' => 'admin',
        ':p' => $hash,
        ':r' => 'admin',
    ]);
}

try {
    if ($driver === 'sqlite') {
        $sqlitePath = $_ENV['DB_SQLITE_PATH'] ?? (__DIR__ . '/data/myg.sqlite');
        $dir = dirname($sqlitePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new PDOException("No se pudo crear el directorio de datos SQLite: {$dir}");
        }

        $conn = new PDO('sqlite:' . $sqlitePath, null, null, $opciones);
        $conn->exec('PRAGMA foreign_keys = ON');
        inicializarSqliteSiVacio($conn);
    } else {
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $conn = new PDO($dsn, $user, (string)$password, $opciones);
    }
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
