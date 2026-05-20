<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// recoger y limpiar los datos del form
$nombre               = trim($_POST['nombre']               ?? '');
$apellido             = trim($_POST['apellido']             ?? '');
$telefono             = trim($_POST['telefono']             ?? '');
$codigo_universitario = trim($_POST['codigo_universitario'] ?? '');
$password             = $_POST['password']                  ?? '';
$confirmar_pwd        = $_POST['confirmar_password']        ?? '';

// validaciones básicas
$errores = [];
if (empty($nombre))               $errores[] = 'El nombre es obligatorio.';
if (empty($apellido))             $errores[] = 'El apellido es obligatorio.';
if (empty($telefono))             $errores[] = 'El teléfono es obligatorio.';
if (empty($codigo_universitario)) $errores[] = 'El código universitario es obligatorio.';
if (strlen($password) < 8)        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
if ($password !== $confirmar_pwd) $errores[] = 'Las contraseñas no coinciden.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

// verificar que el código universitario no esté en uso
$stmtDup = $conn->prepare("SELECT id FROM usuarios WHERE codigo_universitario = ? LIMIT 1");
$stmtDup->bind_param('s', $codigo_universitario);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode(['success' => false, 'message' => "El código '{$codigo_universitario}' ya está registrado."]);
    exit;
}
$stmtDup->close();

// cifrar la contraseña
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// obtener el id del rol conductor desde la BD
$stmtRol = $conn->prepare("SELECT id FROM roles WHERE nombre = 'conductor' LIMIT 1");
$stmtRol->execute();
$stmtRol->bind_result($rol_id);
$stmtRol->fetch();
$stmtRol->close();

if (empty($rol_id)) {
    echo json_encode(['success' => false, 'message' => "No se encontró el rol 'conductor' en la base de datos."]);
    exit;
}

// insertar en usuarios y conductores dentro de una transacción
$conn->begin_transaction();

try {

    $stmtU = $conn->prepare("INSERT INTO usuarios (codigo_universitario, password_hash, rol_id) VALUES (?, ?, ?)");
    $stmtU->bind_param('ssi', $codigo_universitario, $password_hash, $rol_id);
    $stmtU->execute();
    $usuario_id = $conn->insert_id;
    $stmtU->close();

    $stmtC = $conn->prepare("INSERT INTO conductores (usuario_id, nombre, apellido, telefono) VALUES (?, ?, ?, ?)");
    $stmtC->bind_param('isss', $usuario_id, $nombre, $apellido, $telefono);
    $stmtC->execute();
    $stmtC->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Conductor {$nombre} {$apellido} creado exitosamente."]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[crear_conductor] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al guardar. Intenta de nuevo.']);
}