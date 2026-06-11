<?php
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once '../includes/conexion.php';

set_exception_handler(function () {
    http_response_code(500);
    echo json_encode([]);
    exit;
});

$id_conductor = isset($_GET['id_conductor']) ? intval($_GET['id_conductor']) : 0;

// DETALLE: asignaciones activas de un conductor
if ($id_conductor > 0) {

    $sql = "
        SELECT
            ac.id               AS asig_id,
            ac.id_cronograma,
            ac.fecha_inicio,
            ac.fecha_fin,
            ch.dia_semana,
            ch.hora_salida,
            ch.turno,
            so.nombre           AS sede_origen,
            sd.nombre           AS sede_destino,
            u.id                AS unidad_id,
            u.nombre            AS unidad_nombre,
            u.placa
        FROM asignaciones_conductor ac
        JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
        JOIN sedes so               ON so.id = ch.id_sede_origen
        JOIN sedes sd               ON sd.id = ch.id_sede_destino
        JOIN unidades u             ON u.id  = ac.id_unidad
        WHERE ac.id_conductor = ?
          AND ac.activo = 1
        ORDER BY
            CASE ch.dia_semana
                WHEN 'Lunes' THEN 1
                WHEN 'Martes' THEN 2
                WHEN 'Miércoles' THEN 3
                WHEN 'Jueves' THEN 4
                WHEN 'Viernes' THEN 5
                WHEN 'Sábado' THEN 6
                WHEN 'Domingo' THEN 7
                ELSE 8
            END,
            ch.hora_salida
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['asignaciones' => []]); exit; }

    $stmt->bind_param('i', $id_conductor);
    $stmt->execute();
    $res = $stmt->get_result();

    $asignaciones = [];
    while ($f = $res->fetch_assoc()) {
        $asignaciones[] = [
            'asig_id'      => (int) $f['asig_id'],
            'id_cronograma'=> (int) $f['id_cronograma'],
            'sede_origen'  => $f['sede_origen'],
            'sede_destino' => $f['sede_destino'],
            'dia_semana'   => $f['dia_semana'],
            'hora_salida'  => $f['hora_salida'],
            'turno'        => $f['turno'],
            'unidad_id'    => (int) $f['unidad_id'],
            'unidad'       => $f['unidad_nombre'] . ' · ' . $f['placa'],
            'desde'        => $f['fecha_inicio'],
            'hasta'        => $f['fecha_fin'],
        ];
    }
    $stmt->close();

    echo json_encode(['asignaciones' => $asignaciones]);
    exit;
}

// LISTADO GENERAL — un objeto por conductor con resumen
$sql = "
    SELECT
        c.id            AS conductor_id,
        c.nombre,
        c.apellido,
        us.estado        AS conductor_estado,
        so.nombre       AS sede_origen,
        sd.nombre       AS sede_destino,
        ch.dia_semana,  
        u.nombre        AS unidad_nombre,
        u.placa
    FROM conductores c
    INNER JOIN usuarios us ON us.id = c.usuario_id
    LEFT JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
    LEFT JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
    LEFT JOIN sedes so ON so.id = ch.id_sede_origen
    LEFT JOIN sedes sd ON sd.id = ch.id_sede_destino
    LEFT JOIN unidades u ON u.id  = ac.id_unidad
    ORDER BY c.id,
             CASE ch.dia_semana
                WHEN 'Lunes' THEN 1
                WHEN 'Martes' THEN 2
                WHEN 'Miércoles' THEN 3
                WHEN 'Jueves' THEN 4
                WHEN 'Viernes' THEN 5
                WHEN 'Sábado' THEN 6
                WHEN 'Domingo' THEN 7
                ELSE 8
             END
";

$res = $conn->query($sql);
if (!$res) { http_response_code(500); echo json_encode([]); exit; }

$orden_dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$abrev      = ['Lunes'=>'Lun','Martes'=>'Mar','Miércoles'=>'Mié','Jueves'=>'Jue',
                'Viernes'=>'Vie','Sábado'=>'Sáb','Domingo'=>'Dom'];

$conductores = [];

while ($f = $res->fetch_assoc()) {
    $id = (int) $f['conductor_id'];

    if (!isset($conductores[$id])) {
        $conductores[$id] = [
            'id'       => $id,
            'nombre'   => $f['nombre'] . ' ' . $f['apellido'],
            'estado'   => (int) $f['conductor_estado'],
            'rutas'    => [],
            'dias'     => [],
            'unidades' => [],
        ];
    }

    if ($f['sede_origen'] && $f['sede_destino']) {
        $ruta = $f['sede_origen'] . '|' . $f['sede_destino'];
        if (!in_array($ruta, $conductores[$id]['rutas'])) {
            $conductores[$id]['rutas'][] = $ruta;
        }
    }

    if ($f['dia_semana'] && !in_array($f['dia_semana'], $conductores[$id]['dias'])) {
        $conductores[$id]['dias'][] = $f['dia_semana'];
    }

    if ($f['unidad_nombre'] && $f['placa']) {
        $u = $f['unidad_nombre'] . ' · ' . $f['placa'];
        if (!in_array($u, $conductores[$id]['unidades'])) {
            $conductores[$id]['unidades'][] = $u;
        }
    }
}

foreach ($conductores as &$c) {
    usort($c['dias'], function ($a, $b) use ($orden_dias) {
        return array_search($a, $orden_dias) - array_search($b, $orden_dias);
    });
    $c['dias_abrev'] = array_map(function ($d) use ($abrev) {
        return $abrev[$d] ?? $d;
    }, $c['dias']);

    $rutas_fmt = [];
    foreach ($c['rutas'] as $r) {
        list($origen, $destino) = explode('|', $r);
        $fmt = abreviarSede($origen) . ' → ' . abreviarSede($destino);
        if (!in_array($fmt, $rutas_fmt)) $rutas_fmt[] = $fmt;
    }
    $c['rutas_fmt'] = $rutas_fmt;

    // contar asignaciones activas
    $c['total_asignaciones'] = count($c['rutas']); // rutas únicas activas
    unset($c['rutas']);
}
unset($c);

echo json_encode(array_values($conductores));

function abreviarSede($nombre = '') {
    if ($nombre === '') return '—';
    $map = [
        'Sede Central'                   => 'Sede C.',
        'Ciudad Universitaria'           => 'C. Univ.',
        'Campus Agronomía y Veterinaria' => 'Agronomía',
    ];
    return isset($map[$nombre]) ? $map[$nombre] : explode(' ', $nombre)[0];
}
