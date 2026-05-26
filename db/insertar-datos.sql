use db_transport_univo;

-- VALORES INSERTADOS
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

--  cronograma de horarios — Martes, Miércoles y Jueves completos
--  Rutas: 1=SC→CU  2=CU→SC  3=SC→Agro  4=Agro→SC

-- RUTA 1: Sede Central → Ciudad Universitaria 
INSERT INTO `cronograma_horarios`
  (`id_sede_origen`,`id_sede_destino`,`dia_semana`,`hora_salida`,`turno`) VALUES
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
INSERT INTO `cronograma_horarios`
  (`id_sede_origen`,`id_sede_destino`,`dia_semana`,`hora_salida`,`turno`) VALUES
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
INSERT INTO `cronograma_horarios`
  (`id_sede_origen`,`id_sede_destino`,`dia_semana`,`hora_salida`,`turno`) VALUES
(1,3,'Martes','07:15','Matutino'),
(1,3,'Martes','12:20','Vespertino'),
(1,3,'Miércoles','07:15','Matutino'),
(1,3,'Miércoles','12:20','Vespertino'),
(1,3,'Jueves',   '07:15','Matutino'),
(1,3,'Jueves',   '12:20','Vespertino');

-- RUTA 4: Campus Agronomía → Sede Central 
INSERT INTO `cronograma_horarios`
  (`id_sede_origen`,`id_sede_destino`,`dia_semana`,`hora_salida`,`turno`) VALUES
(3,1,'Martes',   '11:20','Matutino'),
(3,1,'Martes',   '16:20','Vespertino'),
(3,1,'Miércoles','11:20','Matutino'),
(3,1,'Miércoles','16:20','Vespertino'),
(3,1,'Jueves',   '11:20','Matutino'),
(3,1,'Jueves',   '16:20','Vespertino');

--  HORARIOS DE TRABAJO Y ASIGNACIONES

-- Horario A: Levi cubre SC↔CU de lunes a sábado mañana+tarde
INSERT INTO `horarios_conductor`
  (`id_unidad`,`dia_semana`,`hora_inicio`,`hora_fin`) VALUES
(1,'Martes','06:00','17:30'),
(1,'Miércoles','06:00','17:30'),
(1,'Jueves','06:00','17:30');

-- Horario B: Daniela cubre ruta Agro (solo Ma-V)
INSERT INTO `horarios_conductor`
  (`id_unidad`,`dia_semana`,`hora_inicio`,`hora_fin`) VALUES
(2,'Martes',   '07:00','17:00'),
(2,'Miércoles','07:00','17:00'),
(2,'Jueves',   '07:00','17:00');

-- Asignaciones
INSERT INTO `asignaciones_conductor`
  (`id_horario`,`id_conductor`,`fecha_inicio`) VALUES
-- Levi cubre horarios 
(1,1,'2026-05-26'), 
(2,1,'2026-05-26'), 
(3,1,'2026-05-26'), 
-- Daniela cubre horarios agro
(4,2,'2026-05-26'), 
(5,2,'2026-05-26'), 
(6,2,'2026-05-26'); 