use Transport_univo;

-- Eliminar datos de las tablas para resetear la base de datos
DELETE FROM `conductores`;
DELETE FROM `usuarios`;
DELETE FROM `microbuses`;
DELETE FROM `horarios`;
DELETE FROM `rutas`;
-- Se resetea el auto_increment para que los IDs comiencen desde 1 nuevamente
ALTER TABLE `conductores` AUTO_INCREMENT = 1;
ALTER TABLE `usuarios` AUTO_INCREMENT = 1;
ALTER TABLE `microbuses` AUTO_INCREMENT = 1;
ALTER TABLE `horarios` AUTO_INCREMENT = 1;
ALTER TABLE `rutas` AUTO_INCREMENT = 1;
