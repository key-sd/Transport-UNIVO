<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

include("../includes/conexion.php");

header('Content-Type: application/json; charset=utf-8');

$dias = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo'];
$dia_hoy = $dias[date('N')];

$conductor_id = $_SESSION['usuario_id'];

$sql = "
    SELECT
        hs.hora AS hora_salida,
        s_origen.nombre AS origen,
        s_destino.nombre AS destino
    FROM cronograma_horarios ch
    INNER JOIN horas_salida hs ON hs.id = ch.id_hora_salida
    INNER JOIN rutas r ON r.id = ch.id_ruta
    INNER JOIN sedes s_origen ON s_origen.id = r.id_sede_origen
    INNER JOIN sedes s_destino ON s_destino.id = r.id_sede_destino
    INNER JOIN conductores c ON c.id = ch.conductor_id
    WHERE ch.dia_semana = ?
    AND c.usuario_id = ?
    AND ch.estado = 1
    ORDER BY hs.hora ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $dia_hoy, $conductor_id);
$stmt->execute();
$resultado = $stmt->get_result();

$horarios = [];
while ($fila = $resultado->fetch_assoc()) {
    $horarios[] = $fila;
}

$stmt->close();
echo json_encode($horarios);