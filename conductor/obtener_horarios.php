<?php
session_start();

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

// Se agrega LEFT JOIN con viajes para obtener el estado_recorrido real del día
// Si el viaje no existe aún en la tabla viajes, estado_recorrido llega como NULL
$sql = "
SELECT
    ch.hora_salida                  AS hora_salida,
    so.nombre                       AS origen,
    sd.nombre                       AS destino,
    COALESCE(v.estado_recorrido, '') AS estado_recorrido
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
    $horarios[] = $fila;
}

echo json_encode($horarios);

$stmt->close();
$conn->close();