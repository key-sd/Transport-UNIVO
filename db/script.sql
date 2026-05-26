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

-- Tabla de usuarios
CREATE TABLE `usuarios` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `codigo_universitario` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol_id` int NOT NULL,
  UNIQUE KEY `codigo_unico` (`codigo_universitario`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de conductores
CREATE TABLE `conductores` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `estado` tinyint(1) DEFAULT '1', -- 1 = Activo, 0 = Inactivo (Borrado lógico)
  UNIQUE KEY `usuario_id_unico` (`usuario_id`),
  CONSTRAINT `fk_conductores_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de microbuses
CREATE TABLE `unidades` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `capacidad_maxima` int NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  UNIQUE KEY `placa_unica` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de sedes
CREATE TABLE `sedes` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  UNIQUE KEY `nombre_unico` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de hora salida
CREATE TABLE `horas_salida` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `hora` time NOT NULL,
  `turno` enum('Matutino', 'Vespertino') NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  UNIQUE KEY `hora_unica` (`hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de rutas
CREATE TABLE `rutas` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_sede_origen` int NOT NULL,
  `id_sede_destino` int NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  CONSTRAINT `fk_rutas_origen` FOREIGN KEY (`id_sede_origen`) REFERENCES `sedes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rutas_destino` FOREIGN KEY (`id_sede_destino`) REFERENCES `sedes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cronograma semanal 
CREATE TABLE `cronograma_horarios` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_ruta` int NOT NULL,
  `id_hora_salida` int NOT NULL,
  `dia_semana` enum('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo') NOT NULL,
  `conductor_id` int NULL,
  `estado` tinyint(1) DEFAULT '1',
  -- Evita duplicados de conductor o microbus
  UNIQUE KEY `itinerario_unico` (`id_ruta`, `id_hora_salida`, `dia_semana`),
  CONSTRAINT `fk_cronograma_rutas` FOREIGN KEY (`id_ruta`) REFERENCES `rutas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cronograma_horas` FOREIGN KEY (`id_hora_salida`) REFERENCES `horas_salida` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Para la logica de estado de unidades en tiempo real
CREATE TABLE `estado_unidad` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `conductor_id` int NOT NULL,
  `unidad_id` int NOT NULL,
  `estado` enum('en_sede','proximo_salir','en_trafico','llegando') NOT NULL,
  `capacidad` enum('disponible','medio_lleno','lleno') NOT NULL,
  `actualizado_en` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_estado_conductor` FOREIGN KEY (`conductor_id`) REFERENCES `conductores` (`id`),
  CONSTRAINT `fk_estado_unidad` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 -- Para almacenar la ubicación en tiempo real de los conductores según lo cambien
CREATE TABLE `ubicaciones` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `conductor_id` int NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `actualizado_en` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ubicacion_conductor` FOREIGN KEY (`conductor_id`) REFERENCES `conductores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Insertar roles
INSERT INTO `roles` (`nombre`) VALUES 
('admin'), ('pasajero'), ('conductor');

-- Insertar usuarios 
INSERT INTO `usuarios` (`codigo_universitario`, `password_hash`, `rol_id`) VALUES 
('u20260001','$2y$10$vqyXr8as1lK//K.JQF3Q2uxC9h4ivZl4Ijj.FfJfcyw.aCIqDA1Pi', 1), 
('u20260002','$2y$10$kmxPL1U7kDc59MM71rr4rO5yQOyJAfxYpaViQglHvXZ9kNrmOcN8G', 2), 
('u20260003','$2y$10$LXz7KvcADrt8rxvU2wk3Ye0Sqd.Se0hhmRD1CAxf6g3tpN5/MwyFG', 3);

-- Insertar conductor 
INSERT INTO `conductores` (`usuario_id`, `nombre`, `apellido`, `telefono`) VALUES 
(3, 'Juan', 'Pérez', '7777-1234'); 
-- Insertar unidades
INSERT INTO `unidades` (`nombre`, `placa`, `capacidad_maxima`) VALUES 
('Unidad 1', 'U-12345', 30);

-- Insertar sedes
INSERT INTO `sedes` (`nombre`) VALUES 
('Sede Central'), 
('Ciudad Universitaria'), 
('Campus de Agronomía y Veterinaria');


--- Datos de pruebas con los horarios reales de la U
INSERT INTO `horas_salida` (`hora`, `turno`) VALUES
('06:15:00', 'Matutino'),
('06:20:00', 'Matutino'),
('06:40:00', 'Matutino'),
('07:15:00', 'Matutino'),
('07:20:00', 'Matutino'),
('08:00:00', 'Matutino'),
('09:25:00', 'Matutino'),
('10:00:00', 'Matutino'),
('10:40:00', 'Matutino'),
('11:10:00', 'Matutino'),
('11:20:00', 'Matutino'),
('12:10:00', 'Vespertino'),
('12:20:00', 'Vespertino'),
('12:30:00', 'Vespertino'),
('12:50:00', 'Vespertino'),
('14:30:00', 'Vespertino'),
('14:40:00', 'Vespertino'),
('15:15:00', 'Vespertino'),
('15:30:00', 'Vespertino'),
('16:00:00', 'Vespertino'),
('16:10:00', 'Vespertino'),
('16:20:00', 'Vespertino'),
('16:40:00', 'Vespertino'),
('16:45:00', 'Vespertino'),
('17:10:00', 'Vespertino');
 
INSERT INTO `rutas` (`id_sede_origen`, `id_sede_destino`) VALUES
(1, 2),
(2, 1),
(1, 3),
(3, 1);
 
INSERT INTO `cronograma_horarios` (`id_ruta`, `id_hora_salida`, `dia_semana`, `conductor_id`) VALUES
(1, (SELECT id FROM horas_salida WHERE hora='06:40:00'), 'Lunes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Lunes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='11:10:00'), 'Lunes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='12:50:00'), 'Lunes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='16:00:00'), 'Lunes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='16:45:00'), 'Lunes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='06:40:00'), 'Martes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Martes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='10:00:00'), 'Martes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='12:30:00'), 'Martes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='15:15:00'), 'Martes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='16:40:00'), 'Martes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='06:40:00'), 'Miércoles', 1),
(1, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Miércoles', 1),
(1, (SELECT id FROM horas_salida WHERE hora='11:10:00'), 'Miércoles', 1),
(1, (SELECT id FROM horas_salida WHERE hora='12:50:00'), 'Miércoles', 1),
(1, (SELECT id FROM horas_salida WHERE hora='16:00:00'), 'Miércoles', 1),
(1, (SELECT id FROM horas_salida WHERE hora='16:45:00'), 'Miércoles', 1),
(1, (SELECT id FROM horas_salida WHERE hora='06:40:00'), 'Jueves', 1),
(1, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Jueves', 1),
(1, (SELECT id FROM horas_salida WHERE hora='10:00:00'), 'Jueves', 1),
(1, (SELECT id FROM horas_salida WHERE hora='12:30:00'), 'Jueves', 1),
(1, (SELECT id FROM horas_salida WHERE hora='15:15:00'), 'Jueves', 1),
(1, (SELECT id FROM horas_salida WHERE hora='16:40:00'), 'Jueves', 1),
(1, (SELECT id FROM horas_salida WHERE hora='06:40:00'), 'Viernes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Viernes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='11:10:00'), 'Viernes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='12:50:00'), 'Viernes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='16:00:00'), 'Viernes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='16:45:00'), 'Viernes', 1),
(1, (SELECT id FROM horas_salida WHERE hora='06:40:00'), 'Sábado', 1),
(1, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Sábado', 1),
(1, (SELECT id FROM horas_salida WHERE hora='11:10:00'), 'Sábado', 1),
(1, (SELECT id FROM horas_salida WHERE hora='15:15:00'), 'Sábado', 1),
(2, (SELECT id FROM horas_salida WHERE hora='06:15:00'), 'Lunes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Lunes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='10:40:00'), 'Lunes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='12:20:00'), 'Lunes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='15:30:00'), 'Lunes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='16:20:00'), 'Lunes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='06:15:00'), 'Martes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='07:20:00'), 'Martes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='09:25:00'), 'Martes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='12:10:00'), 'Martes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='14:40:00'), 'Martes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='16:10:00'), 'Martes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='06:15:00'), 'Miércoles', 1),
(2, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Miércoles', 1),
(2, (SELECT id FROM horas_salida WHERE hora='10:40:00'), 'Miércoles', 1),
(2, (SELECT id FROM horas_salida WHERE hora='12:20:00'), 'Miércoles', 1),
(2, (SELECT id FROM horas_salida WHERE hora='15:30:00'), 'Miércoles', 1),
(2, (SELECT id FROM horas_salida WHERE hora='16:20:00'), 'Miércoles', 1),
(2, (SELECT id FROM horas_salida WHERE hora='06:15:00'), 'Jueves', 1),
(2, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Jueves', 1),
(2, (SELECT id FROM horas_salida WHERE hora='09:25:00'), 'Jueves', 1),
(2, (SELECT id FROM horas_salida WHERE hora='12:10:00'), 'Jueves', 1),
(2, (SELECT id FROM horas_salida WHERE hora='14:40:00'), 'Jueves', 1),
(2, (SELECT id FROM horas_salida WHERE hora='16:10:00'), 'Jueves', 1),
(2, (SELECT id FROM horas_salida WHERE hora='06:15:00'), 'Viernes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Viernes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='09:25:00'), 'Viernes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='10:40:00'), 'Viernes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='12:20:00'), 'Viernes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='15:30:00'), 'Viernes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='16:20:00'), 'Viernes', 1),
(2, (SELECT id FROM horas_salida WHERE hora='06:15:00'), 'Sábado', 1),
(2, (SELECT id FROM horas_salida WHERE hora='08:00:00'), 'Sábado', 1),
(2, (SELECT id FROM horas_salida WHERE hora='10:40:00'), 'Sábado', 1),
(2, (SELECT id FROM horas_salida WHERE hora='14:40:00'), 'Sábado', 1),
(3, (SELECT id FROM horas_salida WHERE hora='07:15:00'), 'Martes', 1),
(3, (SELECT id FROM horas_salida WHERE hora='12:20:00'), 'Martes', 1),
(3, (SELECT id FROM horas_salida WHERE hora='07:15:00'), 'Miércoles', 1),
(3, (SELECT id FROM horas_salida WHERE hora='12:20:00'), 'Miércoles', 1),
(3, (SELECT id FROM horas_salida WHERE hora='07:15:00'), 'Jueves', 1),
(3, (SELECT id FROM horas_salida WHERE hora='12:20:00'), 'Jueves', 1),
(3, (SELECT id FROM horas_salida WHERE hora='07:15:00'), 'Viernes', 1),
(3, (SELECT id FROM horas_salida WHERE hora='12:20:00'), 'Viernes', 1),
(3, (SELECT id FROM horas_salida WHERE hora='06:20:00'), 'Sábado', 1),
(4, (SELECT id FROM horas_salida WHERE hora='11:20:00'), 'Martes', 1),
(4, (SELECT id FROM horas_salida WHERE hora='16:20:00'), 'Martes', 1),
(4, (SELECT id FROM horas_salida WHERE hora='11:20:00'), 'Miércoles', 1),
(4, (SELECT id FROM horas_salida WHERE hora='16:20:00'), 'Miércoles', 1),
(4, (SELECT id FROM horas_salida WHERE hora='11:20:00'), 'Jueves', 1),
(4, (SELECT id FROM horas_salida WHERE hora='16:20:00'), 'Jueves', 1),
(4, (SELECT id FROM horas_salida WHERE hora='11:20:00'), 'Viernes', 1),
(4, (SELECT id FROM horas_salida WHERE hora='16:20:00'), 'Viernes', 1),
(4, (SELECT id FROM horas_salida WHERE hora='14:30:00'), 'Sábado', 1);