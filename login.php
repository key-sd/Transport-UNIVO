<?php
session_start();
include("includes/conexion.php");

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $usuario  = $_POST['usuario'];
    $password = $_POST['password'];

    $sql = "SELECT u.*, r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            WHERE u.usuario = '$usuario'
            AND u.password_hash = '$password'";

    $resultado = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($resultado) > 0) {

        $datos = mysqli_fetch_assoc($resultado);

        $_SESSION['usuario'] = $datos['usuario'];
        $_SESSION['rol']     = $datos['rol'];

        if ($datos['rol'] == "admin") {
            header("Location: /Transport-UNIVO/dashboards/admin.php");
            exit();
        } else if ($datos['rol'] == "estudiante") {
            header("Location: /Transport-UNIVO/dashboards/alumno.php");
            exit();
        } else if ($datos['rol'] == "conductor") {
            header("Location: /Transport-UNIVO/dashboards/conductor.php");
            exit();
        }

    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransporteU — Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_login.css">
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
            <img src="./img/logo.png" alt="Logo UNIVO" class="logo-univo mb-4">
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

    <!-- Lado derecho: degradado azul oscuro + formulario -->
    <div class="lado-derecho d-flex align-items-center justify-content-center">
        <div class="login-card">

            <div class="text-center mb-4">
                <div class="bus-icon d-flex align-items-center justify-content-center mx-auto mb-3">
                    <i class="bi bi-bus-front-fill fs-3"></i>
                </div>
                <h2 class="login-titulo fw-semibold">Iniciar Sesión</h2>
                <p class="login-subtitulo mb-0">Acceso exclusivo para comunidad universitaria</p>
            </div>

            <?php if ($error != ""): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="mb-3">
                    <label for="usuario" class="form-label fw-medium">Usuario</label>
                    <div class="input-group campo-input-group">
                        <span class="input-group-text border-end-0">
                            <i class="bi bi-person"></i>
                        </span>
                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            class="form-control border-start-0"
                            placeholder="usuario@univo.edu"
                            autocomplete="username"
                            value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                        >
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-medium">Contraseña</label>
                    <div class="input-group campo-input-group">
                        <span class="input-group-text border-end-0">
                            <i class="bi bi-lock"></i>
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
                            <i class="bi bi-eye" id="iconoOjo"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-ingresar w-100 fw-medium">Iniciar Sesión</button>

            </form>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>