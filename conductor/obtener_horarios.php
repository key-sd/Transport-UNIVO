<?php
session_start();
date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

include("../includes/conexion.php");

$dias = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo'
];

$dia_hoy    = $dias[date('N')];
$fecha_hoy  = date('Y-m-d');
$usuario_id = $_SESSION['usuario_id'];

// NOTA: se usa NULL cuando no hay registro en viajes (COALESCE devuelve NULL, no '').
// El JS interpreta NULL/vacío como "pendiente" y cualquier valor de estado_recorrido
// como el estado real del viaje (en_sede, proximo_salir, en_camino, llegando, completado).
$sql = "
SELECT
    ac.id                                AS id_asignacion,
    ch.hora_salida                       AS hora_salida,
    so.nombre                            AS origen,
    sd.nombre                            AS destino,
    v.estado_recorrido                   AS estado_recorrido
FROM conductores c
INNER JOIN asignaciones_conductor ac
    ON ac.id_conductor = c.id AND ac.activo = 1
INNER JOIN cronograma_horarios ch
    ON ch.id = ac.id_cronograma AND ch.dia_semana = ? AND ch.estado = 1
INNER JOIN sedes so
    ON so.id = ch.id_sede_origen
INNER JOIN sedes sd
    ON sd.id = ch.id_sede_destino
LEFT JOIN viajes v
    ON v.id_asignacion = ac.id AND v.fecha = ?
WHERE c.usuario_id = ?
ORDER BY ch.hora_salida ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $dia_hoy, $fecha_hoy, $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$horarios  = [];

while ($fila = $resultado->fetch_assoc()) {
    // estado_recorrido llega como NULL si no hay registro en viajes.
    // Lo normalizamos a string vacío para que el JS pueda comparar fácilmente.
    $fila['estado_recorrido'] = $fila['estado_recorrido'] ?? '';
    $horarios[] = $fila;
}

echo json_encode($horarios);

$stmt->close();
$conn->close();