use transport_univo;

INSERT INTO `usuarios` (`codigo_universitario`, `password_hash`, `rol_id`) VALUES 
('u2026001','$2y$10$vqyXr8as1lK//K.JQF3Q2uxC9h4ivZl4Ijj.FfJfcyw.aCIqDA1Pi', 1), 
('u2026002','$2y$10$kmxPL1U7kDc59MM71rr4rO5yQOyJAfxYpaViQglHvXZ9kNrmOcN8G', 2), 
('u2026003','$2y$10$LXz7KvcADrt8rxvU2wk3Ye0Sqd.Se0hhmRD1CAxf6g3tpN5/MwyFG', 3);

INSERT INTO `conductores` (`usuario_id`, `nombre`, `apellido`, `telefono`) VALUES 
(3, 'Juan', 'Pérez', '555-1234');