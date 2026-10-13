<?php
/**
 * MYG - Procesamiento de Compra Inteligente
 * Manejo de transacciones SQL atómicas para registro de clientes y pedidos
 */

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Obtener y sanitizar datos del formulario
$cedula    = trim($_POST['cedula'] ?? '');
$nombre    = trim($_POST['nombre'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$email     = trim($_POST['email'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$producto  = trim($_POST['producto'] ?? '');
$cantidad  = (int)($_POST['cantidad'] ?? 1);
$total     = (float)($_POST['total'] ?? 0.00);

$productoCodificado = urlencode($producto);

// Validaciones básicas requeridas
if (empty($cedula) || empty($nombre) || empty($email) || empty($producto) || $cantidad < 1 || $total <= 0) {
    header("Location: comprar.php?error=incompleto&producto={$productoCodificado}");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: comprar.php?error=email&producto={$productoCodificado}");
    exit;
}

try {
    // Iniciar transacción atómica
    $conn->beginTransaction();

    // 1. Verificar si el cliente ya existe por su cédula
    $stmtCliente = $conn->prepare("SELECT id FROM clientes WHERE cedula = :cedula LIMIT 1");
    $stmtCliente->execute([':cedula' => $cedula]);
    $clienteExistente = $stmtCliente->fetch();

    if ($clienteExistente) {
        $clienteId = (int)$clienteExistente['id'];
        
        // Actualizar datos de contacto por si cambiaron (opcional y recomendable)
        $stmtUpdate = $conn->prepare("
            UPDATE clientes 
            SET nombre = :nombre, telefono = :telefono, email = :email, direccion = :direccion 
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':nombre'    => $nombre,
            ':telefono'  => $telefono,
            ':email'     => $email,
            ':direccion' => $direccion,
            ':id'        => $clienteId
        ]);
    } else {
        // Registrar nuevo cliente
        $stmtInsert = $conn->prepare("
            INSERT INTO clientes (nombre, cedula, telefono, email, direccion) 
            VALUES (:nombre, :cedula, :telefono, :email, :direccion)
        ");
        $stmtInsert->execute([
            ':nombre'    => $nombre,
            ':cedula'    => $cedula,
            ':telefono'  => $telefono,
            ':email'     => $email,
            ':direccion' => $direccion
        ]);
        $clienteId = (int)$conn->lastInsertId();
    }

    // 2. Insertar el pedido enlazando el cliente_id
    $stmtPedido = $conn->prepare("
        INSERT INTO pedidos (cliente_id, nombre_cliente, email, producto, cantidad, total) 
        VALUES (:cliente_id, :nombre_cliente, :email, :producto, :cantidad, :total)
    ");
    $stmtPedido->execute([
        ':cliente_id'     => $clienteId,
        ':nombre_cliente' => $nombre,
        ':email'          => $email,
        ':producto'       => $producto,
        ':cantidad'       => $cantidad,
        ':total'          => $total
    ]);

    // Confirmar la transacción
    $conn->commit();

    // Redirigir a la página principal con mensaje de éxito
    header("Location: index.html?success=ok#catalogo");
    exit;

} catch (Exception $e) {
    // Revertir cambios en caso de cualquier error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    if ((bool)($_ENV['APP_DEBUG'] ?? false)) {
        die("Error en la transacción de compra: " . $e->getMessage());
    } else {
        error_log("Error en procesar_compra.php: " . $e->getMessage());
        header("Location: comprar.php?error=compra&producto={$productoCodificado}");
        exit;
    }
}
