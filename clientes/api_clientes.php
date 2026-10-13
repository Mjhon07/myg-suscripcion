<?php
/**
 * MYG - API RESTful de Clientes
 * Operaciones CRUD completas, búsqueda en tiempo real (debounced) y verificación de cédula única
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Leer cuerpo JSON si aplica
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true) ?? [];
if (!empty($jsonData)) {
    $_POST = array_merge($_POST, $jsonData);
    if (isset($jsonData['action'])) {
        $action = $jsonData['action'];
    }
}

switch ($action) {

    // =========================================================================
    // 1. LISTADO PAGINADO DE CLIENTES (5 POR PÁGINA)
    // =========================================================================
    case 'list':
        $limite = 5;
        $pagina = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($pagina - 1) * $limite;

        try {
            $stmtCount = $conn->query("SELECT COUNT(*) FROM clientes");
            $totalRegistros = (int)$stmtCount->fetchColumn();
            $totalPaginas   = max(1, (int)ceil($totalRegistros / $limite));

            $stmt = $conn->prepare("
                SELECT id, nombre, cedula, telefono, email, direccion, fecha_registro 
                FROM clientes 
                ORDER BY id DESC 
                LIMIT {$limite} OFFSET {$offset}
            ");
            $stmt->execute();
            $clientes = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                'success'         => true,
                'clientes'        => $clientes,
                'total_registros' => $totalRegistros,
                'total_paginas'   => $totalPaginas,
                'pagina_actual'   => $pagina
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'mensaje' => 'Error al listar clientes.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // =========================================================================
    // 2. BÚSQUEDA REACTIVA CON DEBOUNCE (NOMBRE, CÉDULA O EMAIL)
    // =========================================================================
    case 'search':
        $termino = trim($_GET['q'] ?? '');

        if (empty($termino)) {
            // Si la búsqueda está vacía, redirigir a la lista normal
            header("Location: api_clientes.php?action=list&page=1");
            exit;
        }

        try {
            $likeTerm = "%{$termino}%";
            // Regla PDO obligatoria: parámetros únicos con ATTR_EMULATE_PREPARES => false
            $stmt = $conn->prepare("
                SELECT id, nombre, cedula, telefono, email, direccion, fecha_registro 
                FROM clientes 
                WHERE nombre LIKE :q_nombre OR cedula LIKE :q_cedula OR email LIKE :q_email 
                ORDER BY id DESC 
                LIMIT 25
            ");
            $stmt->execute([
                ':q_nombre' => $likeTerm,
                ':q_cedula' => $likeTerm,
                ':q_email'  => $likeTerm
            ]);
            $clientes = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                'success'         => true,
                'clientes'        => $clientes,
                'total_registros' => count($clientes),
                'termino'         => $termino
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'mensaje' => 'Error en la búsqueda de clientes.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // =========================================================================
    // 3. VERIFICACIÓN DE CÉDULA ÚNICA EN TIEMPO REAL
    // =========================================================================
    case 'check_cedula':
        $cedula    = trim($_GET['cedula'] ?? $_POST['cedula'] ?? '');
        $excludeId = (int)($_GET['exclude_id'] ?? $_POST['exclude_id'] ?? 0);

        if (empty($cedula)) {
            http_response_code(200);
            echo json_encode(['exists' => false]);
            exit;
        }

        try {
            if ($excludeId > 0) {
                $stmt = $conn->prepare("SELECT id FROM clientes WHERE cedula = :cedula AND id != :id LIMIT 1");
                $stmt->execute([':cedula' => $cedula, ':id' => $excludeId]);
            } else {
                $stmt = $conn->prepare("SELECT id FROM clientes WHERE cedula = :cedula LIMIT 1");
                $stmt->execute([':cedula' => $cedula]);
            }

            $existe = (bool)$stmt->fetch();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'exists'  => $existe,
                'mensaje' => $existe ? 'Esta cédula ya está registrada en el sistema.' : 'Cédula disponible.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'mensaje' => 'Error al validar la cédula.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // =========================================================================
    // 4. OBTENER UN CLIENTE POR ID (PARA MODAL EDITAR)
    // =========================================================================
    case 'get':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        try {
            $stmt = $conn->prepare("SELECT id, nombre, cedula, telefono, email, direccion FROM clientes WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $cliente = $stmt->fetch();

            if ($cliente) {
                http_response_code(200);
                echo json_encode(['success' => true, 'cliente' => $cliente], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'mensaje' => 'Cliente no encontrado.'], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'mensaje' => 'Error al obtener datos del cliente.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // =========================================================================
    // 5. CREAR NUEVO CLIENTE (DESDE MODAL)
    // =========================================================================
    case 'create':
        $nombre    = trim($_POST['nombre'] ?? '');
        $cedula    = trim($_POST['cedula'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if (empty($nombre) || empty($cedula)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'Nombre y cédula son obligatorios.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            // Verificar unicidad
            $stmtCheck = $conn->prepare("SELECT id FROM clientes WHERE cedula = :cedula LIMIT 1");
            $stmtCheck->execute([':cedula' => $cedula]);
            if ($stmtCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'mensaje' => 'Ya existe un cliente con esta cédula.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("
                INSERT INTO clientes (nombre, cedula, telefono, email, direccion) 
                VALUES (:nombre, :cedula, :telefono, :email, :direccion)
            ");
            $stmt->execute([
                ':nombre'    => $nombre,
                ':cedula'    => $cedula,
                ':telefono'  => $telefono,
                ':email'     => $email,
                ':direccion' => $direccion
            ]);
            $nuevoId = (int)$conn->lastInsertId();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'mensaje' => '¡Cliente registrado con éxito!',
                'cliente' => [
                    'id'             => $nuevoId,
                    'nombre'         => $nombre,
                    'cedula'         => $cedula,
                    'telefono'       => $telefono,
                    'email'          => $email,
                    'direccion'      => $direccion,
                    'fecha_registro' => date('Y-m-d H:i:s')
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'mensaje' => 'Error al registrar el cliente.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // =========================================================================
    // 6. ACTUALIZAR CLIENTE (DESDE MODAL)
    // =========================================================================
    case 'update':
        $id        = (int)($_POST['id'] ?? 0);
        $nombre    = trim($_POST['nombre'] ?? '');
        $cedula    = trim($_POST['cedula'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if ($id <= 0 || empty($nombre) || empty($cedula)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'ID, nombre y cédula son obligatorios.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            // Verificar que no pertenezca a otro
            $stmtCheck = $conn->prepare("SELECT id FROM clientes WHERE cedula = :cedula AND id != :id LIMIT 1");
            $stmtCheck->execute([':cedula' => $cedula, ':id' => $id]);
            if ($stmtCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'mensaje' => 'La cédula ya pertenece a otro cliente.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("
                UPDATE clientes 
                SET nombre = :nombre, cedula = :cedula, telefono = :telefono, email = :email, direccion = :direccion 
                WHERE id = :id
            ");
            $stmt->execute([
                ':nombre'    => $nombre,
                ':cedula'    => $cedula,
                ':telefono'  => $telefono,
                ':email'     => $email,
                ':direccion' => $direccion,
                ':id'        => $id
            ]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'mensaje' => '¡Datos del cliente actualizados exitosamente!',
                'cliente' => [
                    'id'        => $id,
                    'nombre'    => $nombre,
                    'cedula'    => $cedula,
                    'telefono'  => $telefono,
                    'email'     => $email,
                    'direccion' => $direccion
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar el cliente.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // =========================================================================
    // 7. ELIMINAR CLIENTE
    // =========================================================================
    case 'delete':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'ID inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = $conn->prepare("DELETE FROM clientes WHERE id = :id");
            $stmt->execute([':id' => $id]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'mensaje' => 'El cliente fue eliminado correctamente.',
                'id'      => $id
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'mensaje' => 'Error al eliminar el cliente.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida.'], JSON_UNESCAPED_UNICODE);
        break;
}
