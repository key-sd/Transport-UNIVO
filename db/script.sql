-- Crear base de datos
CREATE DATABASE `db_transport_univo` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_transport_univo;

-- Tabla de roles
CREATE TABLE `roles` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  UNIQUE KEY `uq_rol_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de usuarios
CREATE TABLE `usuarios` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol_id` INT NOT NULL,
  UNIQUE KEY `uq_codigo` (`codigo`),
  CONSTRAINT `fk_usuarios_rol`
    FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de conductores
CREATE TABLE `conductores` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `nombre` VARCHAR(50) NOT NULL,
  `apellido` VARCHAR(50) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `estado` TINYINT(1) DEFAULT 1, -- 1 = Activo, 0 = Inactivo
  UNIQUE KEY `uq_conductor_usuario` (`usuario_id`),
  CONSTRAINT `fk_conductor_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de unidades
CREATE TABLE `unidades` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `placa` VARCHAR(20) NOT NULL,
  `capacidad_maxima` INT NOT NULL,
  `estado` TINYINT(1) DEFAULT 1,
  UNIQUE KEY `uq_placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de sedes
CREATE TABLE `sedes` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `estado` TINYINT(1) DEFAULT 1,
  UNIQUE KEY `uq_sede_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tablas para el cronogramaHorarios donde se podrá seleccionar multiples.
CREATE TABLE `cronograma_horarios` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_sede_origen` INT NOT NULL,
  `id_sede_destino` INT NOT NULL,
  `dia_semana` ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
  `hora_salida` TIME NOT NULL,
  `turno` ENUM('Matutino','Vespertino') NOT NULL,
  `estado` TINYINT(1) DEFAULT 1,
  UNIQUE KEY `uq_cronograma`
    (`id_sede_origen`, `id_sede_destino`, `dia_semana`, `hora_salida`),
  CONSTRAINT `fk_crono_origen` FOREIGN KEY (`id_sede_origen`) REFERENCES `sedes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_crono_destino` FOREIGN KEY (`id_sede_destino`) REFERENCES `sedes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Horarios del conductor (son las asiganciones del admin)
CREATE TABLE `horarios_conductor` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_unidad` INT NOT NULL,
  `dia_semana` ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `estado` TINYINT(1) DEFAULT 1,
  CONSTRAINT `fk_hc_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- asiganciones para los conductores y la vigencia 
CREATE TABLE `asignaciones_conductor` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_horario` INT NOT NULL,
  `id_conductor` INT NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  CONSTRAINT `fk_asignar_horario` FOREIGN KEY (`id_horario`)REFERENCES `horarios_conductor` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_asignar_conductor` FOREIGN KEY (`id_conductor`) REFERENCES `conductores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tabla sustituciones
-- Evento temporal cuando un conductor es reemplazado.

CREATE TABLE `sustituciones` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_asignacion` INT NOT NULL,
  `id_conductor_suplente` INT NOT NULL,
  `fecha_desde` DATE NOT NULL,
  `fecha_hasta` DATE DEFAULT NULL,
  `motivo` VARCHAR(255) DEFAULT NULL,
  `activa` TINYINT(1) DEFAULT 1,
  CONSTRAINT `fk_sust_asignacion` FOREIGN KEY (`id_asignacion`) 
    REFERENCES `asignaciones_conductor` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_sust_suplente` FOREIGN KEY (`id_conductor_suplente`) REFERENCES `conductores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tabla para los viajes 
-- Una fila por cada salida real del microbús.
CREATE TABLE `viajes` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_cronograma` INT NOT NULL,
  `id_conductor` INT NOT NULL,
  `id_unidad` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `hora_salida_programada` TIME NOT NULL,
  `hora_salida_real` TIME DEFAULT NULL,
  `estado_recorrido` ENUM('en_sede','proximo_salir','en_camino','llegando','completado') NOT NULL DEFAULT 'en_sede',
  `estado_unidad` ENUM('vacio','medio_lleno','lleno') NOT NULL DEFAULT 'vacio',
  UNIQUE KEY `uq_viaje` (`id_cronograma`, `fecha`),
  CONSTRAINT `fk_viaje_crono` FOREIGN KEY (`id_cronograma`) REFERENCES `cronograma_horarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_viaje_conductor` FOREIGN KEY (`id_conductor`) REFERENCES `conductores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_viaje_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tabla ubicaciones log GPS del conductor mientras opera.
CREATE TABLE `ubicaciones` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `conductor_id` int NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `actualizado_en` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ubicacion_conductor` FOREIGN KEY (`conductor_id`) REFERENCES `conductores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- VALORES INSERTADOS
-- Insertar roles
INSERT INTO `roles` (`nombre`) VALUES
('admin'),
('pasajero'),
('conductor');
-- Insertar usuarios
INSERT INTO `usuarios` (`codigo`, `password_hash`, `rol_id`) VALUES 
('a20260001','$2y$10$vqyXr8as1lK//K.JQF3Q2uxC9h4ivZl4Ijj.FfJfcyw.aCIqDA1Pi', 1), 
('u20260002','$2y$10$kmxPL1U7kDc59MM71rr4rO5yQOyJAfxYpaViQglHvXZ9kNrmOcN8G', 2),
('c0001','$2y$10$LXz7KvcADrt8rxvU2wk3Ye0Sqd.Se0hhmRD1CAxf6g3tpN5/MwyFG', 3),
('c0002','$2y$10$LXz7KvcADrt8rxvU2wk3Ye0Sqd.Se0hhmRD1CAxf6g3tpN5/MwyFG', 3);
-- Insertar conductores
INSERT INTO `conductores`
(`usuario_id`, `nombre`, `apellido`, `telefono`) VALUES
(3,'Levi','Morales','7777-1234'), (4,'Daniela','Ackerman','7678-1345');

-- Insertar unidades
INSERT INTO `unidades`
(`nombre`, `placa`, `capacidad_maxima`) VALUES
('Unidad 1','U-12345',30),
('Unidad 2','U-67890',25);

-- Insertar sedes
INSERT INTO `sedes` (`nombre`) VALUES
('Sede Central'),
('Ciudad Universitaria'),
('Campus Agronomía y Veterinaria');

--  cronograma de horarios — Martes, Miércoles y Jueves completos
--  Rutas: 1=SC→CU  2=CU→SC  3=SC→Agro  4=Agro→SC

-- RUTA 1: Sede Central → Ciudad Universitaria 
INSERT INTO `cronograma_horarios` (`id_sede_origen`,`id_sede_destino`,`dia_semana`,`hora_salida`,`turno`) VALUES
-- Martes
(1,2,'Martes','06:40','Matutino'),
(1,2,'Martes','08:00','Matutino'),
(1,2,'Martes','10:00','Matutino'),
(1,2,'Martes','12:30','Vespertino'),
(1,2,'Martes','15:15','Vespertino'),
(1,2,'Martes','16:40','Vespertino'),
-- Miércoles
(1,2,'Miércoles','06:40','Matutino'),
(1,2,'Miércoles','08:00','Matutino'),
(1,2,'Miércoles','11:10','Matutino'),
(1,2,'Miércoles','12:50','Vespertino'),
(1,2,'Miércoles','16:00','Vespertino'),
(1,2,'Miércoles','16:45','Vespertino'),
-- Jueves
(1,2,'Jueves','06:40','Matutino'),
(1,2,'Jueves','08:00','Matutino'),
(1,2,'Jueves','10:00','Matutino'),
(1,2,'Jueves','12:30','Vespertino'),
(1,2,'Jueves','15:15','Vespertino'),
(1,2,'Jueves','16:40','Vespertino');

-- RUTA 2: Ciudad Universitaria → Sede Central 
INSERT INTO `cronograma_horarios` (`id_sede_origen`,`id_sede_destino`,`dia_semana`,`hora_salida`,`turno`) VALUES
-- Martes
(2,1,'Martes','06:15','Matutino'),
(2,1,'Martes','07:20','Matutino'),
(2,1,'Martes','09:25','Matutino'),
(2,1,'Martes','12:10','Vespertino'),
(2,1,'Martes','14:40','Vespertino'),
(2,1,'Martes','16:10','Vespertino'),
-- Miércoles
(2,1,'Miércoles','06:15','Matutino'),
(2,1,'Miércoles','08:00','Matutino'),
(2,1,'Miércoles','10:40','Matutino'),
(2,1,'Miércoles','12:20','Vespertino'),
(2,1,'Miércoles','15:30','Vespertino'),
(2,1,'Miércoles','16:20','Vespertino'),
-- Jueves
(2,1,'Jueves','06:15','Matutino'),
(2,1,'Jueves','08:00','Matutino'),
(2,1,'Jueves','09:25','Matutino'),
(2,1,'Jueves','12:10','Vespertino'),
(2,1,'Jueves','14:40','Vespertino'),
(2,1,'Jueves','16:10','Vespertino');

-- RUTA 3: Sede Central → Campus Agronomía 
INSERT INTO `cronograma_horarios` (`id_sede_origen`,`id_sede_destino`,`dia_semana`,`hora_salida`,`turno`) VALUES
(1,3,'Martes','07:15','Matutino'),
(1,3,'Martes','12:20','Vespertino'),
(1,3,'Miércoles','07:15','Matutino'),
(1,3,'Miércoles','12:20','Vespertino'),
(1,3,'Jueves','07:15','Matutino'),
(1,3,'Jueves','12:20','Vespertino');

-- RUTA 4: Campus Agronomía → Sede Central 
INSERT INTO `cronograma_horarios` (`id_sede_origen`,`id_sede_destino`,`dia_semana`,`hora_salida`,`turno`) VALUES
(3,1,'Martes','11:20','Matutino'),
(3,1,'Martes','16:20','Vespertino'),
(3,1,'Miércoles','11:20','Matutino'),
(3,1,'Miércoles','16:20','Vespertino'),
(3,1,'Jueves','11:20','Matutino'),
(3,1,'Jueves','16:20','Vespertino');

--  HORARIOS DE TRABAJO Y ASIGNACIONES

-- Horario A: Levi cubre SC↔CU de lunes a sábado mañana+tarde
INSERT INTO `horarios_conductor` (`id_unidad`,`dia_semana`,`hora_inicio`,`hora_fin`) VALUES
(1,'Martes','06:00','17:30'),
(1,'Miércoles','06:00','17:30'),
(1,'Jueves','06:00','17:30');

-- Horario B: Daniela cubre ruta Agro (solo Ma-V)
INSERT INTO `horarios_conductor` (`id_unidad`,`dia_semana`,`hora_inicio`,`hora_fin`) VALUES
(2,'Martes','07:00','17:00'),
(2,'Miércoles','07:00','17:00'),
(2,'Jueves','07:00','17:00');

-- Asignaciones
INSERT INTO `asignaciones_conductor` (`id_horario`,`id_conductor`,`fecha_inicio`) VALUES
-- Levi cubre horarios 
(1,1,'2026-05-26'), 
(2,1,'2026-05-26'), 
(3,1,'2026-05-26'), 
-- Daniela cubre horarios agro
(4,2,'2026-05-26'), 
(5,2,'2026-05-26'), 
(6,2,'2026-05-26'); 