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

/* Devuelve los cronogramas agrupados por ruta (origen → destino). Cada ruta incluye los días que tiene registrados.*/
$sql = "
    SELECT
        ch.id_sede_origen,
        ch.id_sede_destino,
        so.nombre  AS origen,
        sd.nombre  AS destino,
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
            WHEN 'Miércoles' THEN 3
            WHEN 'Jueves' THEN 4
            WHEN 'Viernes' THEN 5
            WHEN 'Sábado' THEN 6
            WHEN 'Domingo' THEN 7
            ELSE 8
        END
";

$resultado = $conn->query($sql);

if (!$resultado) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

// Agrupar por ruta (origen+destino)
$rutas = [];
while ($fila = $resultado->fetch_assoc()) {
    $key = $fila['id_sede_origen'] . '-' . $fila['id_sede_destino'];
    if (!isset($rutas[$key])) {
        $rutas[$key] = [
            'ruta_key'        => $key,
            'id_sede_origen'  => (int) $fila['id_sede_origen'],
            'id_sede_destino' => (int) $fila['id_sede_destino'],
            'origen'          => $fila['origen'],
            'destino'         => $fila['destino'],
            'dias'            => [],
            'estado'          => (int) $fila['estado'],
        ];
    }
    $rutas[$key]['dias'][] = $fila['dia_semana'];
}

echo json_encode(array_values($rutas));
