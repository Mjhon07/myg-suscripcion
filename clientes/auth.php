<?php
/**
 * MYG - Middleware de Autenticación y Control de Sesión Administrativa
 * Protege las rutas administrativas verificando la sesión activa
 * Retorna código HTTP 401 en JSON para peticiones AJAX o redirige a login.html
 */

require_once __DIR__ . '/../env.php';

$sessionName = $_ENV['SESSION_NAME'] ?? 'MYG_session';

if (session_status() === PHP_SESSION_NONE) {
    session_name($sessionName);
    session_start();
}

// Detectar si la petición es asíncrona (AJAX / Fetch)
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
          (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

// Verificar si existe un usuario autenticado
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Acceso denegado. Sesión no iniciada o expirada.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        header('Location: login.html');
        exit;
    }
}
