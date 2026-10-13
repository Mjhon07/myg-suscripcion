<?php
/**
 * MYG - Cierre Seguro de Sesión
 * Limpieza de cookies de sesión y destrucción total
 */

require_once __DIR__ . '/../env.php';

$sessionName = $_ENV['SESSION_NAME'] ?? 'MYG_session';

if (session_status() === PHP_SESSION_NONE) {
    session_name($sessionName);
    session_start();
}

// Vaciar arreglo de sesión
$_SESSION = [];

// Eliminar cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir la sesión en el servidor
session_destroy();

header('Location: login.php?msg=logout');
exit;
