<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'pasajero') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit();
}

require_once '../includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$dias = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo'
];
$dia_hoy = $dias[date('N')];

// Consulta de horarios con asignación y estado de viaje/GPS en tiempo real
$sql = "
SELECT
    ch.id AS id_cronograma,
    ch.hora_salida,
    ch.dia_semana,
    so.nombre AS origen,
    so.id AS origen_id,
    sd.nombre AS destino,
    sd.id AS destino_id,
    ch.turno,
    ac.id AS id_asignacion,
    c.id AS conductor_id,
    CONCAT(c.nombre, ' ', c.apellido) AS conductor_nombre,
    c.telefono AS conductor_telefono,
    u.id AS unidad_id,
    u.nombre AS unidad_nombre,
    u.placa AS unidad_placa,
    v.estado_recorrido,
    v.estado_unidad AS capacidad,
    v.hora_salida_real,
    ub.latitud,
    ub.longitud,
    ub.actualizado_en AS ubicacion_actualizado,
    TIMESTAMPDIFF(SECOND, ub.actualizado_en, NOW()) AS ubicacion_edad_seg
FROM cronograma_horarios ch
INNER JOIN sedes so ON so.id = ch.id_sede_origen
INNER JOIN sedes sd ON sd.id = ch.id_sede_destino
/* Solo horarios con asignación activa y conductor activo */
INNER JOIN asignaciones_conductor ac ON  ac.id_cronograma = ch.id AND ac.activo = 1
INNER JOIN conductores c ON c.id = ac.id_conductor
INNER JOIN usuarios usr ON usr.id = c.usuario_id AND usr.estado = 1
INNER JOIN unidades u ON u.id = ac.id_unidad
LEFT JOIN viajes v ON  v.id_asignacion = ac.id AND v.fecha = CURRENT_DATE()
LEFT JOIN (
    SELECT u1.id_conductor, u1.latitud, u1.longitud, u1.actualizado_en
    FROM   ubicaciones u1
    INNER JOIN (
        SELECT id_conductor, MAX(actualizado_en) AS max_ts
        FROM   ubicaciones
        GROUP  BY id_conductor
    ) u2 ON u1.id_conductor = u2.id_conductor
        AND u1.actualizado_en = u2.max_ts
) ub ON ub.id_conductor = c.id
WHERE ch.dia_semana = ? AND ch.estado     = 1
ORDER BY ch.hora_salida ASC
";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $dia_hoy);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $cronogramas = [];
    while ($row = $resultado->fetch_assoc()) {
        $cronogramas[] = [
            'id_cronograma'   => (int) $row['id_cronograma'],
            'hora_salida'     => $row['hora_salida'],
            'dia_semana'      => $row['dia_semana'],
            'origen'          => $row['origen'],
            'origen_id'       => (int) $row['origen_id'],
            'destino'         => $row['destino'],
            'destino_id'      => (int) $row['destino_id'],
            'turno'           => $row['turno'],
            'id_asignacion'   =>(int) $row['id_asignacion'],
            'conductor'       => [
                'id'       => (int) $row['conductor_id'],
                'nombre'   => $row['conductor_nombre'],
                'telefono' => $row['conductor_telefono'],
            ],
            'unidad'           => [
                'id'     => (int) $row['unidad_id'],
                'nombre' => $row['unidad_nombre'],
                'placa'  => $row['unidad_placa'],
            ],
            'estado_recorrido'=> $row['estado_recorrido'] ?? 'pendiente',
            'capacidad'       => $row['capacidad'] ?? 'desconocida',
            'hora_salida_real'=> $row['hora_salida_real'],
            'gps'             => ($row['latitud'] !== null) ? [
                'lat'            => (float) $row['latitud'],
                'lng'            => (float) $row['longitud'],
                'actualizado_en' => $row['ubicacion_actualizado'],
                'edad_seg'       => (int) $row['ubicacion_edad_seg'],
            ] : null,
        ];
    }

    echo json_encode($cronogramas);
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conexion->close();
