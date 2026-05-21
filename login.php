<?php
session_start();
require_once 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $codigo   = trim($_POST['usuario']   ?? '');
    $password = trim($_POST['password']  ?? '');

    if (empty($codigo) || empty($password)) {
        $_SESSION['error'] = "Por favor completa todos los campos.";
        header("Location: login.php");
        exit();
    }

    /* ── Prepared statement — sin inyección SQL ── */
    $sql  = "SELECT u.id, u.codigo_universitario, u.password_hash, r.nombre AS rol
             FROM usuarios u
             INNER JOIN roles r ON u.rol_id = r.id
             WHERE u.codigo_universitario = ?
             LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $codigo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $datos = $resultado->fetch_assoc();

        /* ── Verificar contraseña con bcrypt ── */
        if (password_verify($password, $datos['password_hash'])) {

            /* Regenerar ID de sesión para evitar session fixation */
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $datos['id'];
            $_SESSION['usuario']    = $datos['codigo_universitario'];
            $_SESSION['rol']        = $datos['rol'];   // 'admin' | 'conductor' | 'pasajero'

            switch ($datos['rol']) {
                case 'admin':
                    header("Location: /Transport-UNIVO/admin/admin.php");
                    break;
                case 'conductor':
                    header("Location: /Transport-UNIVO/conductor/conductor.php");
                    break;
                case 'pasajero':
                    header("Location: /Transport-UNIVO/pasajero/pasajero.php");
                    break;
                default:
                    $_SESSION['error'] = "Rol no reconocido.";
                    header("Location: login.php");
            }
            exit();

        } else {
            /* Contraseña incorrecta */
            $_SESSION['error'] = "Usuario o contraseña incorrectos.";
            header("Location: login.php");
            exit();
        }

    } else {
        /* Usuario no encontrado */
        $_SESSION['error'] = "Usuario o contraseña no encontrados.";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransporteU — Iniciar Sesión</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS propio -->
    <link rel="stylesheet" href="css/login.css">
    <link rel="icon" type="image/x-icon" href="img/logo.png">
</head>
<body>

<div class="login-wrapper">

    <!-- Lado izquierdo: carrusel + texto -->
    <div class="lado-izquierdo">

        <div class="fondo-carrusel">
            <div class="fondo-slide activo" style="background-image: url('img/anchico.webp');"></div>
            <div class="fondo-slide" style="background-image: url('img/central.jpg');"></div>
            <div class="fondo-slide" style="background-image: url('img/ciudad-universitaria.jpg');"></div>
        </div>

        <div class="overlay"></div>

        <div class="lado-texto">
            <img src="img/logo.png" alt="Logo UNIVO" class="logo-univo mb-4">
            <p class="bienvenida-sub">Bienvenido/a</p>
            <h1 class="bienvenida-titulo">Sistema de Transporte<br>UNIVO</h1>
            <p class="bienvenida-desc">Informate del transporte universitario.</p>
        </div>

        <div class="carrusel-dots">
            <span class="dot activo" data-index="0"></span>
            <span class="dot" data-index="1"></span>
            <span class="dot" data-index="2"></span>
        </div>

    </div>

    <!-- Lado derecho -->
    <div class="lado-derecho d-flex align-items-center justify-content-center">

        <div class="login-card animate__animated animate__fadeInRight">

            <div class="text-center mb-4">
                <div class="bus-icon d-flex align-items-center justify-content-center mx-auto mb-3">
                    <i class="ri-bus-2-fill" style="font-size: 28px;"></i>
                </div>
                <h2 class="login-titulo fw-semibold">Iniciar Sesión</h2>
                <p class="login-subtitulo mb-0">Acceso exclusivo para comunidad universitaria</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="flex items-center p-4 mb-4 text-red-800 rounded-lg bg-red-50 animate__animated animate__shakeX" role="alert">
                    <i class="ri-error-warning-fill me-2"></i>
                    <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="mb-3">
                    <label for="usuario" class="form-label fw-medium">Código universitario</label>
                    <div class="input-group campo-input-group">
                        <span class="input-group-text border-end-0">
                            <i class="ri-user-3-line"></i>
                        </span>
                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            class="form-control border-start-0"
                            placeholder="Ej: u2026001"
                            autocomplete="username"
                            value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                        >
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-medium">Contraseña</label>
                    <div class="input-group campo-input-group">
                        <span class="input-group-text border-end-0">
                            <i class="ri-lock-2-line"></i>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control border-start-0 border-end-0"
                            placeholder="••••••••"
                            autocomplete="current-password"
                        >
                        <button type="button" class="input-group-text border-start-0" id="btnVerPass">
                            <i class="ri-eye-line" id="iconoOjo"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-ingresar w-100 fw-medium">
                    <i class="ri-login-box-line me-2"></i>Iniciar Sesión
                </button>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>