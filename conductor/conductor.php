<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
    header("Location: /Transport-UNIVO/login.php");
    exit();
}
include("../includes/conexion.php");

// Datos del conductor desde sesión
$nombre_conductor = $_SESSION['usuario'] ?? 'Conductor';
$conductor_id = $_SESSION['usuario_id'] ?? null;

// Buscar datos del conductor
$datos_conductor = null;
if ($conductor_id) {
    $stmt = $conn->prepare("SELECT c.nombre, c.apellido FROM conductores c INNER JOIN usuarios u ON u.id = c.usuario_id WHERE u.id = ?");
    $stmt->bind_param('i', $conductor_id);
    $stmt->execute();
    $datos_conductor = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$nombre_display = $datos_conductor ? $datos_conductor['nombre'] . ' ' . $datos_conductor['apellido'] : $nombre_conductor;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransporteU — Conductor</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../css/conductor.css">
</head>
<body>

    <!-- HEADER -->
    <header class="conductor-header">
        <div class="header-izq">
            <img src="../img/logo.png" alt="Logo" class="header-logo">
            <div>
                <p class="header-saludo">Bienvenido</p>
                <p class="header-nombre"><?php echo htmlspecialchars($nombre_display); ?></p>
            </div>
        </div>
        <div class="header-der">
            <div class="header-fecha-hora">
                <p class="header-hora" id="reloj">--:--</p>
                <p class="header-fecha" id="fecha-hoy"></p>
            </div>
            <a href="../includes/logout.php" class="btn-logout" title="Cerrar sesión">
                <i class="ri-logout-box-r-line"></i>
            </a>
        </div>
    </header>

    <main class="conductor-main">

        <!-- TARJETA: RUTA Y HORARIOS DE HOY -->
        <section class="card-conductor animate__animated animate__fadeInUp">
            <div class="card-titulo">
                <i class="ri-route-line"></i>
                <span>Mis viajes de hoy</span>
            </div>
            <div id="contenedor-horarios">
                <div class="cargando">
                    <i class="ri-loader-4-line spin"></i> Cargando horarios...
                </div>
            </div>
        </section>

        <!-- TARJETA: PRÓXIMA SALIDA -->
        <section class="card-conductor card-proxima animate__animated animate__fadeInUp" style="animation-delay:.1s">
            <div class="card-titulo">
                <i class="ri-timer-flash-line"></i>
                <span>Próxima salida</span>
            </div>
            <div class="proxima-info">
                <p class="proxima-hora" id="proxima-hora">--:--</p>
                <p class="proxima-ruta" id="proxima-ruta">Cargando...</p>
                <div class="cuenta-regresiva">
                    <i class="ri-time-line"></i>
                    <span id="cuenta-regresiva">--</span>
                </div>
            </div>
        </section>

        <!-- TARJETA: ESTADO DEL MICRO -->
        <section class="card-conductor animate__animated animate__fadeInUp" style="animation-delay:.2s">
            <div class="card-titulo">
                <i class="ri-bus-line"></i>
                <span>Estado del microbús</span>
            </div>

            <div class="estado-grid">
                <button class="btn-estado" data-estado="en_sede">
                    <i class="ri-map-pin-line"></i>
                    <span>En sede</span>
                </button>
                <button class="btn-estado" data-estado="proximo_salir">
                    <i class="ri-bus-2-line"></i>
                    <span>Próximo a salir</span>
                </button>
                <button class="btn-estado" data-estado="en_trafico">
                    <i class="ri-road-map-line"></i>
                    <span>En tráfico</span>
                </button>
                <button class="btn-estado" data-estado="llegando">
                    <i class="ri-flag-line"></i>
                    <span>Llegando</span>
                </button>
            </div>

            <div class="capacidad-grupo">
                <p class="capacidad-label">Capacidad actual</p>
                <div class="capacidad-opciones">
                    <button class="btn-capacidad" data-cap="disponible">
                        <i class="ri-checkbox-blank-circle-line"></i> Disponible
                    </button>
                    <button class="btn-capacidad" data-cap="medio_lleno">
                        <i class="ri-checkbox-blank-circle-fill" style="color:#f5c518"></i> Medio lleno
                    </button>
                    <button class="btn-capacidad" data-cap="lleno">
                        <i class="ri-checkbox-blank-circle-fill" style="color:#ef4444"></i> Lleno
                    </button>
                </div>
            </div>

            <button class="btn-guardar-estado" id="btn-guardar-estado">
                <i class="ri-save-line me-2"></i>Guardar estado
            </button>

            <p class="ultima-actualizacion" id="ultima-actualizacion"></p>
        </section>

        <!-- TARJETA: MAPA -->
        <section class="card-conductor card-mapa animate__animated animate__fadeInUp" style="animation-delay:.3s">
            <div class="card-titulo">
                <i class="ri-map-2-line"></i>
                <span>Ubicación en tiempo real</span>
                <button class="btn-gps" id="btn-gps">
                    <i class="ri-gps-line"></i> Activar GPS
                </button>
            </div>
            <div id="mapa-conductor"></div>
            <p class="gps-estado" id="gps-estado">GPS inactivo</p>
        </section>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/conductor.js"></script>
</body>
</html>