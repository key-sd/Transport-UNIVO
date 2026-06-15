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

$conductor_id         = intval($_POST['conductor_id']         ?? 0);
$nombre               = trim($_POST['nombre']                 ?? '');
$apellido             = trim($_POST['apellido']               ?? '');
$telefono             = trim($_POST['telefono']               ?? '');
$codigo = trim($_POST['codigo']   ?? '');
$password             = $_POST['password']                    ?? '';
$confirmar_pwd        = $_POST['confirmar_password']          ?? '';

$errores = [];

if ($conductor_id <= 0)           $errores[] = 'ID de conductor inválido.';
if (empty($nombre))               $errores[] = 'El nombre es obligatorio.';
if (empty($apellido))             $errores[] = 'El apellido es obligatorio.';
if (empty($telefono))             $errores[] = 'El teléfono es obligatorio.';
if (empty($codigo)) $errores[] = 'El código es obligatorio.';

// contraseña opcional en edición — solo valida si se escribió algo
$cambiarPassword = !empty($password);
if ($cambiarPassword) {
    if (strlen($password) < 8)        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($password !== $confirmar_pwd) $errores[] = 'Las contraseñas no coinciden.';
}

// solo ppermite letras, tilfes y espacios en nombtes y apellidos
if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/', $nombre))
    $errores[] = 'El nombre solo puede contener letras.';

if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/', $apellido))
    $errores[] = 'El apellido solo puede contener letras.';
// solo permite el formato c y C
if (!preg_match('/^c\d{4}$/', $codigo))
    $errores[] = 'El código debe tener el formato c0000 (letra c seguida de 4 dígitos).';
// valida que solo sean numeros salvadoreños
if (!preg_match('/^[267]\d{3}-?\d{4}$/', $telefono))
    $errores[] = 'El teléfono debe ser un número salvadoreño válido (ej. 7000-1234).';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

$stmtGet = $conn->prepare("SELECT usuario_id FROM conductores WHERE id = ? LIMIT 1");
$stmtGet->bind_param('i', $conductor_id);
$stmtGet->execute();
$stmtGet->bind_result($usuario_id);
$stmtGet->fetch();
$stmtGet->close();

if (empty($usuario_id)) {
    echo json_encode(['success' => false, 'message' => 'Conductor no encontrado.']);
    exit;
}

// 4. VERIFICAR QUE EL CÓDIGO NO LO USE OTRO USUARIO
$stmtDup = $conn->prepare(
    "SELECT id FROM usuarios WHERE codigo = ? AND id != ? LIMIT 1"
);
$stmtDup->bind_param('si', $codigo, $usuario_id);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode([
        'success' => false,
        'message' => "El código '{$codigo}' ya está en uso por otro usuario."
    ]);
    exit;
}
$stmtDup->close();


$conn->begin_transaction();

try {
    if ($cambiarPassword) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmtU = $conn->prepare(
            "UPDATE usuarios SET codigo = ?, password_hash = ? WHERE id = ?"
        );
        $stmtU->bind_param('ssi', $codigo, $password_hash, $usuario_id);
    } else {
        $stmtU = $conn->prepare(
            "UPDATE usuarios SET codigo = ? WHERE id = ?"
        );
        $stmtU->bind_param('si', $codigo, $usuario_id);
    }
    $stmtU->execute();
    $stmtU->close();

    /* ── UPDATE conductores ── */
    $stmtC = $conn->prepare(
        "UPDATE conductores SET nombre = ?, apellido = ?, telefono = ? WHERE id = ?"
    );
    $stmtC->bind_param('sssi', $nombre, $apellido, $telefono, $conductor_id);
    $stmtC->execute();
    $stmtC->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Conductor {$nombre} {$apellido} actualizado exitosamente."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[editar_conductor] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno al actualizar. Intenta de nuevo.'
    ]);
}