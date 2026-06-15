<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

// TEMPORAL
echo json_encode([
    'session' => $_SESSION,
    'rol' => $_SESSION['rol'] ?? 'NO EXISTE'
]);
exit;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$origen  = intval($_POST['id_sede_origen']  ?? 0);
$destino = intval($_POST['id_sede_destino'] ?? 0);
$dias    = $_POST['dias']  ?? [];   // array: ['Martes','Jueves',...]
$horas   = $_POST['horas'] ?? [];   // array: ['06:40','12:30',...]

// DEBUG TEMPORAL - borrar después
error_log('DIAS RECIBIDOS: ' . print_r($dias, true));
error_log('HORAS RECIBIDAS: ' . print_r($horas, true));
echo json_encode(['debug' => true, 'dias' => $dias, 'horas' => $horas, 'origen' => $origen, 'destino' => $destino]);
exit;

/* Validaciones básicas */
$errores = [];
if ($origen  <= 0)           $errores[] = 'El origen es obligatorio.';
if ($destino <= 0)           $errores[] = 'El destino es obligatorio.';
if ($origen  === $destino)   $errores[] = 'El origen y destino no pueden ser iguales.';
if (empty($dias))            $errores[] = 'Selecciona al menos un día.';
if (empty($horas))           $errores[] = 'Agrega al menos un horario de salida.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

$dias_validos = ['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'];

$insertados = 0;
$omitidos   = 0;
$err_msgs   = [];

foreach ($dias as $dia) {
    $dia = trim($dia);
    if (!in_array($dia, $dias_validos)) continue;

    foreach ($horas as $hora_salida) {
        $hora_salida = trim($hora_salida);
        if (empty($hora_salida)) continue;

        /* validar rango */
        $t    = strtotime($hora_salida);
        $tmin = strtotime('06:00');
        $tmax = strtotime('23:59');
        if ($t === false || $t < $tmin || $t > $tmax) {
            $err_msgs[] = "La hora {$hora_salida} está fuera del rango permitido (6:00 AM – 11:59 PM).";
            $omitidos++;
            continue;
        }

        /* calcular turno */
        $turno = ($t < strtotime('12:00')) ? 'Matutino' : 'Vespertino';

        /* verificar duplicado */
        $stmtDup = $conn->prepare(
            "SELECT id FROM cronograma_horarios
             WHERE id_sede_origen = ? AND id_sede_destino = ?
               AND dia_semana = ? AND hora_salida = ?
             LIMIT 1"
        );
        $stmtDup->bind_param('iiss', $origen, $destino, $dia, $hora_salida);
        $stmtDup->execute();
        $stmtDup->store_result();
        $existe = $stmtDup->num_rows > 0;
        $stmtDup->close();

        if ($existe) {
            $omitidos++;
            continue; // silencioso — ya existía
        }

        /* insertar */
        try {
            $stmt = $conn->prepare(
                "INSERT INTO cronograma_horarios
                 (id_sede_origen, id_sede_destino, dia_semana, hora_salida, turno)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iisss', $origen, $destino, $dia, $hora_salida, $turno);
            $stmt->execute();
            $stmt->close();
            $insertados++;
        } catch (Exception $e) {
            error_log('[crear_cronograma] ' . $e->getMessage());
            $err_msgs[] = "Error al guardar {$hora_salida} ({$dia}).";
            $omitidos++;
        }
    }
}

// Respuesta final
if ($insertados === 0) {
    $msg = !empty($err_msgs)
        ? implode(' ', array_unique($err_msgs))
        : 'Todos los horarios ya estaban registrados.';
    echo json_encode(['success' => false, 'message' => $msg]);
} else {
    $msg = "Se agregaron {$insertados} horario(s) exitosamente.";
    if ($omitidos > 0) $msg .= " ({$omitidos} omitido(s) por duplicado o error)";
    echo json_encode(['success' => true, 'message' => $msg]);
}
