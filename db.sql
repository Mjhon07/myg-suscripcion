-- ==============================================================================
-- MYG - Tablas para InfinityFree
-- NO usa CREATE DATABASE: en InfinityFree ya existe if0_43165716_mysql
-- En phpMyAdmin: selecciona if0_43165716_mysql y luego Importar este archivo
-- ==============================================================================

DROP TABLE IF EXISTS `pedidos`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `mensajes_contacto`;
DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `usuario` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('admin', 'editor') DEFAULT 'admin',
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mensajes_contacto` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `mensaje` TEXT NOT NULL,
    `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clientes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `cedula` VARCHAR(20) NOT NULL UNIQUE,
    `telefono` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `direccion` VARCHAR(255) NULL,
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pedidos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_id` INT NULL,
    `nombre_cliente` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `producto` VARCHAR(150) NOT NULL,
    `cantidad` INT NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `metodo_pago` VARCHAR(50) NULL,
    `estado_pago` VARCHAR(30) DEFAULT 'aprobado',
    `ref_pago` VARCHAR(64) NULL,
    `fecha_pedido` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pedidos_clientes`
        FOREIGN KEY (`cliente_id`)
        REFERENCES `clientes` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario: admin | Contraseña: admin123
INSERT INTO `usuarios` (`nombre`, `usuario`, `password`, `rol`) VALUES
('Administrador MYG', 'admin', 'y$uJAq5KCZXnv1teSJzPYzZei0VM8Kx2TbgDk9q0JqSK0smMRHvcZSe', 'admin');
