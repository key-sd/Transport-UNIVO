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
  `estado` TINYINT(1) DEFAULT 1, -- 1 = Activo, 0 = Inactivo
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

-- asiganciones para los conductores y la vigencia 
CREATE TABLE `asignaciones_conductor` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_cronograma` INT NOT NULL,
  `id_conductor` INT NOT NULL,
  `id_unidad` INT NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  CONSTRAINT `fk_ac_cronograma` FOREIGN KEY (`id_cronograma`) REFERENCES `cronograma_horarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ac_conductor` FOREIGN KEY (`id_conductor`) REFERENCES `conductores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ac_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT
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
  `id_asignacion` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `hora_salida_programada` TIME NOT NULL,
  `hora_salida_real` TIME DEFAULT NULL,
  `estado_recorrido` ENUM('en_sede','proximo_salir','en_camino','completado','cancelado') NOT NULL DEFAULT 'en_sede',
  `estado_unidad` ENUM('vacio','medio_lleno','lleno') NOT NULL DEFAULT 'vacio',
  UNIQUE KEY `uq_viaje` (`id_asignacion`, `fecha`),
  CONSTRAINT `fk_viaje_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignaciones_conductor` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tabla ubicaciones log GPS del conductor mientras opera.
CREATE TABLE `ubicaciones` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_conductor` int NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `actualizado_en` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ubicacion_conductor` FOREIGN KEY (`id_conductor`) REFERENCES `conductores` (`id`)
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
('u20260001','$2y$10$kmxPL1U7kDc59MM71rr4rO5yQOyJAfxYpaViQglHvXZ9kNrmOcN8G', 2),
('u20260002','$2y$10$kmxPL1U7kDc59MM71rr4rO5yQOyJAfxYpaViQglHvXZ9kNrmOcN8G', 2),
('u20260003','$2y$10$kmxPL1U7kDc59MM71rr4rO5yQOyJAfxYpaViQglHvXZ9kNrmOcN8G', 2),
('u20260004','$2y$10$kmxPL1U7kDc59MM71rr4rO5yQOyJAfxYpaViQglHvXZ9kNrmOcN8G', 2),
('u20260005','$2y$10$kmxPL1U7kDc59MM71rr4rO5yQOyJAfxYpaViQglHvXZ9kNrmOcN8G', 2);
-- Insertar sedes
INSERT INTO `sedes` (`nombre`) VALUES
('Sede Central'),
('Ciudad Universitaria'),
('Campus Agronomia y Veterinaria');

-- Insertar usuarios
INSERT INTO `usuarios` (`codigo`, `password_hash`, `rol_id`) VALUES 
('c0001','$2y$10$LXz7KvcADrt8rxvU2wk3Ye0Sqd.Se0hhmRD1CAxf6g3tpN5/MwyFG', 3),
('c0002','$2y$10$LXz7KvcADrt8rxvU2wk3Ye0Sqd.Se0hhmRD1CAxf6g3tpN5/MwyFG', 3),
('c0003','$2y$10$LXz7KvcADrt8rxvU2wk3Ye0Sqd.Se0hhmRD1CAxf6g3tpN5/MwyFG',3);
-- Conductores reales del horario
INSERT INTO `conductores` (`usuario_id`,`nombre`,`apellido`,`telefono`) VALUES
(6,'Salvador','Aleman',  '7777-0001'),
(7,'Oscar',   'Hernandez','7777-0002'),
(8,'Manuel',  'Ramos',   '7777-0003');
-- Vehículos reales
INSERT INTO `unidades` (`nombre`,`placa`,`capacidad_maxima`) VALUES
('Coaster Hyundai','HYU-001',20),
('Coaster Nissan', 'NIS-001',20);

INSERT INTO `cronograma_horarios` (`id_sede_origen`, `id_sede_destino`, `dia_semana`, `hora_salida`, `turno`) VALUES
(2, 1, 'Lunes', '06:15:00', 'Matutino'),
(2, 1, 'Lunes', '07:00:00', 'Matutino'),
(2, 1, 'Lunes', '08:00:00', 'Matutino'),
(2, 1, 'Lunes', '09:25:00', 'Matutino'),
(2, 1, 'Lunes', '10:40:00', 'Matutino'),
(2, 1, 'Lunes', '12:10:00', 'Vespertino'),
(2, 1, 'Lunes', '12:20:00', 'Vespertino'),
(2, 1, 'Lunes', '14:00:00', 'Vespertino'),
(2, 1, 'Lunes', '15:30:00', 'Vespertino'),
(2, 1, 'Lunes', '16:00:00', 'Vespertino'),
(2, 1, 'Lunes', '16:20:00', 'Vespertino'),
-- martes
(2, 1, 'Martes', '06:15:00', 'Matutino'),
(2, 1, 'Martes', '07:00:00', 'Matutino'),
(2, 1, 'Martes', '08:00:00', 'Matutino'),
(2, 1, 'Martes', '09:25:00', 'Matutino'),
(2, 1, 'Martes', '10:40:00', 'Matutino'),
(2, 1, 'Martes', '12:10:00', 'Vespertino'),
(2, 1, 'Martes', '12:20:00', 'Vespertino'),
(2, 1, 'Martes', '14:00:00', 'Vespertino'),
(2, 1, 'Martes', '15:30:00', 'Vespertino'),
(2, 1, 'Martes', '16:00:00', 'Vespertino'),
(2, 1, 'Martes', '16:20:00', 'Vespertino'),
-- miercoles
(2, 1, 'Miercoles', '06:15:00', 'Matutino'),
(2, 1, 'Miercoles', '07:00:00', 'Matutino'),
(2, 1, 'Miercoles', '08:00:00', 'Matutino'),
(2, 1, 'Miercoles', '09:25:00', 'Matutino'),
(2, 1, 'Miercoles', '10:40:00', 'Matutino'),
(2, 1, 'Miercoles', '12:10:00', 'Vespertino'),
(2, 1, 'Miercoles', '12:20:00', 'Vespertino'),
(2, 1, 'Miercoles', '14:00:00', 'Vespertino'),
(2, 1, 'Miercoles', '15:30:00', 'Vespertino'),
(2, 1, 'Miercoles', '16:00:00', 'Vespertino'),
(2, 1, 'Miercoles', '16:20:00', 'Vespertino'),
-- jueves
(2, 1, 'Jueves', '06:15:00', 'Matutino'),
(2, 1, 'Jueves', '07:00:00', 'Matutino'),
(2, 1, 'Jueves', '08:00:00', 'Matutino'),
(2, 1, 'Jueves', '09:25:00', 'Matutino'),
(2, 1, 'Jueves', '10:40:00', 'Matutino'),
(2, 1, 'Jueves', '12:10:00', 'Vespertino'),
(2, 1, 'Jueves', '12:20:00', 'Vespertino'),
(2, 1, 'Jueves', '14:00:00', 'Vespertino'),
(2, 1, 'Jueves', '15:30:00', 'Vespertino'),
(2, 1, 'Jueves', '16:00:00', 'Vespertino'),
(2, 1, 'Jueves', '16:20:00', 'Vespertino'),
-- viernes
(2, 1, 'Viernes', '06:15:00', 'Matutino'),
(2, 1, 'Viernes', '07:00:00', 'Matutino'),
(2, 1, 'Viernes', '08:00:00', 'Matutino'),
(2, 1, 'Viernes', '09:25:00', 'Matutino'),
(2, 1, 'Viernes', '10:40:00', 'Matutino'),
(2, 1, 'Viernes', '12:10:00', 'Vespertino'),
(2, 1, 'Viernes', '12:20:00', 'Vespertino'),
(2, 1, 'Viernes', '14:00:00', 'Vespertino'),
(2, 1, 'Viernes', '15:30:00', 'Vespertino'),
(2, 1, 'Viernes', '16:00:00', 'Vespertino'),
(2, 1, 'Viernes', '16:20:00', 'Vespertino'),
-- Sábado
(2, 1, 'Sabado', '06:10:00', 'Matutino'),
(2, 1, 'Sabado', '07:00:00', 'Matutino'),
(2, 1, 'Sabado', '08:00:00', 'Matutino'),
(2, 1, 'Sabado', '09:25:00', 'Matutino'),
(2, 1, 'Sabado', '10:40:00', 'Matutino'),
(2, 1, 'Sabado', '12:20:00', 'Vespertino'),
(2, 1, 'Sabado', '16:20:00', 'Vespertino');

-- Sede Central a Ciudad Universitaria (L-V) 
INSERT INTO `cronograma_horarios` (`id_sede_origen`, `id_sede_destino`, `dia_semana`, `hora_salida`, `turno`) VALUES
(1, 2, 'Lunes', '06:40:00', 'Matutino'),
(1, 2, 'Lunes', '07:40:00', 'Matutino'),
(1, 2, 'Lunes', '08:40:00', 'Matutino'),
(1, 2, 'Lunes', '10:00:00', 'Matutino'),
(1, 2, 'Lunes', '11:10:00', 'Matutino'),
(1, 2, 'Lunes', '12:30:00', 'Vespertino'),
(1, 2, 'Lunes', '12:50:00', 'Vespertino'),
(1, 2, 'Lunes', '15:15:00', 'Vespertino'),
(1, 2, 'Lunes', '16:00:00', 'Vespertino'),
(1, 2, 'Lunes', '16:40:00', 'Vespertino'),
(1, 2, 'Lunes', '16:45:00', 'Vespertino'),
-- martes
(1, 2, 'Martes', '06:40:00', 'Matutino'),
(1, 2, 'Martes', '07:40:00', 'Matutino'),
(1, 2, 'Martes', '08:40:00', 'Matutino'),
(1, 2, 'Martes', '10:00:00', 'Matutino'),
(1, 2, 'Martes', '11:10:00', 'Matutino'),
(1, 2, 'Martes', '12:30:00', 'Vespertino'),
(1, 2, 'Martes', '12:50:00', 'Vespertino'),
(1, 2, 'Martes', '15:15:00', 'Vespertino'),
(1, 2, 'Martes', '16:00:00', 'Vespertino'),
(1, 2, 'Martes', '16:40:00', 'Vespertino'),
(1, 2, 'Martes', '16:45:00', 'Vespertino'),
-- miercoles
(1, 2, 'Miercoles', '06:40:00', 'Matutino'),
(1, 2, 'Miercoles', '07:40:00', 'Matutino'),
(1, 2, 'Miercoles', '08:40:00', 'Matutino'),
(1, 2, 'Miercoles', '10:00:00', 'Matutino'),
(1, 2, 'Miercoles', '11:10:00', 'Matutino'),
(1, 2, 'Miercoles', '12:30:00', 'Vespertino'),
(1, 2, 'Miercoles', '12:50:00', 'Vespertino'),
(1, 2, 'Miercoles', '15:15:00', 'Vespertino'),
(1, 2, 'Miercoles', '16:00:00', 'Vespertino'),
(1, 2, 'Miercoles', '16:40:00', 'Vespertino'),
(1, 2, 'Miercoles', '16:45:00', 'Vespertino'),
-- jueves
(1, 2, 'Jueves', '06:40:00', 'Matutino'),
(1, 2, 'Jueves', '07:40:00', 'Matutino'),
(1, 2, 'Jueves', '08:40:00', 'Matutino'),
(1, 2, 'Jueves', '10:00:00', 'Matutino'),
(1, 2, 'Jueves', '11:10:00', 'Matutino'),
(1, 2, 'Jueves', '12:30:00', 'Vespertino'),
(1, 2, 'Jueves', '12:50:00', 'Vespertino'),
(1, 2, 'Jueves', '15:15:00', 'Vespertino'),
(1, 2, 'Jueves', '16:00:00', 'Vespertino'),
(1, 2, 'Jueves', '16:40:00', 'Vespertino'),
(1, 2, 'Jueves', '16:45:00', 'Vespertino'),
-- viernes 
(1, 2, 'Viernes', '06:40:00', 'Matutino'),
(1, 2, 'Viernes', '07:40:00', 'Matutino'),
(1, 2, 'Viernes', '08:40:00', 'Matutino'),
(1, 2, 'Viernes', '10:00:00', 'Matutino'),
(1, 2, 'Viernes', '11:10:00', 'Matutino'),
(1, 2, 'Viernes', '12:30:00', 'Vespertino'),
(1, 2, 'Viernes', '12:50:00', 'Vespertino'),
(1, 2, 'Viernes', '15:15:00', 'Vespertino'),
(1, 2, 'Viernes', '16:00:00', 'Vespertino'),
(1, 2, 'Viernes', '16:40:00', 'Vespertino'),
(1, 2, 'Viernes', '16:45:00', 'Vespertino'),
-- Sábado
(1, 2, 'Sabado', '06:40:00', 'Matutino'),
(1, 2, 'Sabado', '07:40:00', 'Matutino'),
(1, 2, 'Sabado', '08:40:00', 'Matutino'),
(1, 2, 'Sabado', '10:00:00', 'Matutino'),
(1, 2, 'Sabado', '11:10:00', 'Matutino'),
(1, 2, 'Sabado', '12:50:00', 'Vespertino'),
(1, 2, 'Sabado', '16:45:00', 'Vespertino');
