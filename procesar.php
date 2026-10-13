<?php
/**
 * MYG - Procesamiento de Formulario de Contacto
 * Validación y guardado seguro en la base de datos con PDO
 */

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$nombre  = trim($_POST['nombre'] ?? '');
$email   = trim($_POST['email'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

// Validación de campos obligatorios y formato de email
if (empty($nombre) || empty($email) || empty($mensaje) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?error=contacto#contacto');
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO mensajes_contacto (nombre, email, mensaje) VALUES (:nombre, :email, :mensaje)");
    $stmt->execute([
        ':nombre'  => $nombre,
        ':email'   => $email,
        ':mensaje' => $mensaje,
    ]);

    header('Location: index.html?success=ok#contacto');
    exit;
} catch (PDOException $e) {
    if ((bool)($_ENV['APP_DEBUG'] ?? false)) {
        die("Error al procesar el mensaje: " . $e->getMessage());
    } else {
        error_log("Error en procesar.php: " . $e->getMessage());
        header('Location: index.html?error=contacto#contacto');
        exit;
    }
}
