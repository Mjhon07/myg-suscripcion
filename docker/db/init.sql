-- Inicialización automática al levantar Docker (MariaDB)
-- Login admin: admin / admin123

SET NAMES utf8mb4;

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

INSERT INTO `usuarios` (`nombre`, `usuario`, `password`, `rol`) VALUES
('Administrador MYG', 'admin', '$2y$10$FcRT1f6cYPZd5XJiiiD21O0YeR8JgUtSyfLBWq8GDKZwG3c2ahcJm', 'admin');
