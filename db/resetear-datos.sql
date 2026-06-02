use db_transport_univo;

-- Eliminar datos de las tablas para resetear la base de datos
DELETE FROM `unidades`;
DELETE FROM `asignaciones_conductor`;
DELETE FROM `conductores`;
DELETE FROM `usuarios`;
DELETE FROM `cronograma_horarios`;

ALTER TABLE `unidades` AUTO_INCREMENT = 1;
ALTER TABLE `asignaciones_conductor` AUTO_INCREMENT = 1;
ALTER TABLE `conductores` AUTO_INCREMENT = 1;
ALTER TABLE `usuarios` AUTO_INCREMENT = 1;
ALTER TABLE `cronograma_horarios` AUTO_INCREMENT = 1;