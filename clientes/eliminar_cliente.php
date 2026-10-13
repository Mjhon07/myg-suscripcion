<?php
/**
 * MYG - Eliminación Segura de Clientes
 * Verificación de ID y eliminación en cascada controlada (ON DELETE SET NULL en pedidos)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $conn->prepare("DELETE FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);

        header('Location: clientes.php?msg=eliminado');
        exit;
    } catch (PDOException $e) {
        if ((bool)($_ENV['APP_DEBUG'] ?? false)) {
            die("Error al eliminar cliente: " . $e->getMessage());
        } else {
            error_log("Error al eliminar cliente: " . $e->getMessage());
            header('Location: clientes.php?msg=error_eliminar');
            exit;
        }
    }
}

header('Location: clientes.php');
exit;
