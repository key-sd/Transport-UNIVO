<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

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
    GROUP BY ch.id_sede_origen, ch.id_sede_destino, ch.dia_semana
    ORDER BY ch.id_sede_origen, ch.id_sede_destino, FIELD(ch.dia_semana,
        'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')
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