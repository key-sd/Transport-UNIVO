use transport_univo;

INSERT INTO `usuarios` (`codigo_universitario`, `password_hash`, `rol_id`) VALUES 
('u2026001','adminpass', 1), 
('u2026002','pasajeropass', 2), 
('u2026003','conductorpass', 3);

INSERT INTO `conductores` (`usuario_id`, `nombre`, `apellido`, `telefono`) VALUES 
(3, 'Juan', 'Pérez', '555-1234');