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

$cronogramas = array();
$conductores = array();
$unidades = array();
$rutas = array();

$sqlCronogramas = "
    SELECT
        ch.id,
        ch.id_sede_origen,
        ch.id_sede_destino,
        so.nombre AS origen,
        sd.nombre AS destino,
        ch.dia_semana,
        ch.hora_salida,
        ch.turno,
        (
            SELECT COUNT(*)
            FROM asignaciones_conductor ac
            WHERE ac.id_cronograma = ch.id AND ac.activo = 1
        ) AS tiene_asignacion
    FROM cronograma_horarios ch
    INNER JOIN sedes so ON so.id = ch.id_sede_origen
    INNER JOIN sedes sd ON sd.id = ch.id_sede_destino
    WHERE ch.estado = 1
    ORDER BY
        so.nombre,
        sd.nombre,
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

$res = $conn->query($sqlCronogramas);
if ($res) {
    while ($f = $res->fetch_assoc()) {
        $item = array(
            'id' => (int) $f['id'],
            'id_sede_origen' => (int) $f['id_sede_origen'],
            'id_sede_destino' => (int) $f['id_sede_destino'],
            'origen' => $f['origen'],
            'destino' => $f['destino'],
            'dia_semana' => $f['dia_semana'],
            'hora_salida' => $f['hora_salida'],
            'turno' => $f['turno'],
            'tiene_asignacion' => ((int) $f['tiene_asignacion']) > 0,
        );

        $cronogramas[] = $item;

        $key = $item['id_sede_origen'] . '-' . $item['id_sede_destino'];
        if (!isset($rutas[$key])) {
            $rutas[$key] = array(
                'id_sede_origen' => $item['id_sede_origen'],
                'id_sede_destino' => $item['id_sede_destino'],
                'origen' => $item['origen'],
                'destino' => $item['destino'],
                'label' => $item['origen'] . ' -> ' . $item['destino'],
            );
        }
    }
}

$res = $conn->query("
    SELECT c.id, CONCAT(c.nombre, ' ', c.apellido) AS nombre_completo
    FROM conductores c
    INNER JOIN usuarios u ON u.id = c.usuario_id
    WHERE u.estado = 1
    ORDER BY c.nombre, c.apellido
");
if ($res) {
    while ($f = $res->fetch_assoc()) {
        $conductores[] = $f;
    }
}

$res = $conn->query("
    SELECT id, CONCAT(nombre, ' - ', placa) AS etiqueta
    FROM unidades
    WHERE estado = 1
    ORDER BY nombre
");
if ($res) {
    while ($f = $res->fetch_assoc()) {
        $unidades[] = $f;
    }
}

responderJson(array(
    'cronogramas' => $cronogramas,
    'conductores' => $conductores,
    'unidades' => $unidades,
    'rutas' => array_values($rutas),
));

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
    echo $json === false ? '{"cronogramas":[],"conductores":[],"unidades":[],"rutas":[]}' : $json;
}
