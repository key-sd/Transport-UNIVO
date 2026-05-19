<?php
session_start();

// protección de ruta por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /Transport-UNIVO/login.php");
    exit();
}

include("../includes/conexion.php");

// total de conductores — tabla usuarios y roles ya existen
$q = mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id WHERE r.nombre = 'conductor'");
$total_conductores = mysqli_fetch_assoc($q)['total'];

// total de pasajeros — tabla usuarios y roles ya existen
$q = mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id WHERE r.nombre = 'estudiante'");
$total_pasajeros = mysqli_fetch_assoc($q)['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransporteU — Admin</title>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>

    <link rel="stylesheet" href="../css/admin.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body>

<div class="admin-wrapper">

    <?php include("../components/sidebar_admin.php"); ?>

    <div class="admin-contenido">

        <?php include("../components/navbar_admin.php"); ?>

        <main class="admin-main">

            <!-- banner -->
            <div class="banner-bienvenida animate__animated animate__fadeInDown">
                <div class="banner-texto">
                    <p class="banner-sub">Bienvenido de vuelta</p>
                    <h2 class="banner-titulo">¡Hola, <?php echo ucfirst($_SESSION['usuario']); ?>!</h2>
                    <p class="banner-desc">Aquí tienes un resumen de lo que está pasando hoy.</p>
                </div>
                <div class="banner-icono">
                    <i class="ri-shield-user-line"></i>
                </div>
                <div class="banner-circulo banner-circulo-1"></div>
                <div class="banner-circulo banner-circulo-2"></div>
            </div>

            <!-- estadísticas -->
            <div class="stats-grid animate__animated animate__fadeInUp">

                <div class="stat-card">
                    <div class="stat-card-top">
                        <p class="stat-label">Conductores</p>
                        <div class="stat-icono" style="background:#0d234615;">
                            <i class="ri-steering-2-line" style="color:#0d2346;"></i>
                        </div>
                    </div>
                    <p class="stat-numero">-aquí va conexion-</p>
                    <p class="stat-nota">registrados en el sistema</p>
                </div>

                <div class="stat-card">
                    <div class="stat-card-top">
                        <p class="stat-label">Pasajeros</p>
                        <div class="stat-icono" style="background:#f5c51820;">
                            <i class="ri-group-line" style="color:#be8f02;"></i>
                        </div>
                    </div>
                    <p class="stat-numero">-aquí va conexion-</p>
                    <p class="stat-nota">registrados en el sistema</p>
                </div>

                <!-- estas dos tarjetas son estáticas hasta que existan las tablas -->
                <div class="stat-card">
                    <div class="stat-card-top">
                        <p class="stat-label">Rutas en Marcha</p>
                        <div class="stat-icono" style="background:#1862a815;">
                            <i class="ri-map-2-line" style="color:#1862a8;"></i>
                        </div>
                    </div>
                    <p class="stat-numero">--</p>
                    <p class="stat-nota">en operación</p>
                </div>

                <div class="stat-card">
                    <div class="stat-card-top">
                        <p class="stat-label">Total de Unidades</p>
                        <div class="stat-icono" style="background:#22c55e15;">
                            <i class="ri-bus-line" style="color:#16a34a;"></i>
                        </div>
                    </div>
                    <p class="stat-numero">--</p>
                    <p class="stat-nota">en el sistema</p>
                </div>

            </div>

            <!-- esto cambiaría cuando se creen las tablas necesarias en la bd -->
            <div class="dashboard-grid animate__animated animate__fadeInUp">

                <div class="dash-card">
                    <p class="dash-card-titulo">Estado de unidades</p>
                    <div class="estado-lista">
                        <div class="estado-item estado-en-ruta">
                            <div class="estado-punto"></div>
                            <span>Unidad 01 — Ruta 1</span>
                            <span class="estado-badge badge-en-ruta">En ruta</span>
                        </div>
                        <div class="estado-item estado-por-salir">
                            <div class="estado-punto"></div>
                            <span>Unidad 02 — Ruta 2</span>
                            <span class="estado-badge badge-por-salir">Por salir</span>
                        </div>
                        <div class="estado-item estado-llego">
                            <div class="estado-punto"></div>
                            <span>Unidad 03 — Ruta 3</span>
                            <span class="estado-badge badge-llego">Llegó</span>
                        </div>
                    </div>
                </div>
                <!-- esto también con las tablas de la bd -->
                <div class="dash-card">
                    <p class="dash-card-titulo">Conductores activos hoy</p>
                    <div class="conductores-lista">
                        <div class="conductor-item">
                            <div class="conductor-avatar" style="background:#0d234620; color:#0d2346;">JM</div>
                            <div class="conductor-info">
                                <p class="conductor-nombre">Juan Martínez</p>
                                <p class="conductor-ruta">Ruta 2</p>
                            </div>
                            <span class="estado-badge badge-en-ruta">En ruta</span>
                        </div>
                        <div class="conductor-item">
                            <div class="conductor-avatar" style="background:#f5c51826; color:#be8f02;">CR</div>
                            <div class="conductor-info">
                                <p class="conductor-nombre">Carlos Ramos</p>
                                <p class="conductor-ruta">Ruta 1</p>
                            </div>
                            <span class="estado-badge badge-por-salir">Por salir</span>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>