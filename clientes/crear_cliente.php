<?php
/**
 * MYG - Crear Nuevo Cliente
 * Validación de cédula única y registro mediante PDO
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../conexion.php';

$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Administrador';
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
            // 1. Validar que la cédula no exista previamente
            $stmtCheck = $conn->prepare("SELECT id FROM clientes WHERE cedula = :cedula LIMIT 1");
            $stmtCheck->execute([':cedula' => $cedula]);

            if ($stmtCheck->fetch()) {
                $error = "Ya existe un cliente registrado con la cédula {$cedula}.";
            } else {
                // 2. Insertar nuevo cliente
                $stmtInsert = $conn->prepare("
                    INSERT INTO clientes (nombre, cedula, telefono, email, direccion) 
                    VALUES (:nombre, :cedula, :telefono, :email, :direccion)
                ");
                $stmtInsert->execute([
                    ':nombre'    => $nombre,
                    ':cedula'    => $cedula,
                    ':telefono'  => $telefono,
                    ':email'     => $email,
                    ':direccion' => $direccion,
                ]);

                header('Location: clientes.php?msg=creado');
                exit;
            }
        } catch (PDOException $e) {
            if ((bool)($_ENV['APP_DEBUG'] ?? false)) {
                $error = 'Error de base de datos: ' . $e->getMessage();
            } else {
                $error = 'Ocurrió un error interno al intentar registrar el cliente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Cliente - MYG Panel</title>
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
                    Registrar Nuevo Cliente
                </h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Ingrese la información del cliente para incorporarlo a la base de datos de MYG.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <ion-icon name="alert-circle-outline"></ion-icon>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="crear_cliente.php" method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej: Carlos Mendoza" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="cedula">Número de Cédula / Identificación *</label>
                    <input type="text" name="cedula" id="cedula" class="form-control" placeholder="Ej: 1029384756" required value="<?= htmlspecialchars($_POST['cedula'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono / Celular</label>
                    <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="+57 310 987 6543" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="carlos@correo.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección de Residencia / Entrega</label>
                    <input type="text" name="direccion" id="direccion" class="form-control" placeholder="Calle 45 # 12 - 34" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">
                </div>

                <div class="form-actions">
                    <a href="clientes.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <ion-icon name="save-outline"></ion-icon>
                        Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
