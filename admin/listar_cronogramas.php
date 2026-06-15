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

$sql = "
    SELECT
        ch.id_sede_origen,
        ch.id_sede_destino,
        so.nombre AS origen,
        sd.nombre AS destino,
        ch.dia_semana,
        MIN(ch.estado) AS estado
    FROM cronograma_horarios ch
    INNER JOIN sedes so ON so.id = ch.id_sede_origen
    INNER JOIN sedes sd ON sd.id = ch.id_sede_destino
    GROUP BY
        ch.id_sede_origen,
        ch.id_sede_destino,
        so.nombre,
        sd.nombre,
        ch.dia_semana
    ORDER BY
        ch.id_sede_origen,
        ch.id_sede_destino,
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

$resultado = $conn->query($sql);
if (!$resultado) {
    responderJson(array());
    exit;
}

$rutas = array();
while ($fila = $resultado->fetch_assoc()) {
    $key = $fila['id_sede_origen'] . '-' . $fila['id_sede_destino'];

    if (!isset($rutas[$key])) {
        $rutas[$key] = array(
            'ruta_key' => $key,
            'id_sede_origen' => (int) $fila['id_sede_origen'],
            'id_sede_destino' => (int) $fila['id_sede_destino'],
            'origen' => $fila['origen'],
            'destino' => $fila['destino'],
            'dias' => array(),
            'estado' => (int) $fila['estado'],
        );
    }

    $rutas[$key]['dias'][] = $fila['dia_semana'];
}

responderJson(array_values($rutas));

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
