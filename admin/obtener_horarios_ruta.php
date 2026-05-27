<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');
/* Devuelve los horarios registrados para una ruta específica (origen → destino), agrupados por día de la semana.*/

$origen  = intval($_GET['origen']  ?? 0);
$destino = intval($_GET['destino'] ?? 0);

if ($origen <= 0 || $destino <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

$orden_dias = "FIELD(dia_semana,'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')";

$stmt = $conn->prepare("
    SELECT id, dia_semana, hora_salida, turno, estado
    FROM cronograma_horarios
    WHERE id_sede_origen = ? AND id_sede_destino = ?
    ORDER BY {$orden_dias}, hora_salida ASC
");
$stmt->bind_param('ii', $origen, $destino);
$stmt->execute();
$res = $stmt->get_result();

$dias = [];
while ($fila = $res->fetch_assoc()) {
    $dia = $fila['dia_semana'];
    if (!isset($dias[$dia])) $dias[$dia] = [];
    $dias[$dia][] = [
        'id'          => (int) $fila['id'],
        'hora_salida' => substr($fila['hora_salida'], 0, 5), // HH:MM
        'turno'       => $fila['turno'],
        'estado'      => (int) $fila['estado'],
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'origen'  => $origen,
    'destino' => $destino,
    'dias'    => $dias,
]);
