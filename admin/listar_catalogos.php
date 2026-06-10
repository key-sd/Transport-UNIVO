<?php
/*muestra los catálogos necesarios para el modal de asignaciones*/
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

/* Cronogramas agrupados por ruta */
// Se devuelve la estructura completa para que el JS pueda filtrar por ruta seleccionada
$cronogramas = [];
$res = $conn->query("
    SELECT
        ch.id,
        ch.id_sede_origen,
        ch.id_sede_destino,
        so.nombre   AS origen,
        sd.nombre   AS destino,
        ch.dia_semana,
        ch.hora_salida,
        ch.turno,
        -- saber si ya tiene asignación activa hoy
        (SELECT COUNT(*) FROM asignaciones_conductor ac
         WHERE ac.id_cronograma = ch.id AND ac.activo = 1) AS tiene_asignacion
    FROM cronograma_horarios ch
    JOIN sedes so ON so.id = ch.id_sede_origen
    JOIN sedes sd ON sd.id = ch.id_sede_destino
    WHERE ch.estado = 1
    ORDER BY so.nombre, sd.nombre,
             FIELD(ch.dia_semana,'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'),
             ch.hora_salida
");

while ($f = $res->fetch_assoc()) {
    $cronogramas[] = [
        'id'               => (int) $f['id'],
        'id_sede_origen'   => (int) $f['id_sede_origen'],
        'id_sede_destino'  => (int) $f['id_sede_destino'],
        'origen'           => $f['origen'],
        'destino'          => $f['destino'],
        'dia_semana'       => $f['dia_semana'],
        'hora_salida'      => $f['hora_salida'],
        'turno'            => $f['turno'],
        'tiene_asignacion' => (int) $f['tiene_asignacion'] > 0,
    ];
}

/* Conductores activos */
$conductores = [];
$res = $conn->query(
    "SELECT c.id, CONCAT(c.nombre,' ',c.apellido) AS nombre_completo
     FROM conductores c
     INNER JOIN usuarios u ON u.id = c.usuario_id
     WHERE u.estado = 1
     ORDER BY c.nombre, c.apellido"
);
while ($f = $res->fetch_assoc()) $conductores[] = $f;

/* Unidades activas */
$unidades = [];
$res = $conn->query(
    "SELECT id, CONCAT(nombre,' — ',placa) AS etiqueta
     FROM unidades WHERE estado = 1 ORDER BY nombre"
);
while ($f = $res->fetch_assoc()) $unidades[] = $f;

if ($conn->errno) {
    echo json_encode(['error' => $conn->error]);
    exit;
}

/* Rutas únicas (para el selector de ruta) */
$rutas = [];
foreach ($cronogramas as $c) {
    $key = $c['id_sede_origen'] . '-' . $c['id_sede_destino'];
    if (!isset($rutas[$key])) {
        $rutas[$key] = [
            'id_sede_origen'  => $c['id_sede_origen'],
            'id_sede_destino' => $c['id_sede_destino'],
            'origen'          => $c['origen'],
            'destino'         => $c['destino'],
            'label'           => $c['origen'] . ' → ' . $c['destino'],
        ];
    }
}

echo json_encode([
    'cronogramas' => $cronogramas,
    'conductores' => $conductores,
    'unidades'    => $unidades,
    'rutas'       => array_values($rutas),
]);
