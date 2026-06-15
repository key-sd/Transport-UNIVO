<?php
ini_set('display_errors', 0);
error_reporting(0);

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/El_Salvador');

require_once __DIR__ . '/../includes/conexion.php';
if (method_exists($conn, 'set_charset')) {
    $conn->set_charset('utf8mb4');
}

$id_conductor = isset($_GET['id_conductor']) ? intval($_GET['id_conductor']) : 0;

if ($id_conductor > 0) {
    $sql = "
        SELECT
            ac.id AS asig_id,
            ac.id_cronograma,
            ac.fecha_inicio,
            ac.fecha_fin,
            ch.dia_semana,
            ch.hora_salida,
            ch.turno,
            so.nombre AS sede_origen,
            sd.nombre AS sede_destino,
            u.id AS unidad_id,
            u.nombre AS unidad_nombre,
            u.placa
        FROM asignaciones_conductor ac
        INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
        INNER JOIN sedes so ON so.id = ch.id_sede_origen
        INNER JOIN sedes sd ON sd.id = ch.id_sede_destino
        INNER JOIN unidades u ON u.id = ac.id_unidad
        WHERE ac.id_conductor = ?
          AND ac.activo = 1
        ORDER BY
            CASE ch.dia_semana
                WHEN 'Lunes' THEN 1
                WHEN 'Martes' THEN 2
                WHEN 'Miercoles' THEN 3
                WHEN 'Jueves' THEN 4
                WHEN 'Viernes' THEN 5
                WHEN 'Sabado' THEN 6
                WHEN 'Domingo' THEN 7
                ELSE 8
            END,
            ch.hora_salida
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        responderJson(array('asignaciones' => array()));
        exit;
    }

    $stmt->bind_param('i', $id_conductor);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res) {
        responderJson(array('asignaciones' => array()));
        exit;
    }

    $asignaciones = array();
    while ($f = $res->fetch_assoc()) {
        $asignaciones[] = array(
            'asig_id' => (int) $f['asig_id'],
            'id_cronograma' => (int) $f['id_cronograma'],
            'sede_origen' => $f['sede_origen'],
            'sede_destino' => $f['sede_destino'],
            'dia_semana' => $f['dia_semana'],
            'hora_salida' => $f['hora_salida'],
            'turno' => $f['turno'],
            'unidad_id' => (int) $f['unidad_id'],
            'unidad' => $f['unidad_nombre'] . ' - ' . $f['placa'],
            'desde' => $f['fecha_inicio'],
            'hasta' => $f['fecha_fin'],
        );
    }

    responderJson(array('asignaciones' => $asignaciones));
    exit;
}

$sql = "
    SELECT
        c.id AS conductor_id,
        c.nombre,
        c.apellido,
        us.estado AS conductor_estado,
        so.nombre AS sede_origen,
        sd.nombre AS sede_destino,
        ch.dia_semana,
        u.nombre AS unidad_nombre,
        u.placa
    FROM conductores c
    INNER JOIN usuarios us ON us.id = c.usuario_id
    LEFT JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
    LEFT JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
    LEFT JOIN sedes so ON so.id = ch.id_sede_origen
    LEFT JOIN sedes sd ON sd.id = ch.id_sede_destino
    LEFT JOIN unidades u ON u.id = ac.id_unidad
    ORDER BY
        c.id,
        CASE ch.dia_semana
            WHEN 'Lunes' THEN 1
            WHEN 'Martes' THEN 2
            WHEN 'Miercoles' THEN 3
            WHEN 'Jueves' THEN 4
            WHEN 'Viernes' THEN 5
            WHEN 'Sabado' THEN 6
            WHEN 'Domingo' THEN 7
            ELSE 8
        END
";

$res = $conn->query($sql);
if (!$res) {
    responderJson(array());
    exit;
}

$orden_dias = array('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo');
$abrev = array(
    'Lunes' => 'Lun',
    'Martes' => 'Mar',
    'Miercoles' => 'Mié',
    'Jueves' => 'Jue',
    'Viernes' => 'Vie',
    'Sabado' => 'Sáb',
    'Domingo' => 'Dom',
);

$conductores = array();

while ($f = $res->fetch_assoc()) {
    $id = (int) $f['conductor_id'];

    if (!isset($conductores[$id])) {
        $conductores[$id] = array(
            'id' => $id,
            'nombre' => $f['nombre'] . ' ' . $f['apellido'],
            'estado' => (int) $f['conductor_estado'],
            'rutas' => array(),
            'dias' => array(),
            'unidades' => array(),
        );
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
        $unidad = $f['unidad_nombre'] . ' - ' . $f['placa'];
        if (!in_array($unidad, $conductores[$id]['unidades'])) {
            $conductores[$id]['unidades'][] = $unidad;
        }
    }
}

foreach ($conductores as &$c) {
    usort($c['dias'], function ($a, $b) use ($orden_dias) {
        return array_search($a, $orden_dias) - array_search($b, $orden_dias);
    });

    $dias_abrev = array();
    foreach ($c['dias'] as $dia) {
        $dias_abrev[] = isset($abrev[$dia]) ? $abrev[$dia] : $dia;
    }
    $c['dias_abrev'] = $dias_abrev;

    $rutas_fmt = array();
    foreach ($c['rutas'] as $ruta) {
        $partes = explode('|', $ruta);
        $origen = isset($partes[0]) ? $partes[0] : '';
        $destino = isset($partes[1]) ? $partes[1] : '';
        $fmt = abreviarSede($origen) . ' -> ' . abreviarSede($destino);
        if (!in_array($fmt, $rutas_fmt)) {
            $rutas_fmt[] = $fmt;
        }
    }
    $c['rutas_fmt'] = $rutas_fmt;
    $c['total_asignaciones'] = count($c['rutas']);
    unset($c['rutas']);
}
unset($c);

responderJson(array_values($conductores));

function abreviarSede($nombre)
{
    if ($nombre === '') {
        return '-';
    }

    $map = array(
        'Sede Central' => 'Sede C.',
        'Ciudad Universitaria' => 'C. Univ.',
        'Campus Agronomía y Veterinaria' => 'Agronomía',
    );

    if (isset($map[$nombre])) {
        return $map[$nombre];
    }

    $partes = explode(' ', $nombre);
    return isset($partes[0]) ? $partes[0] : $nombre;
}

function responderJson($data)
{
    $json = json_encode($data);
    if ($json !== false) {
        echo $json;
        return;
    }

    array_walk_recursive($data, function (&$value) {
        if (is_string($value)) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            $value = $converted === false ? '' : $converted;
        }
    });

    $json = json_encode($data);
    echo $json === false ? '[]' : $json;
}
