-- Crear base de datos
CREATE DATABASE `transport_univo` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE transport_univo;

-- Tabla de roles
CREATE TABLE `roles` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar roles
INSERT INTO `roles` (`nombre`) VALUES 
('admin'), ('pasajero'), ('conductor');

-- Tabla de usuarios
CREATE TABLE `usuarios` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `codigo_universitario` varchar(20) NOT NULL,
  `password_hash` varchar(20) NOT NULL,
  `rol_id` int NOT NULL,
  UNIQUE KEY `codigo_unico` (`codigo_universitario`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `usuarios` (`codigo_universitario`, `password_hash`, `rol_id`) VALUES 
('u2026001','adminpass', 1), 
('u2026002','pasajeropass', 2), 
('u2026003','conductorpass', 3);
-- Tabla de conductores
CREATE TABLE `conductores` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  UNIQUE KEY `usuario_id_unico` (`usuario_id`),
  CONSTRAINT `fk_conductores_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `conductores` (`usuario_id`, `nombre`, `apellido`, `telefono`) VALUES 
(3, 'Juan', 'Pérez', '555-1234');
-- Tabla de microbuses
CREATE TABLE `microbuses` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `capacidad_maxima` int NOT NULL,
  UNIQUE KEY `placa_unica` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de sedes
CREATE TABLE `sedes` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  UNIQUE KEY `nombre_unico` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `sedes` (`nombre`) VALUES 
('Sede Central'), ('Ciudad Universitaria'), ('Campus de Agronomía y Veterinaria');

-- Tabla de horarios
CREATE TABLE `horarios` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `hora_salida` time NOT NULL,
  `hora_llegada` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de rutas 
CREATE TABLE `rutas` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_sede_origen` int NOT NULL,
  `id_sede_destino` int NOT NULL,
  `id_horario` int NOT NULL,
  CONSTRAINT `fk_rutas_origen` FOREIGN KEY (`id_sede_origen`) REFERENCES `sedes` (`id`),
  CONSTRAINT `fk_rutas_destino` FOREIGN KEY (`id_sede_destino`) REFERENCES `sedes` (`id`),
  CONSTRAINT `fk_rutas_horarios` FOREIGN KEY (`id_horario`) REFERENCES `horarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;