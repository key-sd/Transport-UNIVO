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

$origen = isset($_GET['origen']) ? intval($_GET['origen']) : 0;
$destino = isset($_GET['destino']) ? intval($_GET['destino']) : 0;

if ($origen <= 0 || $destino <= 0) {
    responderJson(array(
        'success' => false,
        'message' => 'Parametros invalidos.',
        'dias' => array(),
    ));
    exit;
}

$sql = "
    SELECT id, dia_semana, hora_salida, turno, estado
    FROM cronograma_horarios
    WHERE id_sede_origen = ? AND id_sede_destino = ?
    ORDER BY
        CASE dia_semana
            WHEN 'Lunes' THEN 1
            WHEN 'Martes' THEN 2
            WHEN 'Miércoles' THEN 3
            WHEN 'Jueves' THEN 4
            WHEN 'Viernes' THEN 5
            WHEN 'Sábado' THEN 6
            WHEN 'Domingo' THEN 7
            ELSE 8
        END,
        hora_salida ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    responderJson(array('success' => false, 'message' => 'Error al preparar consulta.', 'dias' => array()));
    exit;
}

$stmt->bind_param('ii', $origen, $destino);
$stmt->execute();
$res = $stmt->get_result();

if (!$res) {
    responderJson(array('success' => false, 'message' => 'Error al cargar horarios.', 'dias' => array()));
    exit;
}

$dias = array();
while ($fila = $res->fetch_assoc()) {
    $dia = $fila['dia_semana'];
    if (!isset($dias[$dia])) {
        $dias[$dia] = array();
    }

    $dias[$dia][] = array(
        'id' => (int) $fila['id'],
        'hora_salida' => substr($fila['hora_salida'], 0, 5),
        'turno' => $fila['turno'],
        'estado' => (int) $fila['estado'],
    );
}

responderJson(array(
    'success' => true,
    'origen' => $origen,
    'destino' => $destino,
    'dias' => $dias,
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
    echo $json === false ? '{"success":false,"dias":[]}' : $json;
}
