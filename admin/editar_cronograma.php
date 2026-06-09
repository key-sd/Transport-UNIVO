<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

/*Recibe las acciones de agregar/eliminar pero en eliminar solamente es la hora*/

$accion = trim($_POST['accion'] ?? '');

/* AGREGAR una hora a una ruta+día */
if ($accion === 'agregar') {

    $origen      = intval($_POST['id_sede_origen']  ?? 0);
    $destino     = intval($_POST['id_sede_destino'] ?? 0);
    $dia         = trim($_POST['dia_semana']        ?? '');
    $hora_salida = trim($_POST['hora_salida']       ?? '');

    /*Validaciones básicas*/
    $errores = [];
    if ($origen  <= 0)  $errores[] = 'El origen es obligatorio.';
    if ($destino <= 0)  $errores[] = 'El destino es obligatorio.';
    if ($origen === $destino) $errores[] = 'El origen y destino no pueden ser iguales.';

    $dias_validos = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
    if (!in_array($dia, $dias_validos)) $errores[] = 'El día seleccionado no es válido.';

    if (empty($hora_salida)) {
        $errores[] = 'La hora de salida es obligatoria.';
    }

    if (!empty($errores)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
        exit;
    }
    /*Validar rango de horas de salida*/
    $t    = strtotime($hora_salida);
    $tmin = strtotime('06:00');
    $tmax = strtotime('23:59');

    if ($t === false || $t < $tmin || $t > $tmax) {
        echo json_encode([
            'success' => false,
            'message' => 'La hora debe estar entre las 6:00 AM y las 11:59 PM.'
        ]);
        exit;
    }

    /*Calcular turno automáticamente Matutino: 06:00 – 11:59  y  Vespertino: 12:00 – 18:00 */
    $tmedio = strtotime('12:00');
    $turno  = ($t < $tmedio) ? 'Matutino' : 'Vespertino';

    /*Verificar duplicado */
    $stmtDup = $conn->prepare(
        "SELECT id FROM cronograma_horarios
         WHERE id_sede_origen = ? AND id_sede_destino = ?
           AND dia_semana = ? AND hora_salida = ?
         LIMIT 1"
    );
    $stmtDup->bind_param('iiss', $origen, $destino, $dia, $hora_salida);
    $stmtDup->execute();
    $stmtDup->store_result();

    if ($stmtDup->num_rows > 0) {
        $stmtDup->close();
        echo json_encode([
            'success' => false,
            'message' => "La hora {$hora_salida} ya está registrada para ese día y ruta."
        ]);
        exit;
    }
    $stmtDup->close();

    /*insertar*/ 
    try {
        $stmt = $conn->prepare(
            "INSERT INTO cronograma_horarios
             (id_sede_origen, id_sede_destino, dia_semana, hora_salida, turno)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iisss', $origen, $destino, $dia, $hora_salida, $turno);
        $stmt->execute();
        $nuevo_id = $conn->insert_id;
        $stmt->close();

        echo json_encode([
            'success'     => true,
            'message'     => "Hora {$hora_salida} ({$turno}) agregada correctamente.",
            'horario_id'  => $nuevo_id,
            'turno'       => $turno,
            'hora_salida' => $hora_salida,
        ]);

    } catch (Exception $e) {
        error_log('[editar_cronograma/agregar] ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno al guardar. Intenta de nuevo.']);
    }

/* Eliminar una hora de salida por el id*/
} elseif ($accion === 'eliminar') {

    $horario_id = intval($_POST['horario_id'] ?? 0);

    if ($horario_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de horario inválido.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM cronograma_horarios WHERE id = ?");
        $stmt->bind_param('i', $horario_id);
        $stmt->execute();
        $afectadas = $stmt->affected_rows;
        $stmt->close();

        if ($afectadas === 0) {
            echo json_encode(['success' => false, 'message' => 'El horario no existe o ya fue eliminado.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Hora eliminada correctamente.']);
        }

    } catch (Exception $e) {
        error_log('[editar_cronograma/eliminar] ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno al eliminar. Intenta de nuevo.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
