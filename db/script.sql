-- Crear base de datos
CREATE DATABASE `transport_univo` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE transport_univo;

-- Tabla de roles
CREATE TABLE `roles` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar roles
INSERT INTO `roles` (`nombre`) VALUES
('admin'),
('estudiante'),
('conductor');

-- Tabla de usuarios
CREATE TABLE `usuarios` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol_id` int NOT NULL,
  UNIQUE KEY `usuario` (`usuario`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar usuarios de prueba
INSERT INTO `usuarios` (`usuario`, `password_hash`, `rol_id`) VALUES
('admin',      '123', 1),
('estudiante', '123', 2),
('conductor',  '123', 3);
