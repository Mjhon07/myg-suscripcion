<?php
/**
 * MYG - Editar Cliente
 * Validación de cédula única excluyendo el registro actual y actualización PDO
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: clientes.php');
    exit;
}

// Consultar datos del cliente
$stmt = $conn->prepare("SELECT id, nombre, cedula, telefono, email, direccion FROM clientes WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header('Location: clientes.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $cedula    = trim($_POST['cedula'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (empty($nombre) || empty($cedula)) {
        $error = 'El nombre y el número de cédula son campos obligatorios.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico ingresado no tiene un formato válido.';
    } else {
        try {
            // Validar que la cédula no pertenezca a otro cliente
            $stmtCheck = $conn->prepare("SELECT id FROM clientes WHERE cedula = :cedula AND id != :id LIMIT 1");
            $stmtCheck->execute([
                ':cedula' => $cedula,
                ':id'     => $id
            ]);

            if ($stmtCheck->fetch()) {
                $error = "La cédula {$cedula} ya está asignada a otro cliente.";
            } else {
                // Actualizar registro
                $stmtUpdate = $conn->prepare("
                    UPDATE clientes 
                    SET nombre = :nombre, cedula = :cedula, telefono = :telefono, email = :email, direccion = :direccion 
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    ':nombre'    => $nombre,
                    ':cedula'    => $cedula,
                    ':telefono'  => $telefono,
                    ':email'     => $email,
                    ':direccion' => $direccion,
                    ':id'        => $id
                ]);

                header('Location: clientes.php?msg=actualizado');
                exit;
            }
        } catch (PDOException $e) {
            if ((bool)($_ENV['APP_DEBUG'] ?? false)) {
                $error = 'Error de base de datos: ' . $e->getMessage();
            } else {
                $error = 'Ocurrió un error interno al actualizar el cliente.';
            }
        }
    }
} else {
    // Rellenar variables con los datos de la base de datos
    $nombre    = $cliente['nombre'];
    $cedula    = $cliente['cedula'];
    $telefono  = $cliente['telefono'];
    $email     = $cliente['email'];
    $direccion = $cliente['direccion'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente #<?= $id ?> - MYG Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <nav class="admin-navbar">
        <div class="admin-nav-container">
            <a href="clientes.php" class="admin-brand">
                <div class="admin-brand-icon">
                    <ion-icon name="game-controller"></ion-icon>
                </div>
                <span>MYG Admin</span>
            </a>
            <div class="admin-user-info">
                <a href="clientes.php" class="btn btn-secondary" style="padding: 0.45rem 0.9rem; font-size: 0.85rem;">
                    <ion-icon name="arrow-back-outline"></ion-icon>
                    Volver al listado
                </a>
            </div>
        </div>
    </nav>

    <main class="admin-main">
        <div class="form-card">
            <div style="margin-bottom: 2rem;">
                <h1 style="font-family: var(--font-heading); font-size: 1.8rem; color: #fff; margin-bottom: 0.35rem;">
                    Editar Cliente <span style="color: var(--cyan);">#<?= $id ?></span>
                </h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Modifique los datos requeridos. El sistema validará la unicidad de la cédula.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <ion-icon name="alert-circle-outline"></ion-icon>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="editar_cliente.php?id=<?= $id ?>" method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej: Carlos Mendoza" required value="<?= htmlspecialchars($nombre) ?>">
                </div>

                <div class="form-group">
                    <label for="cedula">Número de Cédula / Identificación *</label>
                    <input type="text" name="cedula" id="cedula" class="form-control" placeholder="Ej: 1029384756" required value="<?= htmlspecialchars($cedula) ?>">
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono / Celular</label>
                    <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="+57 310 987 6543" value="<?= htmlspecialchars($telefono ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="carlos@correo.com" value="<?= htmlspecialchars($email ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección de Residencia / Entrega</label>
                    <input type="text" name="direccion" id="direccion" class="form-control" placeholder="Calle 45 # 12 - 34" value="<?= htmlspecialchars($direccion ?? '') ?>">
                </div>

                <div class="form-actions">
                    <a href="clientes.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <ion-icon name="checkmark-done-outline"></ion-icon>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
